"""PILOT-HEALTHCHECK-SESSION-001: public CLI against native anonymous HTTP lifecycle."""
import fcntl
import hashlib
import http.server
import threading
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time

REPO = Path(__file__).resolve().parents[2]
import sys
sys.path.insert(0, str(REPO/'tests/Support'))
from pilot_healthcheck_storage_cases import check_storage_rejections
PHP = shutil.which('php')

def run(env):
    return subprocess.run(['sh', str(REPO/'rapid-pilot/healthcheck.sh')],
                          env=env, capture_output=True, timeout=7)

def assert_exit(env, expected, label):
    result = run(env)
    assert result.returncode == expected, (label, result.returncode, result.stderr.decode())
    assert result.stdout == b'' and result.stderr == b'', (label, 'private output')
    print('PASS', label)

def inventory(root):
    return {p.name: hashlib.sha256(p.read_bytes()).hexdigest()
            for p in (root/'sessions/pilot').iterdir() if p.is_file()}

with tempfile.TemporaryDirectory(prefix='fm2-health-') as task:
    root = Path(task)
    mode = root/'mode'
    mode.write_text('')
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    hits = []
    class Trap(http.server.BaseHTTPRequestHandler):
        def do_GET(self):
            hits.append(self.path)
            self.send_response(503)
            self.end_headers()
        def log_message(self, *args):
            pass
    trap = http.server.HTTPServer(('127.0.0.1', 0), Trap)
    threading.Thread(target=trap.serve_forever, daemon=True).start()
    env = dict(os.environ, FMONITOR_DEMO_PORT=str(port), FMONITOR_SESSION_STATE_ROOT=task,
               FMONITOR_SESSION_INSTANCE='pilot', FMONITOR_TRUSTED_REQUEST_SCHEME='http',
               HEALTH_FIXTURE_MODE=str(mode), HEALTH_FIXTURE_TRAP=f'http://127.0.0.1:{trap.server_port}')
    with (root/'server.log').open('wb') as log:
        server = subprocess.Popen([PHP, '-S', f'127.0.0.1:{port}',
            str(REPO/'tests/Support/pilot_healthcheck_session_router.php')], env=env,
            stdout=log, stderr=log)
        try:
            deadline = time.monotonic()+3
            while True:
                try:
                    with socket.create_connection(('127.0.0.1', port), timeout=.1):
                        break
                except OSError:
                    assert time.monotonic() < deadline, 'SETUP_FAILURE server'
                    time.sleep(.02)
            # Establish healthy public fixture before intended RED.
            old = f"file_get_contents('http://127.0.0.1:{port}/pilot/objects');"
            subprocess.run([PHP, '-r', old], check=True, stdout=subprocess.DEVNULL, env=env)
            before = inventory(root)
            subprocess.run([PHP, '-r', old], check=True, stdout=subprocess.DEVNULL, env=env)
            assert len(inventory(root)) > len(before), 'characterization: predecessor grows'
            print('PASS healthy fixture; predecessor creates additional sessions', flush=True)
            assert_exit(env, 0, 'INTENDED_RED first bounded CLI succeeds')
            baseline = inventory(root)
            for _ in range(20):
                result = run(env)
                assert (result.returncode, result.stdout, result.stderr) == (0, b'', b'')
            assert inventory(root) == baseline, 'twenty probes preserve session bytes/count'
            print('PASS twenty probes preserve native session/lock inventory')
            jar = root/'healthcheck/cookies.txt'
            lock = root/'healthcheck/probe.lock'
            assert jar.stat().st_mode & 0o777 == 0o600
            assert lock.stat().st_mode & 0o777 == 0o600
            assert jar.parent.stat().st_mode & 0o777 == 0o700
            with lock.open('rb') as held:
                fcntl.flock(held, fcntl.LOCK_EX | fcntl.LOCK_NB)
                start = time.monotonic()
                assert_exit(env, 1, 'contended lock')
                assert time.monotonic()-start < 1.8
            for value in ['/pilot/objects', '/pilot/installers', 'non200', 'external', 'loop', 'chain4']:
                mode.write_text(value)
                assert_exit(env, 1, value)
            mode.write_text('chain3')
            assert_exit(env, 0, 'exactly three redirects accepted')
            assert hits == [], 'cross-origin redirect rejected before request'
            mode.write_text('')
            for value in ['0', '65536', '8092x']:
                assert_exit(dict(env, FMONITOR_DEMO_PORT=value), 1, 'bad port '+value)
            assert_exit(dict(env, FMONITOR_SESSION_STATE_ROOT='relative'), 1, 'relative root')
            check_storage_rejections(env, root, jar, lock)
            saved = jar.read_bytes()
            jar.chmod(0o644)
            assert_exit(env, 1, 'cookie permissions')
            jar.chmod(0o600)
            jar.unlink()
            target = root/'untouched-cookie'
            target.write_bytes(saved)
            jar.symlink_to(target)
            assert_exit(env, 1, 'cookie symlink')
            assert target.read_bytes() == saved
            jar.unlink()
            jar.write_bytes(saved)
            jar.chmod(0o600)
            # Valid-format stale cookie: application must recover without credentials.
            jar.write_text(f'# Netscape HTTP Cookie File\n127.0.0.1\tFALSE\t/pilot\tFALSE\t0\tfm2auth_{port}\t'+ 'a'*64+'\n')
            assert_exit(env, 0, 'stale cookie recovery')
            mode.write_text('slow')
            start = time.monotonic()
            assert_exit(env, 1, 'network deadline')
            assert time.monotonic()-start < 2.8
        finally:
            trap.shutdown()
            trap.server_close()
            server.terminate()
            try:
                server.wait(timeout=2)
            except subprocess.TimeoutExpired:
                server.kill()
                server.wait(timeout=2)
print('PILOT_HEALTHCHECK_SESSION_OK')
