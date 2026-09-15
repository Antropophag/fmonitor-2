#!/usr/bin/env python3
"""Explicit CI selection, category execution and evidence aggregation (stdlib only)."""
import argparse
import json
import math
import os
from pathlib import Path
import subprocess
import sys
import time
import importlib.util

ROOT = Path(__file__).resolve().parents[2]
CATEGORIES = ['unit', 'integration', 'e2e', 'governance']
INTEGRATION_TIMINGS = ROOT / 'tools/verification/integration-timings.tsv'
# Missing or unusable historical data must remain schedulable with positive weight.
INTEGRATION_FALLBACK_WEIGHT = 1.0
HARNESS_CORE = [
    'tools/delivery/*', 'tests/Verification/delivery_harness*_test.py',
    'tests/Verification/change_verification_001_test.py',
    'tests/Verification/verification_ci_001_test.py',
    'tests/Verification/verification_inventory_001_test.py',
    'specs/DELIVERY-HARNESS*.md', 'openspec/changes/*delivery-harness*/**',
]
HARNESS_METADATA = HARNESS_CORE + [
    '.github/workflows/quality-graph.yml', '.quality-graph/verification-policy.json',
    'tools/verification/ci.py', 'tools/verification/inventory.py',
    'tools/verification/suites.tsv', 'reviews/tests/*HARNESS*.md',
    'reviews/code/*HARNESS*.md', 'AGENTS.md', 'docs/operations/current-delivery-goal.md',
]


def expected_job_conclusions(mode):
    """Canonical Quality Graph job obligations for an admitted CI mode."""
    if mode not in {'full', 'harness', 'docs', 'fast'}:
        raise ValueError('unsupported admission mode')
    expected = {'plan': 'SUCCESS', 'fast': 'SKIPPED' if mode == 'harness' else 'SUCCESS',
                'harness': 'SUCCESS' if mode == 'harness' else 'SKIPPED'}
    expected.update(dict.fromkeys(('unit', 'e2e', 'governance'),
                                  'SUCCESS' if mode == 'full' else 'SKIPPED'))
    if mode == 'full':
        expected['Integration (1/2)'] = 'SUCCESS'
        expected['Integration (2/2)'] = 'SUCCESS'
    else:
        expected['Integration (${{ matrix.shard }}/2)'] = 'SKIPPED'
    expected['verify'] = 'SUCCESS'
    return expected


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
    previous_bytecode = sys.dont_write_bytecode
    try:
        sys.dont_write_bytecode = True
        module_spec.loader.exec_module(module)
    finally:
        sys.dont_write_bytecode = previous_bytecode
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


def inventory_module():
    path = ROOT / 'tools/verification/inventory.py'
    spec = importlib.util.spec_from_file_location('fmonitor_verification_inventory', path)
    module = importlib.util.module_from_spec(spec)
    previous_bytecode = sys.dont_write_bytecode
    try:
        sys.dont_write_bytecode = True
        spec.loader.exec_module(module)
    finally:
        sys.dont_write_bytecode = previous_bytecode
    return module


def inventory():
    return [(entry.category, entry.runtime, entry.path)
            for entry in inventory_module().load(ROOT)]


def integration_weights(paths):
    """Return advisory weights for canonical paths; timing rows never add membership."""
    weights = {}
    invalid = set()
    try:
        lines = INTEGRATION_TIMINGS.read_text().splitlines()
    except OSError as error:
        print(f'INTEGRATION_TIMING_FALLBACK: {error}', file=sys.stderr)
        return dict.fromkeys(paths, INTEGRATION_FALLBACK_WEIGHT)
    for number, line in enumerate(lines, 1):
        if not line or line.startswith('#'):
            continue
        fields = line.split('\t')
        if len(fields) != 2 or not fields[0]:
            print(f'INTEGRATION_TIMING_FALLBACK: invalid row {number}', file=sys.stderr)
            continue
        path, raw_weight = fields
        if path in weights or path in invalid:
            weights.pop(path, None)
            invalid.add(path)
            print(f'INTEGRATION_TIMING_FALLBACK: duplicate path {path}', file=sys.stderr)
            continue
        try:
            weight = float(raw_weight)
        except ValueError:
            invalid.add(path)
            print(f'INTEGRATION_TIMING_FALLBACK: invalid weight for {path}', file=sys.stderr)
            continue
        if not math.isfinite(weight) or weight <= 0:
            invalid.add(path)
            print(f'INTEGRATION_TIMING_FALLBACK: invalid weight for {path}', file=sys.stderr)
            continue
        weights[path] = weight
    for path in sorted(paths):
        if path not in weights and path not in invalid:
            print(f'INTEGRATION_TIMING_FALLBACK: missing weight for {path}', file=sys.stderr)
    return {path: weights.get(path, INTEGRATION_FALLBACK_WEIGHT) for path in paths}


