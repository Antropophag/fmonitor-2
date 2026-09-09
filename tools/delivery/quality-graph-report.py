#!/usr/bin/env python3
"""Render existing CI outcomes as native reports; never execute verification."""
import argparse
import json
import os
from pathlib import Path
import re
import shutil
import sys
import tempfile

NODES = ('plan', 'fast', 'unit', 'integration', 'e2e', 'governance', 'verify')
CATEGORIES = ('unit', 'integration', 'e2e', 'governance')
TITLES = {node: 'Integration' if node == 'integration' else node for node in NODES}
STATUSES = {'success': 'passed', 'failure': 'failed', 'cancelled': 'cancelled', 'skipped': 'skipped'}


def strict_object(pairs):
    result = {}
    for key, value in pairs:
        if key in result:
            raise ValueError('duplicate JSON key')
        result[key] = value
    return result


def outcomes(raw, full):
    values = json.loads(raw, object_pairs_hook=strict_object)
    if not isinstance(values, dict) or set(values) != set(NODES):
        raise ValueError('expected exactly seven CI jobs')
    if any(not isinstance(value, str) or value not in STATUSES for value in values.values()):
        raise ValueError('invalid job outcome')
    if values['plan'] == 'success':
        if full == 'unknown':
            raise ValueError('successful plan requires explicit selection')
        if full == 'false' and any(values[node] != 'skipped' for node in CATEGORIES):
            raise ValueError('docs-only categories must be skipped')
    elif full != 'unknown':
        raise ValueError('failed plan has no authoritative selection')
    if values['verify'] == 'success':
        expected = dict.fromkeys(NODES, 'success')
        if full == 'false':
            expected.update(dict.fromkeys(CATEGORIES, 'skipped'))
        if values != expected:
            raise ValueError('successful verify contradicts job evidence')
    return values


def positive_integer(value):
    if not isinstance(value, str) or re.fullmatch(r'[1-9][0-9]*', value) is None:
        raise ValueError('invalid positive workflow identity')
    return int(value)


def provenance(digest):
    environment = {key: os.environ[key] for key in (
        'GITHUB_REPOSITORY', 'GITHUB_EVENT_NAME', 'GITHUB_EVENT_PATH',
        'GITHUB_RUN_ID', 'GITHUB_RUN_ATTEMPT', 'GITHUB_WORKSPACE')}
    if environment['GITHUB_EVENT_NAME'] != 'pull_request':
        raise ValueError('only pull-request reports are supported')
    repository = environment['GITHUB_REPOSITORY']
    if re.fullmatch(r'[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+', repository) is None:
        raise ValueError('invalid repository')
    event = json.loads(Path(environment['GITHUB_EVENT_PATH']).read_text(), object_pairs_hook=strict_object)
    if event['repository']['full_name'] != repository:
        raise ValueError('event repository mismatch')
    pull = event['pull_request']
    if type(pull['number']) is not int or pull['number'] < 1:
        raise ValueError('invalid pull request identity')
    head = pull['head']['sha']
    if not isinstance(head, str) or re.fullmatch(r'[0-9a-f]{40}', head) is None:
        raise ValueError('invalid PR head')
    if re.fullmatch(r'[0-9a-f]{64}', digest) is None:
        raise ValueError('invalid graph digest')
    workspace = Path(environment['GITHUB_WORKSPACE']).resolve(strict=True)
    if not workspace.is_dir():
        raise ValueError('workspace is not a directory')
    return workspace, {
        'repository': repository, 'pullRequest': pull['number'], 'headSha': head,
        'workflowRunId': positive_integer(environment['GITHUB_RUN_ID']),
        'runAttempt': positive_integer(environment['GITHUB_RUN_ATTEMPT']), 'graphDigest': digest,
    }


def reports(values, full, identity):
    result = {}
    for node in NODES:
        outcome = values[node]
        summary = f'GitHub job {node}: {outcome}.'
        if outcome == 'skipped':
            summary = 'Not executed: docs-only policy.' if full == 'false' else 'Not executed: dependency or workflow interruption.'
        value = {
            'schemaVersion': 0, 'nodeId': node, 'title': TITLES[node],
            'status': STATUSES[outcome], 'summary': summary, 'provenance': identity,
            **{key: [] for key in ('metrics', 'findings', 'annotations', 'diagnostics', 'controls', 'notes')},
        }
        if outcome in ('failure', 'cancelled'):
            value['failureKind'] = 'command' if outcome == 'failure' else 'cancellation'
        result[node + '.json'] = (json.dumps(value, sort_keys=True, separators=(',', ':')) + '\n').encode()
    return result


def publish(workspace, relative, contents):
    path = Path(relative)
    if path.is_absolute() or not path.parts or any(part in ('.', '..') for part in path.parts):
        raise ValueError('output must be a workspace-relative directory')
    target = workspace
    for part in path.parts:
        target /= part
        if target.is_symlink():
            raise ValueError('output symlinks are forbidden')
    if not target.resolve().is_relative_to(workspace):
        raise ValueError('output escapes workspace')
    if target.exists():
        if not target.is_dir() or any(item.is_symlink() or not item.is_file() for item in target.iterdir()):
            raise ValueError('unsafe existing output')
        if {item.name: item.read_bytes() for item in target.iterdir()} != contents:
            raise ValueError('existing output conflicts with this attempt')
        return
    target.parent.mkdir(parents=True, exist_ok=True)
    temporary = Path(tempfile.mkdtemp(prefix='.qg-report-', dir=target.parent))
    try:
        for name, data in contents.items():
            (temporary / name).write_bytes(data)
        # Directory rename publishes all reports together. A raced non-empty
        # destination cannot be overwritten and is reported as a conflict.
        temporary.rename(target)
    finally:
        if temporary.exists():
            shutil.rmtree(temporary)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--full', required=True, choices=('true', 'false', 'unknown'))
    parser.add_argument('--results-json', required=True)
    parser.add_argument('--graph-digest', required=True)
    parser.add_argument('--output-dir', required=True)
    args = parser.parse_args()
    category = 'input'
    try:
        values = outcomes(args.results_json, args.full)
        category = 'provenance'
        workspace, identity = provenance(args.graph_digest)
        contents = reports(values, args.full, identity)
        category = 'output'
        publish(workspace, args.output_dir, contents)
    except (KeyError, OSError, ValueError, TypeError) as error:
        detail = str(error).replace('\n', ' ')[:160]
        print(f'QUALITY_GRAPH_REPORT_FAILURE category={category} detail={detail}', file=sys.stderr)
        return 1
    print('QUALITY_GRAPH_REPORT_OK nodes=7')
    return 0


if __name__ == '__main__':
    sys.exit(main())
