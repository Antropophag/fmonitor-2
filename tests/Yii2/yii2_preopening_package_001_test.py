#!/usr/bin/env python3
"""YII2-PREOPENING-JOURNEY-001: real production image renders without rapid assets."""
import hashlib
import json
import os
from pathlib import Path
import re
import subprocess
import tempfile
import uuid
import zlib

ROOT = Path(__file__).resolve().parents[2]
EXPECTED = 'd7e12a6e13983cca2ee3abe7f867338f2079c5413b9b80cc6e9cae0fac9cf1d5'
asset = ROOT / 'app/InstallationProcess/assets/shlz-logo.jpg.base64'
assert asset.is_file(), 'INTENDED_RED renderer logo must be owned by application package'
assert hashlib.sha256(asset.read_bytes()).hexdigest() == EXPECTED, 'logo bytes preserved'
tag = 'fmonitor-preopening-test:' + uuid.uuid4().hex
payload = {
    'assignmentOrderVersion': 7, 'assignmentOrderDate': '2026-09-01',
    'organizationType': 'individual',
    'installationObjectSnapshot': {'address': 'Москва, Проверочная 10', 'entrance': '2',
        'objectRegistrationNumber': 'TEST-4512', 'plannedStartDate': '2026-10-01',
        'plannedFinishDate': '2026-12-01'},
    'installers': [{'tabId': 7001, 'fullName': 'Монтажник 7001', 'position': 'Монтажник'}],
    'controlEngineer': {'userId': 73, 'fullName': 'Инженер теста', 'position': 'Инженер'},
}
program = r'''
require 'vendor/autoload.php';
require 'app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
if(is_dir('rapid-pilot'))throw new RuntimeException('Legacy runtime packaged');
$asset='app/InstallationProcess/assets/shlz-logo.jpg.base64';
if(hash_file('sha256',$asset)!==getenv('EXPECTED_LOGO_SHA'))throw new RuntimeException('Logo mismatch');
$input=json_decode(stream_get_contents(STDIN),true,flags:JSON_THROW_ON_ERROR);
$result=(new FMonitor2\InstallationProcess\ProductionPdfAssignmentOrderRenderer())->renderAssignmentOrder($input);
foreach(get_included_files()as$file)if(str_contains($file,'/rapid-pilot/')||str_contains($file,'/app/PilotHttp/'))throw new RuntimeException('Legacy dependency');
if(count($result)!==1||$result[0]['mediaType']!=='application/pdf')throw new RuntimeException('PDF envelope');
echo $result[0]['bytes'];
'''
with tempfile.TemporaryDirectory(prefix='preopening-image-') as directory:
    log = Path(directory) / 'build.log'
    try:
        with log.open('wb') as output:
            build = subprocess.run(['docker', 'build', '-f', 'deploy/yii2/Dockerfile', '-t', tag, '.'],
                                   cwd=ROOT, stdout=output, stderr=subprocess.STDOUT, timeout=900)
        assert build.returncode == 0, log.read_text()[-12000:]
        rendered = subprocess.run(['docker', 'run', '--rm', '-i', '--network=none',
            '-e', 'EXPECTED_LOGO_SHA=' + EXPECTED, tag, 'php', '-r', program],
            input=json.dumps(payload, ensure_ascii=False).encode(), stdout=subprocess.PIPE,
            stderr=subprocess.PIPE, timeout=60)
        assert rendered.returncode == 0, rendered.stderr.decode(errors='replace')
        pdf = rendered.stdout
        assert pdf.startswith(b'%PDF-') and b'%%EOF' in pdf[-80:], 'complete actual PDF'
        streams = []
        for stream in re.findall(rb'stream\r?\n(.*?)\r?\nendstream', pdf, re.S):
            try:
                streams.append(zlib.decompress(stream))
            except zlib.error:
                streams.append(stream)
        for marker in ['Монтажник 7001', 'Инженер теста', 'TEST-4512']:
            assert any(marker.encode('utf-16-be') in stream for stream in streams), marker
        print('PASS: YII2-PREOPENING-JOURNEY-001 packaged PDF, exact owned logo, native runtime dependencies')
    finally:
        subprocess.run(['docker', 'image', 'rm', tag], stdout=subprocess.DEVNULL,
                       stderr=subprocess.DEVNULL, timeout=30)
