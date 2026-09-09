"""REVIEW-SOURCE-001: exercise capture/restore through the real CLI and Git."""
import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

TOOL = Path(__file__).resolve().parents[2] / 'tools/delivery/review-source.py'


class ReviewSource(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix='review source ')
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.repo = self.root / 'source repo'
        self.repo.mkdir()
        self.git('init', '-q')
        self.git('config', 'user.email', 'fixture@example.invalid')
        self.git('config', 'user.name', 'Fixture')
        for name, value in {'.gitignore': 'private/\n', 'text.txt': 'base\n',
                            'deleted.txt': 'remove me\n', 'unchanged.txt': 'stable\n',
                            'run.sh': '#!/bin/sh\nexit 0\n'}.items():
            (self.repo / name).write_text(value)
        self.git('add', '.')
        self.git('commit', '-qm', 'fixture base')
        self.base = self.git('rev-parse', 'HEAD').strip()
        self.snapshot = self.root / 'saved snapshot'
        self.destination = self.root / 'restored tree'

    def git(self, *args):
        result = subprocess.run(['git', '-C', str(self.repo), *args],
                                capture_output=True, text=True, timeout=15)
        self.assertEqual(0, result.returncode, result.stderr)
        return result.stdout

    def cli(self, *args, ok=True):
        self.assertTrue(TOOL.is_file(), 'INTENDED_RED: REVIEW-SOURCE-001 capture/restore public seam is absent')
        result = subprocess.run([sys.executable, str(TOOL), *map(str, args)],
                                capture_output=True, text=True, timeout=20)
        if ok:
            self.assertEqual(0, result.returncode, result.stdout + result.stderr)
        else:
            self.assertNotEqual(0, result.returncode, result.stdout)
            self.assertTrue(result.stderr.strip(), 'rejection explains failure')
        return result

    def capture(self, **kw):
        return self.cli('capture', '--repo', self.repo, '--output', self.snapshot, **kw)

    def restore(self, **kw):
        return self.cli('restore', '--snapshot', self.snapshot, '--output', self.destination, **kw)

    def source_state(self):
        index = Path(self.git('rev-parse', '--git-path', 'index').strip())
        if not index.is_absolute():
            index = self.repo / index
        return (self.git('rev-parse', 'HEAD'), self.git('status', '--porcelain=v1', '-uall'),
                index.read_bytes(), self.git('diff', '--binary'), self.git('diff', '--cached', '--binary'))

    def test_roundtrip_all_changes_and_source_unchanged(self):
        (self.repo / 'text.txt').write_text('staged\n')
        self.git('add', 'text.txt')
        (self.repo / 'text.txt').write_text('final unstaged\n')
        (self.repo / 'deleted.txt').unlink()
        (self.repo / 'new file.txt').write_text('new literal\n')
        (self.repo / 'binary.bin').write_bytes(b'\x00\xff\x01binary\n')
        (self.repo / 'run.sh').chmod(0o755)
        (self.repo / 'link').symlink_to('text.txt')
        (self.repo / 'private').mkdir()
        (self.repo / 'private/secret').write_text('ignored fixture\n')
        before = self.source_state()
        result = json.loads(self.capture().stdout)
        manifest = json.loads((self.snapshot / 'manifest.json').read_text())
        digest = hashlib.sha256((self.snapshot / 'source.patch').read_bytes()).hexdigest()
        self.assertEqual(1, manifest['version'])
        self.assertEqual(self.base, manifest['base_commit'])
        self.assertEqual(self.repo.resolve(), Path(manifest['repository']).resolve())
        self.assertEqual(digest, manifest['patch_sha256'])
        self.assertEqual(digest, result['patch_sha256'])
        self.assertEqual(self.snapshot.resolve(), Path(result['snapshot']).resolve())
        self.restore()
        self.assertEqual('final unstaged\n', (self.destination / 'text.txt').read_text())
        self.assertEqual('new literal\n', (self.destination / 'new file.txt').read_text())
        self.assertEqual(b'\x00\xff\x01binary\n', (self.destination / 'binary.bin').read_bytes())
        self.assertFalse((self.destination / 'deleted.txt').exists())
        self.assertTrue((self.destination / 'run.sh').stat().st_mode & 0o111)
        self.assertEqual('text.txt', os.readlink(self.destination / 'link'))
        self.assertEqual('stable\n', (self.destination / 'unchanged.txt').read_text())
        self.assertFalse((self.destination / 'private').exists())
        self.assertEqual(before, self.source_state())
        (self.repo / 'text.txt').write_text('later edit\n')
        self.cli('restore', '--snapshot', self.snapshot, '--output', self.root / 'second restore')
        self.assertEqual('final unstaged\n', (self.root / 'second restore/text.txt').read_text())

    def test_clean_snapshot_and_repeat_refuse_existing_outputs(self):
        self.capture()
        saved = (self.snapshot / 'source.patch').read_bytes()
        self.capture(ok=False)
        self.assertEqual(saved, (self.snapshot / 'source.patch').read_bytes())
        self.restore()
        (self.destination / 'owner').write_text('keep')
        self.restore(ok=False)
        self.assertEqual('keep', (self.destination / 'owner').read_text())
        self.assertEqual('base\n', (self.destination / 'text.txt').read_text())

    def test_capture_rejects_inside_repo_without_changes(self):
        before = self.source_state()
        target = self.repo / 'forbidden snapshot'
        self.cli('capture', '--repo', self.repo, '--output', target, ok=False)
        self.assertFalse(target.exists())
        self.assertEqual(before, self.source_state())

    def test_capture_rejects_non_git_and_existing_owner_directory(self):
        self.cli('capture', '--repo', self.root, '--output', self.snapshot, ok=False)
        self.assertFalse(self.snapshot.exists())
        self.snapshot.mkdir()
        (self.snapshot / 'owner').write_bytes(b'untouched')
        self.capture(ok=False)
        self.assertEqual([self.snapshot / 'owner'], list(self.snapshot.iterdir()))
        self.assertEqual(b'untouched', (self.snapshot / 'owner').read_bytes())

    def test_integrity_rejections_before_destination_creation(self):
        (self.repo / 'text.txt').write_text('changed\n')
        self.capture()
        manifest_path = self.snapshot / 'manifest.json'
        original_manifest = manifest_path.read_text()
        patch_path = self.snapshot / 'source.patch'
        original_patch = patch_path.read_bytes()
        for mutation in ('patch', 'version', 'digest', 'base', 'repository'):
            with self.subTest(mutation=mutation):
                manifest_path.write_text(original_manifest)
                patch_path.write_bytes(original_patch)
                manifest = json.loads(original_manifest)
                if mutation == 'patch':
                    patch_path.write_bytes(original_patch + b'corruption')
                else:
                    key, value = {'version': ('version', 99), 'digest': ('patch_sha256', '0'*64),
                                  'base': ('base_commit', 'f'*40),
                                  'repository': ('repository', str(self.root/'absent'))}[mutation]
                    manifest[key] = value
                    manifest_path.write_text(json.dumps(manifest))
                self.restore(ok=False)
                self.assertFalse(self.destination.exists())

    def test_apply_failure_cleans_only_created_worktree(self):
        self.capture()
        before = self.git('worktree', 'list', '--porcelain')
        patch = b'diff --git a/absent b/absent\n--- a/absent\n+++ b/absent\n@@ -1 +1 @@\n-no such source\n+replacement\n'
        (self.snapshot / 'source.patch').write_bytes(patch)
        manifest_path = self.snapshot / 'manifest.json'
        manifest = json.loads(manifest_path.read_text())
        manifest['patch_sha256'] = hashlib.sha256(patch).hexdigest()
        manifest_path.write_text(json.dumps(manifest))
        self.restore(ok=False)
        self.assertFalse(self.destination.exists())
        self.assertEqual(before, self.git('worktree', 'list', '--porcelain'))
        self.assertTrue(self.repo.exists())
        self.assertTrue(self.snapshot.exists())


if __name__ == '__main__':
    unittest.main(verbosity=2)
