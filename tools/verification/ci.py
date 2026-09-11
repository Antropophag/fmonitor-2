#!/usr/bin/env python3
"""Explicit CI selection, category execution and evidence aggregation (stdlib only)."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import sys
import time
import importlib.util

ROOT = Path(__file__).resolve().parents[2]
CATEGORIES = ['unit', 'integration', 'e2e', 'governance']
HARNESS_CORE = [
    'tools/delivery/*', 'tests/Verification/delivery_harness*_test.py',
    'tests/Verification/change_verification_001_test.py',
    'tests/Verification/verification_ci_001_test.py',
    'tests/Verification/verification_inventory_001_test.py',
    'specs/DELIVERY-HARNESS*.md', 'openspec/changes/*delivery-harness*/**',
]
HARNESS_METADATA = HARNESS_CORE + [
    '.github/workflows/quality-graph.yml', '.quality-graph/verification-policy.json',
    'tools/verification/ci.py', 'tools/verification/suites.tsv',
    'tools/verification/categories.json', 'reviews/tests/*HARNESS*.md',
    'reviews/code/*HARNESS*.md', 'AGENTS.md', 'docs/operations/current-delivery-goal.md',
]


def harness_run(argv, environment):
    harness = ROOT / 'tools/delivery/harness.py'
    python = os.environ.get('FMONITOR_HARNESS_PYTHON', sys.executable)
    result = subprocess.run([python, str(harness), 'run', '--', *argv], cwd=ROOT,
                            env=environment, capture_output=True, text=True)
    if not result.stdout.strip():
        raise ValueError(result.stderr.strip() or 'delivery harness returned no result')
    try:
        summary = json.loads(result.stdout)
    except json.JSONDecodeError as error:
        raise ValueError(f'delivery harness returned invalid JSON: {error}') from error
    module_spec = importlib.util.spec_from_file_location('fmonitor_delivery_harness', harness)
    module = importlib.util.module_from_spec(module_spec)
    module_spec.loader.exec_module(module)
    record = module.hydrate_summary(summary)
    if environment.get('GITHUB_ACTIONS') == 'true':
        for name in ['stdout_path', 'stderr_path']:
            stream = sys.stderr if name == 'stderr_path' else sys.stdout
            print(Path(record[name]).read_text(errors='replace'), end='', file=stream)
    else:
        print(json.dumps(summary, ensure_ascii=True, sort_keys=True, separators=(',', ':')))
        if record['outcome'] != 'GREEN' and record.get('excerpt'):
            print(record['excerpt'], end='' if record['excerpt'].endswith('\n') else '\n')
    return record


def strict_object(pairs):
    result = {}
    for key, value in pairs:
        if key in result:
            raise ValueError(f'duplicate JSON key: {key}')
        result[key] = value
    return result


def inventory():
    mapping = json.loads((ROOT / 'tools/verification/categories.json').read_text(),
                         object_pairs_hook=strict_object)
    if not isinstance(mapping, dict) or any(v not in CATEGORIES for v in mapping.values()):
        raise ValueError('invalid category mapping')
    items = []
    seen = set()
    for suite in ['unit', 'db', 'characterization', 'e2e']:
        result = subprocess.run(['bash', 'tools/verification/run.sh', 'list', suite],
                                cwd=ROOT, capture_output=True, text=True)
        if result.returncode:
            raise ValueError(result.stderr.strip())
        for line in result.stdout.splitlines():
            runtime, path = line.split('\t')
            if path in seen:
                raise ValueError(f'duplicate full-suite path: {path}')
            seen.add(path)
            items.append((runtime, path))
    if seen != set(mapping):
        raise ValueError(f'category inventory mismatch: missing={sorted(seen - set(mapping))} extra={sorted(set(mapping) - seen)}')
    return [(mapping[path], runtime, path) for runtime, path in items]


def docs_only(path):
    if path == 'README.md':
        return True
    return (path.startswith('docs/') and Path(path).suffix in ['.md', '.txt']
            and not path.startswith('docs/architecture/')
            and path != 'docs/development-process.md')


def matches_any(path, patterns):
    import fnmatch
    return any(fnmatch.fnmatchcase(path, pattern) for pattern in patterns)


def harness_only(paths):
    return (bool(paths) and any(matches_any(path, HARNESS_CORE) for path in paths)
            and all(matches_any(path, HARNESS_METADATA) for path in paths))


def plan(base, event):
    files = []
    reason = 'event-requires-full'; mode = 'full'
    if event == 'pull_request':
        reason = 'unknown-base'
        if base:
            resolved = subprocess.run(['git', 'rev-parse', '--verify', '--end-of-options', base + '^{commit}'],
                                      cwd=ROOT, capture_output=True, text=True)
            if resolved.returncode == 0:
                diff = subprocess.run(['git', 'diff', '--name-only', '--no-renames', '-z',
                                       resolved.stdout.strip() + '...HEAD', '--'], cwd=ROOT, capture_output=True)
                if diff.returncode == 0:
                    files = [p.decode('utf-8', errors='surrogateescape') for p in diff.stdout.split(b'\0') if p]
                    if files and all(docs_only(p) for p in files):
                        reason = 'docs-only'; mode = 'docs'
                    elif harness_only(files):
                        reason = 'agent-harness-only'; mode = 'harness'
                    else:
                        reason = 'code-or-unknown-impact'; mode = 'full'
    full = mode == 'full'
    print(json.dumps({'full': full, 'reason': reason, 'files': files,
                      'categories': CATEGORIES if full else [], 'mode': mode}, ensure_ascii=True))


def category_items(category, shard=None):
    if shard is not None and (category != 'integration' or shard not in ['1/2', '2/2']):
        raise ValueError('shard must be 1/2 or 2/2 and is only supported for integration')
    items = [(runtime, path) for group, runtime, path in inventory() if group == category]
    if shard is not None:
        offset = 0 if shard == '1/2' else 1
        items = sorted(items, key=lambda item: item[1])[offset::2]
    return items


def verify_roster():
    items = inventory()
    counts = {category: 0 for category in CATEGORIES}
    for category, _, _ in items:
        counts[category] += 1
    print(json.dumps({'status': 'GREEN', 'tests': len(items), 'categories': counts},
                     ensure_ascii=True, sort_keys=True))


def run_category(category, shard=None):
    items = category_items(category, shard)
    if category in ['integration', 'e2e']:
        # run.sh category supplies the same explicit defaults used by local test stages.
        check = subprocess.run(['php', '-r', '$c=@new mysqli(getenv("FMONITOR_TEST_DB_HOST"),getenv("FMONITOR_TEST_DB_ADMIN_USER"),getenv("FMONITOR_TEST_DB_ADMIN_PASSWORD"),null,(int)getenv("FMONITOR_TEST_DB_PORT")); exit($c->connect_errno===0?0:1);'], cwd=ROOT)
        if check.returncode:
            raise ValueError('test MariaDB unavailable; run make test-db-reset migrate')
    # Harness tests invoke make themselves; outer category selection is not theirs.
    runtime_env = dict(os.environ)
    for name in ['CATEGORY', 'SHARD', 'MAKEFLAGS', 'MFLAGS', 'MAKEOVERRIDES']:
        runtime_env.pop(name, None)
    started = time.monotonic()
    results = []
    for runtime, path in items:
        print(f'VERIFY {path}', flush=True)
        before = time.monotonic()
        try:
            summary = harness_run([runtime, path], runtime_env)
            status = summary['exit_code'] if summary.get('outcome') == 'GREEN' else (summary['exit_code'] or 1)
        except (OSError, ValueError, KeyError, TypeError) as error:
            print(f'REGRESSION_FAILURE: {path}: {error}', file=sys.stderr)
            status = 127
        elapsed = time.monotonic() - before
        results.append((path, elapsed, status))
        print(f'VERIFY_TIMING suite={category} runtime={runtime} file={path} seconds={elapsed:.3f} exit={status}', flush=True)
        if status:
            print(f'REGRESSION_FAILURE: {path}', file=sys.stderr)
    duration = time.monotonic() - started
    failures = sum(status != 0 for _, _, status in results)
    shard_field = f' shard={shard}' if shard is not None else ''
    print(f'CATEGORY_RESULT category={category} tests={len(results)} failures={failures} seconds={duration:.3f}{shard_field}', flush=True)
    summary = os.environ.get('GITHUB_STEP_SUMMARY')
    if summary:
        with open(summary, 'a') as out:
            label = f'{category} ({shard})' if shard is not None else category
            out.write(f'\n### {label}: {len(results)} tests, {failures} failures, {duration:.1f}s\n\n')
            out.write('| Slowest test | Seconds | Exit |\n|---|---:|---:|\n')
            for path, elapsed, status in sorted(results, key=lambda item: item[1], reverse=True)[:10]:
                out.write(f'| `{path}` | {elapsed:.3f} | {status} |\n')
    return 1 if failures else 0


def aggregate(full, raw, mode=None):
    results = json.loads(raw, object_pairs_hook=strict_object)
    if mode is None:
        expected = dict.fromkeys(['plan', 'fast'], 'success')
        expected.update(dict.fromkeys(CATEGORIES, 'success' if full == 'true' else 'skipped'))
        if results != expected:
            raise ValueError(f'incomplete or failed CI evidence: expected={expected}, actual={results}')
        print('VERIFY_OK' if full == 'true' else 'DOCS_VERIFY_OK')
        return
    if mode not in {'full', 'docs', 'harness'}:
        raise ValueError('invalid verification mode')
    expected = {'plan': 'success', 'fast': 'skipped' if mode == 'harness' else 'success',
                'harness': 'success' if mode == 'harness' else 'skipped'}
    expected.update(dict.fromkeys(CATEGORIES, 'success' if mode == 'full' else 'skipped'))
    if results != expected:
        raise ValueError(f'incomplete or failed CI evidence: expected={expected}, actual={results}')
    print({'full': 'VERIFY_OK', 'docs': 'DOCS_VERIFY_OK', 'harness': 'HARNESS_VERIFY_OK'}[mode])


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest='command', required=True)
    selection = commands.add_parser('plan')
    selection.add_argument('--base', default='')
    selection.add_argument('--event', required=True)
    for name in ['list', 'run']:
        category = commands.add_parser(name)
        category.add_argument('category', choices=CATEGORIES)
        category.add_argument('--shard', choices=['1/2', '2/2'])
    aggregation = commands.add_parser('aggregate')
    aggregation.add_argument('--full', choices=['true', 'false'], required=True)
    aggregation.add_argument('--mode', choices=['full', 'docs', 'harness'])
    aggregation.add_argument('--results', required=True)
    commands.add_parser('verify-roster')
    args = parser.parse_args()
    try:
        if args.command == 'plan':
            plan(args.base, args.event)
        elif args.command == 'list':
            for runtime, path in category_items(args.category, args.shard):
                print(f'{runtime}\t{path}')
        elif args.command == 'run':
            return run_category(args.category, args.shard)
        elif args.command == 'aggregate':
            aggregate(args.full, args.results, args.mode)
        else:
            verify_roster()
    except (OSError, ValueError, TypeError) as error:
        print(f'SETUP_FAILURE: {error}', file=sys.stderr)
        return 1
    return 0


if __name__ == '__main__':
    sys.exit(main())
