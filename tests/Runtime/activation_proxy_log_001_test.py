#!/usr/bin/env python3
"""ACTIVATION-PROXY-LOG-001: real nginx syntax, failed upstream and private logs."""
import hashlib
import http.client
import json
import os
from pathlib import Path
import re
import subprocess
import tempfile
import time
import uuid

ROOT = Path(__file__).resolve().parents[2]
FAILURES = []


def check(condition, message):
    if not condition:
        FAILURES.append(message)


def run(arguments, **options):
    return subprocess.run(arguments, cwd=ROOT, capture_output=True, text=True,
                          timeout=options.pop('timeout', 30), **options)


def require(result, label):
    if result.returncode:
        raise RuntimeError('SETUP_FAILURE: ' + label + ': ' + result.stderr[-1200:])
    return result.stdout.strip()


def image():
    supplied = os.environ.get('FMONITOR_TEST_NGINX_IMAGE')
    if supplied:
        return require(run(['docker', 'image', 'inspect', supplied, '--format', '{{.Id}}']), 'explicit nginx image')
    dockerfile = ROOT / 'deploy/yii2/Dockerfile'
    tag = 'fmonitor2-nginx-test-base:' + hashlib.sha256(dockerfile.read_bytes()).hexdigest()[:16]
    existing = run(['docker', 'image', 'inspect', tag, '--format', '{{.Id}}'])
    if existing.returncode:
        environment = dict(os.environ, DOCKER_BUILDKIT='1')
        require(run(['docker', 'build', '--target', 'runtime-base', '--tag', tag,
                     '--file', str(dockerfile), str(ROOT)], env=environment, timeout=600), 'runtime-base build')
    return require(run(['docker', 'image', 'inspect', tag, '--format', '{{.Id}}']), 'built nginx image')


def location(text, expression):
    match = re.search(r'location\s+' + expression + r'\s*\{([^{}]*)\}', text, re.S)
    return match.group(1) if match else None


def handoff(body):
    if body is None:
        return None
    body = re.sub(r'#.*', '', body)
    return sorted(' '.join(value.split()) for value in re.findall(r'\b(?:include\s+[^;]*fastcgi_params|fastcgi_(?:param|pass|read_timeout|pass_request_body|pass_request_headers)\s+[^;]+)\s*;', body))


def request(port, method, path, headers=None, body=None):
    deadline = time.monotonic() + 8
    while True:
        connection = http.client.HTTPConnection('127.0.0.1', port, timeout=2)
        try:
            connection.request(method, path, body=body, headers=headers or {})
            response = connection.getresponse()
            data = response.read()
            return response.status, data
        except OSError:
            if time.monotonic() >= deadline:
                raise
            time.sleep(.05)
        finally:
            connection.close()


