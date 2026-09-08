"""PILOT-HEALTHCHECK-SESSION-001 native cookie-write failure, no permission mutation."""
import os
from pathlib import Path
import resource
import signal
import socketserver
import subprocess
import tempfile
import threading

REPO = Path(__file__).resolve().parents[2]
COOKIE = 'b'*64

class TinyCookieResponse(socketserver.BaseRequestHandler):
    def handle(self):
        self.request.settimeout(2)
        request = b''
        while b'\r\n\r\n' not in request:
            piece = self.request.recv(4096)
            if not piece:
                return
            request += piece
        response = (f'HTTP/1.1 200 OK\r\nSet-Cookie: fm2auth_{self.server.server_address[1]}={COOKIE}; Path=/pilot\r\n'
                    'Content-Length: 0\r\nConnection: close\r\n\r\n').encode()
        assert len(response) < 200, 'fixture headers fit native file limit'
        self.request.sendall(response)

class FixtureServer(socketserver.ThreadingTCPServer):
    daemon_threads = True


def limited_files():
    signal.signal(signal.SIGXFSZ, signal.SIG_IGN)
    resource.setrlimit(resource.RLIMIT_FSIZE, (200, 200))

with FixtureServer(('127.0.0.1', 0), TinyCookieResponse) as server:
    threading.Thread(target=server.serve_forever, daemon=True).start()
    try:
        with tempfile.TemporaryDirectory(prefix='fm2-health-cookie-failure-') as task:
            root = Path(task)
            env = dict(os.environ, FMONITOR_DEMO_PORT=str(server.server_address[1]),
                       FMONITOR_SESSION_STATE_ROOT=task)
            command = ['sh', str(REPO/'rapid-pilot/healthcheck.sh')]
            healthy = subprocess.run(command, env=env, capture_output=True, timeout=7)
            assert (healthy.returncode, healthy.stdout, healthy.stderr) == (0, b'', b''), 'healthy fixture'
            jar = root/'healthcheck/cookies.txt'
            assert len(jar.read_bytes()) > 200 and COOKIE in jar.read_text(), 'cookie file exceeds native resource bound'
            print('PASS healthy public CLI; headers fit but complete cookie exceeds200 bytes', flush=True)
            failed = subprocess.run(command, env=env, capture_output=True, timeout=7, preexec_fn=limited_files)
            assert (failed.returncode, failed.stdout, failed.stderr) == (1, b'', b''), (
                'INTENDED_RED cookie persistence failure must fail health', failed.returncode,
                failed.stdout, failed.stderr)
            print('PASS native cookie write failure returns1 without output')
    finally:
        server.shutdown()
print('PILOT_HEALTHCHECK_COOKIE_FAILURE_OK')
