"""Negative CLI storage cases for PILOT-HEALTHCHECK-SESSION-001."""
import os
import hashlib
import subprocess
from pathlib import Path


def check_storage_rejections(env, root, jar, lock):
    script = Path(__file__).resolve().parents[2]/'rapid-pilot/healthcheck.sh'
    def snapshot():
        result = {}
        for path in [root, *root.rglob('*')]:
            if path.name == 'server.log':
                continue
            info = path.lstat()
            content = os.readlink(path) if path.is_symlink() else (
                hashlib.sha256(path.read_bytes()).hexdigest() if path.is_file() else '')
            result[str(path.relative_to(root))] = (info.st_uid, info.st_mode, content)
        return result
    def rejected(candidate, label):
        before = snapshot()
        result = subprocess.run(['sh', str(script)], env=candidate, capture_output=True, timeout=7)
        assert (result.returncode, result.stdout, result.stderr) == (1, b'', b''), label
        assert snapshot() == before, 'rejection preserves artifacts: '+label
        print('PASS', label)
    rejected(dict(env, FMONITOR_SESSION_STATE_ROOT=str(root/'missing')), 'missing root')
    rejected(dict(env, FMONITOR_SESSION_STATE_ROOT=str(root/'mode')), 'file root')
    root_link = root/'root-link'
    root_link.symlink_to(root, target_is_directory=True)
    rejected(dict(env, FMONITOR_SESSION_STATE_ROOT=str(root_link)), 'symlink root')
    for path, good_mode in [(jar.parent, 0o700), (lock, 0o600)]:
        path.chmod(0o755 if path.is_dir() else 0o644)
        rejected(env, 'invalid permissions '+path.name)
        path.chmod(good_mode)
        saved = path.with_name(path.name+'-saved')
        path.rename(saved)
        path.symlink_to(saved, target_is_directory=saved.is_dir())
        rejected(env, 'symlink '+path.name)
        path.unlink()
        saved.rename(path)