def integration_shards(items):
    """Allocate canonical integration items to exactly two deterministic LPT bins."""
    item_by_path = {path: (runtime, path) for runtime, path in items}
    weights = integration_weights(item_by_path)
    shards = [[], []]
    loads = [0.0, 0.0]
    for path in sorted(item_by_path, key=lambda value: (-weights[value], value)):
        index = 0 if loads[0] <= loads[1] else 1
        shards[index].append(item_by_path[path])
        loads[index] += weights[path]
    return [sorted(shard, key=lambda item: item[1]) for shard in shards]


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


def reconstructed_plan(base):
    diff = subprocess.run(['git', 'diff', '--name-only', '--no-renames', '-z',
                           base + '...HEAD', '--'], cwd=ROOT, capture_output=True, check=True)
    inputs = sorted(path.decode('utf-8', errors='surrogateescape')
                    for path in diff.stdout.split(b'\0') if path
                    and path.startswith(b'openspec/changes/')
                    and path.endswith(b'/verification-input.json'))
    if len(inputs) != 1:
        return None, ('no committed verification input' if not inputs
                      else 'multiple committed verification inputs')
    planner_path = ROOT / 'tools/delivery/change-verification.py'
    module_spec = importlib.util.spec_from_file_location('change_verification', planner_path)
    module = importlib.util.module_from_spec(module_spec)
    previous_bytecode = sys.dont_write_bytecode
    try:
        sys.dont_write_bytecode = True
        module_spec.loader.exec_module(module)
    finally:
        sys.dont_write_bytecode = previous_bytecode
    first = module.build(base, inputs[0])
    second = module.build(base, inputs[0])
    if module.canonical(first) != module.canonical(second):
        raise ValueError('verification plan reconstruction is not deterministic')
    actual = {item['path'] for item in first['paths']['actual']}
    if not actual or not actual.intersection(first['paths']['planned']):
        raise ValueError('verification input does not match the candidate diff')
    return first, None


def plan(base, event, verification_plan=None):
    files = []
    reason = 'event-requires-full'; mode = 'full'
    if verification_plan:
        candidate = json.loads(Path(verification_plan).read_text(), object_pairs_hook=strict_object)
        head = subprocess.run(['git', 'rev-parse', 'HEAD'], cwd=ROOT, capture_output=True,
                              text=True, check=True).stdout.strip()
        selected = candidate.get('selected_checks')
        if (candidate.get('head') != head or candidate.get('verification_lane') != 'FAST'
                or not isinstance(selected, list) or not selected):
            raise ValueError('verification plan is not exact-source FAST')
        print(json.dumps({'full': False, 'reason': 'exact-source-fast-plan', 'files': [],
                          'categories': [], 'mode': 'fast', 'selected_checks': selected},
                         ensure_ascii=True))
        return
    if event == 'pull_request':
        reason = 'unknown-base'
        if base:
            resolved = subprocess.run(['git', 'rev-parse', '--verify', '--end-of-options', base + '^{commit}'],
                                      cwd=ROOT, capture_output=True, text=True)
            if resolved.returncode == 0:
                reconstructed, reconstruction_reason = reconstructed_plan(resolved.stdout.strip())
                if reconstructed and reconstructed.get('verification_lane') == 'FAST':
                    result = dict(reconstructed)
                    result.update(full=False, mode='fast', categories=[],
                                  reason='exact-source-reconstructed-fast-plan',
                                  admission_expectations={
                                      'selected_success': 'required', 'selected_failure': 'failure',
                                      'selected_missing': 'failure', 'selected_skipped': 'failure',
                                      'policy_unselected': 'neutral', 'source': 'current_head'})
                    print(json.dumps(result, ensure_ascii=True, sort_keys=True))
                    return
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
                if reconstruction_reason and files and mode == 'full':
                    reason = reconstruction_reason
    full = mode == 'full'
    print(json.dumps({'full': full, 'reason': reason, 'files': files,
                      'categories': CATEGORIES if full else [], 'mode': mode}, ensure_ascii=True))


def category_items(category, shard=None):
    if shard is not None and (category != 'integration' or shard not in ['1/2', '2/2']):
        raise ValueError('shard must be 1/2 or 2/2 and is only supported for integration')
    items = [(runtime, path) for group, runtime, path in inventory() if group == category]
    if shard is not None:
        offset = 0 if shard == '1/2' else 1
        items = integration_shards(items)[offset]
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


def run_fast(base):
    plan_value, reason = reconstructed_plan(base)
    if not plan_value or plan_value.get('verification_lane') != 'FAST':
        raise ValueError(reason or 'reconstructed plan is not FAST')
    failures = []
    for check in plan_value['selected_checks']:
        identifier, argv = check.get('id'), check.get('argv')
        if not isinstance(identifier, str) or not isinstance(argv, list):
            raise ValueError('invalid selected check')
        environment = dict(os.environ)
        environment['PYTHONDONTWRITEBYTECODE'] = '1'
        record = harness_run(argv, environment)
        if record.get('outcome') != 'GREEN':
            failures.append(identifier)
    if failures:
        raise ValueError('selected check failed: ' + ','.join(failures))
    print('FAST_SELECTED_OK')


