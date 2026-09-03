#!/usr/bin/env python3
"""Deterministic architecture ratchet for FMonitor 2.0.

The baseline records existing debt, never permissions for new debt. Run from any
working directory. Use --write-baseline only after an architectural review.
"""

from __future__ import annotations

import argparse
import collections
import hashlib
import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
BASELINE = Path(__file__).with_name("baseline.json")
SOURCE_ROOTS = ("app", "rapid-pilot", "bin", "public")
SOURCE_SUFFIXES = {".php", ".js", ".sql"}
IGNORED_PARTS = {"demo", "legacy-migration"}
DDL = re.compile(r"\b(?:CREATE|ALTER|DROP|TRUNCATE)\s+(?:TABLE|DATABASE|INDEX|USER)\b", re.I)
SQL = re.compile(r"\b(?:SELECT|INSERT\s+INTO|UPDATE|DELETE\s+FROM)\b", re.I)
MUTATION_SQL = re.compile(r"\b(?:INSERT\s+INTO|UPDATE|DELETE\s+FROM)\b", re.I)
WORKFORCE_MIGRATION_APPLY = re.compile(
    r"\b(?:BitrixWorkforceHistory|WorkforceCatalog)SchemaMigration\s*::\s*apply\s*\("
)
WORKFORCE_DDL = re.compile(
    r"\b(?:CREATE|ALTER|DROP)\s+TABLE\b[^;]*"
    r"(?:fm2_workforce_|workforce_(?:catalog|observations|sync_runs|sync_metadata))",
    re.I,
)
WORKFORCE_MIGRATION_IMPORT = re.compile(
    r"\buse\s+(?:[A-Za-z_][A-Za-z0-9_]*\\)*"
    r"(?P<class>BitrixWorkforceHistorySchemaMigration|WorkforceCatalogSchemaMigration)"
    r"(?:\s+as\s+(?P<alias>[A-Za-z_][A-Za-z0-9_]*))?\s*;",
    re.I,
)
WORKFORCE_TABLE_ASSIGNMENT = re.compile(
    r"\$(?P<variable>[A-Za-z_][A-Za-z0-9_]*)\s*=\s*[^;]*"
    r"(?:fm2_workforce_|workforce_(?:catalog|observations|sync_runs|sync_metadata))[^;]*;",
    re.I,
)
MUTATING_METHOD = re.compile(
    r"\bpublic\s+function\s+(prepare|confirm|open|close|record|accept|reject|"
    r"schedule|complete|reverse|assign|register|create|update|delete|block|unblock)"
    r"[A-Za-z0-9_]*\s*\(", re.I
)
NATIVE_SESSION_CALL = re.compile(
    r"\b(?:session_save_path|session_start|session_regenerate_id|session_write_close|session_destroy)\s*\("
)
SESSION_COMPATIBILITY_ROOT = re.compile(
    r"/home/fmonitor/\.local/state/fmonitor2/sessions"
)
UNSAFE_SESSION_REPAIR = re.compile(r"\b(?:chmod|chown)\s*\(")
SESSION_INTERNAL_FACTORY = re.compile(
    r"\b(?P<class>PilotSessionOperationResult|PilotSessionFilesystemEvent|PilotSessionInspectionResult)"
    r"::(?P<method>owner[A-Za-z0-9_]+|inspector[A-Za-z0-9_]+)\s*\("
)


def files() -> list[Path]:
    result: list[Path] = []
    for root in SOURCE_ROOTS:
        base = ROOT / root
        if not base.exists():
            continue
        result.extend(
            p for p in base.rglob("*")
            if p.is_file() and p.suffix in SOURCE_SUFFIXES and not any(x in IGNORED_PARTS for x in p.relative_to(ROOT).parts)
        )
    return sorted(result)


def production_file(path: Path) -> bool:
    rel = path.relative_to(ROOT).as_posix()
    name = path.name.lower()
    return not (
        rel.startswith("app/demo/")
        or name.startswith("verify-")
        or name.startswith("profile-")
        or name.endswith("_test.php")
    )