def inspect_family(image_id, family, evidence):
    container = None
    config = ROOT / 'deploy' / family / 'nginx.conf'
    text = config.read_text()
    normal = location(text, r'/')
    activation = location(text, r'=\s+/pilot/activate')
    check(activation is not None, family + ': exact activation boundary missing')
    check(handoff(normal) == handoff(activation), family + ': FastCGI handoff/Host/body/timeout parity')
    expected = '/workspace/fmonitor-2/public/' + ('yii.php' if family == 'yii2' else 'runtime.php')
    check(normal is not None and ('SCRIPT_FILENAME ' + expected) in normal,
          family + ': existing entrypoint preserved')
    mount = f'type=bind,src={config},dst=/tmp/candidate-nginx.conf,readonly'
    common = ['--pull=never', '--add-host', 'php:127.0.0.1', '--mount', mount,
              '--entrypoint', 'nginx']
    require(run(['docker', 'run', '--rm', *common, image_id, '-t', '-c', '/tmp/candidate-nginx.conf']), family + ' nginx -t')
    invalid_config = evidence / (family + '-invalid.conf')
    invalid_config.write_text('fixture_invalid_directive on;\n' + text)
    invalid_mount = f'type=bind,src={invalid_config},dst=/tmp/invalid-nginx.conf,readonly'
    invalid = run(['docker', 'run', '--rm', '--pull=never', '--add-host', 'php:127.0.0.1',
                   '--mount', invalid_mount, '--entrypoint', 'nginx', image_id,
                   '-t', '-c', '/tmp/invalid-nginx.conf'])
    check(invalid.returncode != 0 and 'fixture_invalid_directive' in invalid.stderr,
          family + ': invalid configuration remains diagnosable')
    tokens = {letter: 'ACTIVATE_' + letter + '_' + uuid.uuid4().hex for letter in 'ABCD'}
    try:
        container = require(run(['docker', 'run', '--detach', '--name', 'fm2-log-test-' + uuid.uuid4().hex[:12],
                                 '--publish', '127.0.0.1::8080', *common, image_id,
                                 '-c', '/tmp/candidate-nginx.conf', '-g', 'daemon off;']), family + ' nginx start')
        port = int(require(run(['docker', 'port', container, '8080/tcp']), 'published port').rsplit(':', 1)[1])
        headers = {'Referer': 'https://example.invalid/?token=' + tokens['B'], 'User-Agent': tokens['C']}
        for method in ['GET', 'POST']:
            body = 'token=' + tokens['D'] + '&password=' + tokens['D'] if method == 'POST' else None
            sent_headers = dict(headers)
            if body:
                sent_headers['Content-Type'] = 'application/x-www-form-urlencoded'
            status, response_body = request(port, method, '/pilot/activate?token=' + tokens['A'], sent_headers, body)
            check(status == 502, family + ': activation upstream failure stays 502')
            check(all(token.encode() not in response_body for token in tokens.values()), family + ': failure body safe')
        status, _ = request(port, 'GET', '/health/live')
        check(status == 502, family + ': ordinary upstream failure stays 502')
        deadline = time.monotonic() + 3
        while True:
            logs = run(['docker', 'logs', container])
            require(logs, 'nginx logs')
            if '/health/live' in logs.stdout or time.monotonic() >= deadline:
                break
            time.sleep(.05)
        (evidence / (family + '-stdout.log')).write_text(logs.stdout)
        (evidence / (family + '-stderr.log')).write_text(logs.stderr)
        for stream, contents in [('access', logs.stdout), ('error', logs.stderr)]:
            for letter, token in tokens.items():
                check(token not in contents, family + ': INTENDED_RED token ' + letter + ' leaked in ' + stream)
        check('/pilot/activate' not in logs.stderr, family + ': activation request-associated error suppressed')
        check('/health/live' in logs.stderr and 'upstream' in logs.stderr, family + ': ordinary upstream diagnostics retained')
        records = []
        for line in logs.stdout.splitlines():
            try:
                record = json.loads(line)
            except json.JSONDecodeError:
                continue
            if isinstance(record, dict):
                records.append(record)
        check(len(records) == 3, family + ': one structured access record per request')
        expected_records = [('GET', '/pilot/activate'), ('POST', '/pilot/activate'), ('GET', '/health/live')]
        ids = []
        for record, expected_route in zip(records, expected_records):
            check((record.get('method'), record.get('uri')) == expected_route, family + ': exact method/path without args')
            check(record.get('status') == 502, family + ': diagnostic status')
            request_id = record.get('request_id', '')
            check(isinstance(request_id, str) and re.fullmatch('[0-9a-f]{32}', request_id) is not None, family + ': generated request ID')
            ids.append(request_id)
            check(str(record.get('upstream_status')) == '502', family + ': upstream outcome')
            for key in ['request_time', 'upstream_response_time']:
                value = record.get(key)
                try:
                    valid = value == '-' or float(value) >= 0
                except (TypeError, ValueError):
                    valid = False
                check(valid, family + ': operational timing ' + key)
            check(not any(key in record for key in ['request', 'request_uri', 'args', 'query_string', 'referer', 'user_agent', 'cookie', 'authorization', 'body']), family + ': private request fields omitted')
        check(len(set(ids)) == 3, family + ': independently generated request IDs')
    finally:
        if container:
            require(run(['docker', 'rm', '--force', container]), 'own container cleanup')


def main():
    evidence = Path(tempfile.mkdtemp(prefix='fmonitor-activation-proxy-'))
    image_id = image()
    for family in ['yii2', 'runtime']:
        inspect_family(image_id, family, evidence)
    (evidence / 'summary.json').write_text(json.dumps({'image': image_id, 'failures': FAILURES}, indent=2))
    if FAILURES:
        for failure in FAILURES:
            print('REGRESSION_FAILURE: ' + failure)
        print('Evidence: ' + str(evidence))
        return 1
    print('PASS: ACTIVATION-PROXY-LOG-001 both real nginx configurations; evidence ' + str(evidence))
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