def run_fast_node(base, event):
    selected, _ = reconstructed_plan(base)
    if event == 'pull_request' and selected and selected.get('verification_lane') == 'FAST':
        return run_fast(base)
    commands = [
        ['python3', 'tools/verification/inventory.py', 'validate'],
        ['make', 'lint', 'architecture-check'],
        ['python3', 'tools/delivery/render-dependencies.py', '--check'],
        ['python3', 'tests/Verification/development_setup_001_test.py'],
        ['uv', 'sync', '--frozen'],
        ['uv', 'run', '--frozen', 'python', 'tools/delivery/render-current-quality-graph.py', '--check'],
        ['python3', 'tools/delivery/check-current-quality-graph.py', '--root', '.'],
        ['git', 'diff', '--check'],
    ]
    for argv in commands:
        subprocess.run(argv, cwd=ROOT, check=True)


def aggregate(full, raw, mode=None, fast_plan=None, base=None):
    results = json.loads(raw, object_pairs_hook=strict_object)
    if mode == 'fast':
        if full != 'false' or not (fast_plan or base):
            raise ValueError('FAST admission requires bounded mode and a plan')
        if fast_plan:
            plan = json.loads(Path(fast_plan).read_text(), object_pairs_hook=strict_object)
        else:
            plan, reason = reconstructed_plan(base)
            if plan is None:
                raise ValueError(reason)
        if plan.get('verification_lane') != 'FAST':
            raise ValueError('FAST admission requires a FAST plan')
        head = subprocess.run(['git', 'rev-parse', 'HEAD'], cwd=ROOT, capture_output=True,
                              text=True, check=True).stdout.strip()
        if plan.get('head') != head or (fast_plan and results.get('source') != head):
            raise ValueError('FAST admission source does not match current HEAD')
        observed = results.get('checks')
        if observed is None and base:
            expected_jobs = {'plan': 'success', 'fast': 'success', 'harness': 'skipped',
                             **dict.fromkeys(CATEGORIES, 'skipped')}
            if results != expected_jobs:
                raise ValueError(f'incomplete or failed FAST CI evidence: expected={expected_jobs}, actual={results}')
            observed = {item['id']: 'success' for item in plan['selected_checks']}
        if not isinstance(observed, dict):
            raise ValueError('FAST admission checks are missing')
        selected = plan.get('selected_checks')
        if not isinstance(selected, list) or not selected:
            raise ValueError('FAST plan has no selected checks')
        identifiers = [item.get('id') for item in selected if isinstance(item, dict)]
        if (len(identifiers) != len(selected) or any(not isinstance(value, str) or not value
                                                     for value in identifiers)
                or len(identifiers) != len(set(identifiers))):
            raise ValueError('FAST plan has invalid selected checks')
        for identifier in identifiers:
            if identifier not in observed:
                raise ValueError(f'missing selected check: {identifier}')
            status = observed[identifier]
            if status == 'skipped':
                raise ValueError(f'unexpectedly skipped selected check: {identifier}')
            if status != 'success':
                raise ValueError(f'selected check failed: {identifier}')
        print('FAST_VERIFY_OK')
        return
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
    selection.add_argument('--verification-plan')
    for name in ['list', 'run']:
        category = commands.add_parser(name)
        category.add_argument('category', choices=CATEGORIES)
        category.add_argument('--shard', choices=['1/2', '2/2'])
    fast = commands.add_parser('run-fast')
    fast.add_argument('--base', required=True)
    fast_node = commands.add_parser('run-fast-node')
    fast_node.add_argument('--base', required=True)
    fast_node.add_argument('--event', required=True)
    aggregation = commands.add_parser('aggregate')
    aggregation.add_argument('--full', choices=['true', 'false'], required=True)
    aggregation.add_argument('--mode', choices=['full', 'docs', 'harness', 'fast'])
    aggregation.add_argument('--fast-plan')
    aggregation.add_argument('--base')
    aggregation.add_argument('--results', required=True)
    commands.add_parser('verify-roster')
    args = parser.parse_args()
    try:
        if args.command == 'plan':
            plan(args.base, args.event, args.verification_plan)
        elif args.command == 'list':
            for runtime, path in category_items(args.category, args.shard):
                print(f'{runtime}\t{path}')
        elif args.command == 'run':
            return run_category(args.category, args.shard)
        elif args.command == 'run-fast':
            run_fast(args.base)
        elif args.command == 'run-fast-node':
            run_fast_node(args.base, args.event)
        elif args.command == 'aggregate':
            aggregate(args.full, args.results, args.mode, args.fast_plan, args.base)
        else:
            verify_roster()
    except (OSError, ValueError, TypeError, subprocess.SubprocessError) as error:
        print(f'SETUP_FAILURE: {error}', file=sys.stderr)
        return 1
    return 0


if __name__ == '__main__':
    sys.exit(main())