def without_php_global_call_qualifiers(source: str, *, assume_php: bool = False) -> str:
    """Remove qualifiers from direct global calls using PHP's own lexer."""
    prefix = "<?php " if assume_php else ""
    php = r'''$tokens=token_get_all(stream_get_contents(STDIN));$n=count($tokens);$attributeDepth=0;$previous=null;for($i=0;$i<$n;$i++){$token=$tokens[$i];$id=is_array($token)?$token[0]:null;$text=is_array($token)?$token[1]:$token;if($id===T_ATTRIBUTE)$attributeDepth=1;if($id===T_NAME_FULLY_QUALIFIED&&$attributeDepth===0&&$previous!==T_NEW&&preg_match('/^\\\\[A-Za-z_][A-Za-z0-9_]*$/D',$text)===1){$j=$i+1;while($j<$n&&is_array($tokens[$j])&&in_array($tokens[$j][0],[T_WHITESPACE,T_COMMENT,T_DOC_COMMENT],true))$j++;if(($tokens[$j]??null)==='(')$text=substr($text,1);}echo $text;if(!is_array($token)&&$attributeDepth>0){if($token==='[')$attributeDepth++;elseif($token===']')$attributeDepth--;}if(!is_array($token)||!in_array($id,[T_WHITESPACE,T_COMMENT,T_DOC_COMMENT],true))$previous=$id??$token;}'''
    try:
        completed = subprocess.run(
            ["php", "-r", php],
            input=prefix + source,
            text=True,
            capture_output=True,
            check=True,
        )
    except (OSError, subprocess.CalledProcessError) as error:
        raise RuntimeError("PHP tokenizer unavailable for architecture fingerprinting") from error
    return completed.stdout[len(prefix):] if assume_php else completed.stdout


def normalized_line(line: str) -> str:
    return re.sub(r"\s+", " ", without_php_global_call_qualifiers(line, assume_php=True).strip())


def finding(rule: str, path: Path, line_no: int, evidence: str, *, source_normalized: bool = False) -> str:
    rel = path.relative_to(ROOT).as_posix()
    value = re.sub(r"\s+", " ", evidence.strip()) if source_normalized else normalized_line(evidence)
    digest = hashlib.sha256(value.encode()).hexdigest()[:16]
    return f"{rule}|{rel}|{digest}"


def ddl_owner(path: Path) -> bool:
    rel = path.relative_to(ROOT).as_posix()
    return rel.startswith("app/InstallationProcess/") and path.name.endswith("SchemaMigration.php")


def workforce_migration_owner(path: Path) -> bool:
    rel = path.relative_to(ROOT).as_posix()
    return (
        rel == "bin/fmonitor2-migrate.php"
        or (rel.startswith("app/InstallationProcess/") and path.name.endswith("SchemaMigration.php"))
        or rel.startswith("rapid-pilot/verify-")
        or "/demo/" in rel
    )


def workforce_ownership_matches(text: str) -> list[tuple[str, int]]:
    matches = [("workforce-migration", match.start()) for match in WORKFORCE_MIGRATION_APPLY.finditer(text)]
    for imported in WORKFORCE_MIGRATION_IMPORT.finditer(text):
        owner = imported.group("alias") or imported.group("class")
        invocation = re.compile(rf"\b{re.escape(owner)}\s*::\s*apply\s*\(")
        matches.extend(("workforce-migration", match.start()) for match in invocation.finditer(text, imported.end()))
    matches.extend(("workforce-ddl", match.start()) for match in WORKFORCE_DDL.finditer(text))
    for assignment in WORKFORCE_TABLE_ASSIGNMENT.finditer(text):
        variable = re.escape(assignment.group("variable"))
        ddl = re.compile(
            rf"\b(?:CREATE|ALTER|DROP)\s+TABLE\b[^;]*(?:\{{\${variable}\}}|\${variable}\b)",
            re.I,
        )
        matches.extend(("workforce-ddl", match.start()) for match in ddl.finditer(text, assignment.end()))
    return sorted(set(matches), key=lambda item: item[1])


def sql_owner(path: Path) -> bool:
    rel = path.relative_to(ROOT).as_posix()
    if rel.startswith("app/IdentityAccess/"):
        return path.name.startswith("MariaDb")
    if rel.startswith("app/InspectionEvidence/"):
        return path.name.startswith("MariaDb")
    if rel.startswith("app/InstallationProcess/"):
        return (
            path.name.startswith("MariaDb")
            or path.name.endswith("SchemaMigration.php")
            or path.name in {"PilotCaseImporter.php", "ProductionInstallationProcessFacts.php"}
        )
    if rel.startswith("app/PilotHttp/"):
        return path.name.startswith("MariaDb")
    return False


