# Code review: PILOT-CASE-IMPORT-001

- Reviewer: `Codex agent /root/pilot_import_code_review_fresh` (independent Gate 5 reviewer; did not author the specification, approved test/tracer, importer, CLI, or proxy)
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Production scope: `app/InstallationProcess/PilotCaseImporter.php`, `bin/fmonitor2-import-cases.php`
- Specification: [`specs/PILOT-CASE-IMPORT-001.md`](../../specs/PILOT-CASE-IMPORT-001.md), version `0.2`, `APPROVED 2026-08-28`
- Approved test review: [`reviews/tests/PILOT-CASE-IMPORT-001.md`](../tests/PILOT-CASE-IMPORT-001.md), superseding verdict `APPROVED`
- Previous Gate 5: `CHANGES_REQUIRED` against v0.1 stdin semantics
- Superseding Gate 5: `APPROVED`

## Standards

`APPROVED`. No documented-standard breach or actionable Fowler-baseline smell was found. The CLI has cohesive seams for environment/argv parsing, connection, reconciliation, cleanup and output; the importer owns schema, transaction, eligibility and exact-row reconciliation concerns. Identifier interpolation follows strict prefix validation, selected IDs are bound parameters, and caught exceptions, SQL, configuration, legacy values and filesystem details are never serialized.

The v0.2 stdin boundary is deterministic by construction: neither production file opens, reads, polls, validates, waits for, nor reflects stdin. An open empty writer, immediately supplied adversarial bytes, and bytes delivered after the database operation has been blocked therefore cannot affect parsing, database effects, output or termination. Normal, rejected, schema, retry, confirmed-failure and unknown-commit paths have bounded connection/transaction cleanup. No residual importer, proxy or test process remained after verification.

## Spec

`APPROVED`. Required env and argv grammar fail before connection; charset and exact target/required-legacy schema are checked before mutation. Existing—including progressed—cases are classified without rereading legacy eligibility. New IDs use only the nine approved legacy fields, inherited whitespace/date/zero/fallback rules, exact cutoff and stable rejection ordering.

The batch locks target keys and new legacy rows in deterministic ID order, rejects all-or-nothing before insert, inserts only the exact empty shape with one operation timestamp, and commits before success output. Duplicate/deadlock/timeout retries follow confirmed rollback and reread the whole batch. The two-process race produces exactly one imported result and one already-present result. The least-privilege principal succeeds with legacy/process `SELECT` and target `INSERT`, without `UPDATE` or `DELETE`.

An uncertain `COMMIT` is not retried as an import. A new connection reports recovered success only when every expected-new ID has the exact empty shape, revision and current operation timestamp; absent or stale-operation rows return `IMPORT_OUTCOME_UNKNOWN`. Confirmed failure, schema/database failure, exact JSON key order/newline/stderr, redaction, and no mutation outside the case table all match v0.2. No scope creep was found.

## Verification

```text
php -l app/InstallationProcess/PilotCaseImporter.php: PASS
php -l bin/fmonitor2-import-cases.php: PASS
php -l tests/InstallationProcess/pilot_case_import_001_test.php: PASS
pilot_case_import_001 focused: PASS
InstallationProcess suite: 38/38 sequential PASS
InstallationProcess suite: 38/38 parallel (`xargs -P8`) PASS
git diff --check: PASS
.test-artifacts children: none
residual importer/proxy/test processes: none
```

The intentionally dirty handoff has no usable committed slice fixed point; the review was scoped to the normative v0.2 spec, approved test/review, production importer/CLI and their dependencies. Standards and Spec axes were performed by separately tasked independent agents and both returned `PASS`.

## Findings

None.

Gate 5 is approved.