def collect() -> dict[str, list[str] | dict[str, int]]:
    violations: dict[str, list[str]] = collections.defaultdict(list)
    hotspot: dict[str, int] = {}
    public_seams: list[str] = []
    for path in files():
        rel = path.relative_to(ROOT).as_posix()
        text = path.read_text(encoding="utf-8", errors="replace")
        lines = text.splitlines()
        fingerprint_lines = without_php_global_call_qualifiers(text).splitlines()
        if not workforce_migration_owner(path):
            for rule, offset in workforce_ownership_matches(text):
                number = text.count("\n", 0, offset) + 1
                violations["workforce_migration_ownership"].append(
                    finding(rule, path, number, fingerprint_lines[number - 1], source_normalized=True)
                )
        if not production_file(path):
            continue
        if len(lines) >= 150:
            hotspot[rel] = len(lines)
        for number, line in enumerate(lines, 1):
            session_matches = list(NATIVE_SESSION_CALL.finditer(line))
            compatibility_matches = list(SESSION_COMPATIBILITY_ROOT.finditer(line))
            repair_matches = list(UNSAFE_SESSION_REPAIR.finditer(line)) if compatibility_matches else []
            session_matches += repair_matches if repair_matches else compatibility_matches
            for match in session_matches:
                violations["session_storage_ownership"].append(
                    finding("session-owner", path, number, match.group(0))
                )
            for match in SESSION_INTERNAL_FACTORY.finditer(line):
                factory_class = match.group("class")
                allowed = (
                    factory_class in {"PilotSessionOperationResult", "PilotSessionFilesystemEvent"}
                    and rel == "app/IdentityAccess/FilesystemPilotSessionStorage.php"
                ) or (
                    factory_class == "PilotSessionInspectionResult"
                    and rel == "app/IdentityAccess/PilotSessionStorageInspector.php"
                )
                if not allowed:
                    violations["session_storage_ownership"].append(
                        finding("session-internal-factory", path, number, match.group(0))
                    )
            if DDL.search(line) and not ddl_owner(path):
                violations["ddl_ownership"].append(finding("ddl", path, number, fingerprint_lines[number - 1], source_normalized=True))
            if SQL.search(line) and not sql_owner(path):
                violations["sql_ownership"].append(finding("sql", path, number, fingerprint_lines[number - 1], source_normalized=True))
            if rel.startswith("rapid-pilot/") and (DDL.search(line) or MUTATION_SQL.search(line)):
                violations["rapid_pilot_boundary"].append(finding("rapid-mutation", path, number, fingerprint_lines[number - 1], source_normalized=True))
        if rel.startswith(("app/InstallationProcess/", "app/InspectionEvidence/", "app/IdentityAccess/")):
            forbidden_terms = r"(?:FMonitor2\\PilotHttp|FMonitor2\\RapidPilot|app/PilotHttp|rapid-pilot)"
            if rel.startswith("app/InstallationProcess/"):
                forbidden_terms = r"(?:" + forbidden_terms[3:-1] + r"|new\s+\?mysqli|new\s+MariaDb)"
            forbidden = re.compile(forbidden_terms)
            for number, line in enumerate(lines, 1):
                if forbidden.search(line):
                    violations["dependency_direction"].append(finding("dependency", path, number, line))
            for match in MUTATING_METHOD.finditer(text):
                method = re.search(r"function\s+([A-Za-z0-9_]+)", match.group(0), re.I)
                if method:
                    public_seams.append(f"{rel}::{method.group(1)}")
    return {
        **{key: sorted(value) for key, value in sorted(violations.items())},
        "hotspots": dict(sorted(hotspot.items())),
        "public_seams": sorted(public_seams),
    }


def compare(current: dict, baseline: dict) -> list[str]:
    errors: list[str] = []
    for item in current.get("workforce_migration_ownership", []):
        errors.append(f"workforce_migration_ownership: forbidden production owner: {item}")
    for item in current.get("session_storage_ownership", []):
        errors.append(f"session_storage_ownership: forbidden production owner: {item}")
    for rule in ("ddl_ownership", "sql_ownership", "dependency_direction", "rapid_pilot_boundary"):
        old = collections.Counter(baseline.get(rule, []))
        new = collections.Counter(current.get(rule, []))
        for item, count in sorted((new - old).items()):
            errors.append(f"{rule}: new violation ({count}x): {item}")
    old_hotspots = baseline.get("hotspots", {})
    for path, lines in current.get("hotspots", {}).items():
        if path not in old_hotspots:
            errors.append(f"hotspot_ratchet: new hotspot {path} ({lines} lines)")
        elif lines > old_hotspots[path]:
            errors.append(f"hotspot_ratchet: {path} grew {old_hotspots[path]} -> {lines} lines")
    old_seams = set(baseline.get("public_seams", []))
    for seam in sorted(set(current.get("public_seams", [])) - old_seams):
        errors.append(f"public_seam_ownership: unregistered state-changing seam {seam}")
    return errors


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--write-baseline", action="store_true", help="replace the reviewed current-state baseline")
    parser.add_argument("--json", action="store_true", help="emit machine-readable result")
    args = parser.parse_args()
    current = collect()
    if args.write_baseline:
        BASELINE.write_text(json.dumps(current, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(f"WROTE {BASELINE.relative_to(ROOT)}")
        return 0
    if not BASELINE.exists():
        print("ERROR architecture baseline missing", file=sys.stderr)
        return 2
    baseline = json.loads(BASELINE.read_text(encoding="utf-8"))
    errors = compare(current, baseline)
    result = {"ok": not errors, "errors": errors, "rules": 7}
    if args.json:
        print(json.dumps(result, ensure_ascii=False, sort_keys=True))
    elif errors:
        print("ARCHITECTURE CHECK FAILED")
        for error in errors:
            print(f"- {error}")
    else:
        print("ARCHITECTURE CHECK PASSED (7 rules)")
    return 1 if errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
