"""DEV-SETUP-001: public setup CLI in an isolated temporary checkout."""
from pathlib import Path
import hashlib
import os
import shutil
import subprocess
import tempfile
import unittest
import sys
import zipfile
import pwd


ROOT = Path(__file__).resolve().parents[2]
PIN = "9aaedf50eabf5f92e4af1cbc9c0f2a26a171b35b"


class DevelopmentSetup(unittest.TestCase):
    def setUp(self):
        account_state = Path(pwd.getpwuid(os.geteuid()).pw_dir)
        self.temp = tempfile.TemporaryDirectory(prefix="fmonitor-dev-setup-", dir=account_state)
        self.addCleanup(self.temp.cleanup)
        self.parent = Path(self.temp.name)
        self.root = self.parent / "fmonitor-2"
        self.root.mkdir()
        for relative in ["composer.lock", "Makefile", "rapid-pilot/tcpdf-autoload.php",
                         "Dockerfile", "compose.yaml", "compose.test.yaml",
                         "tools/verification/Dockerfile.test"]:
            source = ROOT / relative
            if source.is_file():
                target = self.root / relative
                target.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(source, target)
        if (ROOT / "tools/delivery").is_dir():
            shutil.copytree(ROOT / "tools/delivery", self.root / "tools/delivery")
        self.bin = self.parent / "bin"
        self.bin.mkdir()
        self.trace = self.parent / "trace"
        self.bad_node = False
        self.git_head = PIN
        self.git_dirty = False
        self.npm_failure = False
        self.real_php = shutil.which("php")
        self._write_stubs()

    def _stub(self, name, body):
        path = self.bin / name
        path.write_text("#!/bin/sh\nset -eu\nprintf '%s\\t%s\\n' '" + name +
                        "' \"$*\" >> \"$TRACE\"\n" + body)
        path.chmod(0o700)

    def _write_stubs(self):
        self._stub("php", """
case "$*" in
  *posix_getpwuid*posix_geteuid*) exec "${REAL_PHP}" "$@" ;;
  *PHP_VERSION_ID*|*extension_loaded*) exit 0 ;;
  *getTCPDFVersion*) exit 0 ;;
  --version|-v) echo 'PHP 8.5.0'; exit 0 ;;
esac
echo 'PHP 8.5.0'
""")
        node_version = "v20.0.0" if self.bad_node else "v22.22.0"
        self._stub("node", f"echo '{node_version}'\n")
        self._stub("python3", """
case "$*" in
  --version|-V) echo 'Python 3.12.11' ;;
  *) exec "${REAL_PYTHON}" "$@" ;;
esac
""")
        self._stub("rg", "exit 0\n")
        self._stub("docker", "exit 0\n")
        self._stub("npm", """
case "$*" in
  --version|-v) echo '10.9.4'; exit 0 ;;
esac
case "${NPM_FAILURE:-0}:$*" in
  1:*build*) exit 71 ;;
esac
exit 0
""")
        self._stub("git", """
case "$*" in
  *tcpdf*' rev-parse HEAD') echo 'fbbaf14cfae8fe646f154f7c530d15ec25764040' ;;
  *' rev-parse HEAD') echo "${GIT_HEAD}" ;;
  *' status --porcelain'*) test "${GIT_DIRTY}" = 0 || echo ' M tracked.css' ;;
  *' diff --quiet'*) test "${GIT_DIRTY}" = 0 ;;
  *' diff-index --quiet'*) test "${GIT_DIRTY}" = 0 ;;
  clone*)
    eval "destination=\${$#}"
    mkdir -p "$destination/.git" "$destination/packages/styles/dist"
    : > "$destination/packages/styles/dist/shlz.css"
    ;;
esac
exit 0
""")

    def env(self):
        # System utilities remain available; every contract prerequisite is intercepted.
        return dict(os.environ, PATH=str(self.bin) + ":/usr/bin:/bin",
                    TRACE=str(self.trace), GIT_HEAD=self.git_head,
                    GIT_DIRTY="1" if self.git_dirty else "0",
                    NPM_FAILURE="1" if self.npm_failure else "0",
                    REAL_PYTHON=sys.executable, REAL_PHP=self.real_php or "",
                    PYTHONDONTWRITEBYTECODE="1")

    def script(self, *args):
        script = self.root / "tools/delivery/setup.sh"
        self.assertTrue(script.is_file(),
                        "INTENDED_RED DEV-SETUP-001 public setup script is missing")
        return subprocess.run(["/bin/bash", str(script), *args], cwd=self.root,
                              env=self.env(), capture_output=True, text=True, timeout=20)

    def calls(self):
        return self.trace.read_text().splitlines() if self.trace.exists() else []

    def mutation_calls(self):
        mutations = []
        for line in self.calls():
            if (line.startswith("git\tclone") or " checkout" in line or
                    " reset" in line or " clean" in line or
                    line.startswith("docker\tbuild")):
                mutations.append(line)
            if line.startswith("npm\t"):
                arguments = line.split("\t", 1)[1]
                words = arguments.split()
                if ("ci" in words or "install" in words or " run generate" in arguments or
                        " run build" in arguments):
                    mutations.append(line)
        return mutations

    @staticmethod
    def fingerprint(path):
        rows = []
        for entry in sorted(path.rglob("*")):
            relative = str(entry.relative_to(path))
            if entry.is_file():
                rows.append((relative, hashlib.sha256(entry.read_bytes()).hexdigest()))
            elif entry.is_dir():
                rows.append((relative + "/", "directory"))
        return rows

    def complete_existing_dependencies(self):
        shlz = self.parent / "shlz-ui"
        (shlz / ".git").mkdir(parents=True)
        (shlz / "packages/styles/dist").mkdir(parents=True)
        (shlz / "packages/styles/dist/shlz.css").write_text("generated export\n")
        (shlz / "packages/behaviors/dist").mkdir(parents=True)
        (shlz / "packages/behaviors/dist/browser.js").write_text("// fixture\n")
        (shlz / "packages/behaviors/dist/tabs.js").write_text("// fixture\n")
        (shlz / "packages/icons/dist").mkdir(parents=True)
        (shlz / "packages/icons/dist/sprite.svg").write_text("<svg/>\n")
        (shlz / "node_modules/playwright").mkdir(parents=True)
        (shlz / "node_modules/playwright/package.json").write_text('{"version":"fixture"}\n')
        (shlz / "node_modules/playwright/cli.js").write_text("// fixture\n")
        tcpdf = self.root / "vendor/tecnickcom/tcpdf"
        (tcpdf / ".git").mkdir(parents=True)
        (tcpdf / "tcpdf.php").write_text("<?php // fixture\n")
        (self.root / "vendor").mkdir(exist_ok=True)
        (self.root / "vendor/autoload.php").write_text("<?php // fixture\n")
        return shlz

    def test_manifest_has_runtime_pins_and_tcpdf_has_one_authority(self):
        manifest = self.root / "tools/delivery/dependencies.env"
        self.assertTrue(manifest.is_file(),
                        "INTENDED_RED DEV-SETUP-001 dependency manifest is missing")
        values = {}
        for line in manifest.read_text().splitlines():
            if line and not line.lstrip().startswith("#"):
                key, value = line.split("=", 1)
                values[key] = value.strip("'\"")
        required = {"PHP_VERSION": "8.5", "NODE_VERSION": "22.22.0",
                    "NPM_VERSION": "10.9.4",
                    "PYTHON_VERSION": "3.12.11", "SHLZ_UI_REVISION": PIN,
                    "PHP_EXTENSIONS": "mysqli,pcntl,dom,mbstring,curl,posix"}
        self.assertEqual(required, {key: values.get(key) for key in required})
        self.assertNotIn("TCPDF_VERSION", values)
        self.assertNotIn("TCPDF_REVISION", values)

    def test_incompatible_runtime_fails_before_dependency_or_build_mutation(self):
        self.bad_node = True
        self._write_stubs()
        result = self.script()
        self.assertNotEqual(0, result.returncode)
        self.assertIn("SETUP_FAILURE", result.stderr)
        self.assertRegex(result.stderr.lower(), r"node|22\.22\.0")
        self.assertEqual([], self.mutation_calls())
        self.assertFalse((self.parent / "shlz-ui").exists())
        self.assertFalse((self.root / "vendor").exists())

    def test_checkout_outside_os_account_home_fails_before_mutation(self):
        account_home = Path(pwd.getpwuid(os.geteuid()).pw_dir).resolve()
        self.assertIsNotNone(self.real_php, "SETUP_FAILURE test host lacks real PHP")
        self.complete_existing_dependencies()
        foreign_temp = tempfile.TemporaryDirectory(prefix="fmonitor-dev-setup-foreign-")
        self.addCleanup(foreign_temp.cleanup)
        foreign_parent = Path(foreign_temp.name)
        checkout = foreign_parent / "fmonitor-2"
        shutil.copytree(self.root, checkout)
        shlz = foreign_parent / "shlz-ui"
        shutil.copytree(self.parent / "shlz-ui", shlz)
        if checkout == account_home or account_home in checkout.parents:
            self.skipTest("fixture checkout unexpectedly lies inside OS account home")
        before = self.fingerprint(shlz)
        environment = self.env()
        script = checkout / "tools/delivery/setup.sh"
        self.assertTrue(script.is_file(), "DEV-SETUP-001 public setup script is missing")
        result = subprocess.run(["/bin/bash", str(script)], cwd=checkout, env=environment,
                                capture_output=True, text=True, timeout=20)
        self.assertNotEqual(0, result.returncode)
        self.assertIn("SETUP_FAILURE", result.stderr)
        self.assertRegex(result.stderr.lower(), r"home|checkout|path")
        self.assertEqual(before, self.fingerprint(shlz))
        self.assertEqual([], self.mutation_calls())

    def test_malformed_manifest_is_rejected_without_command_substitution_or_writes(self):
        sentinel = self.parent / "manifest-injection-sentinel"
        manifest = self.root / "tools/delivery/dependencies.env"
        self.assertTrue(manifest.is_file(), "DEV-SETUP-001 manifest fixture is missing")
        with manifest.open("a") as stream:
            stream.write(f"EXTRA=$(touch {sentinel})\n")
        before = self.fingerprint(self.root)
        result = self.script("--check")
        self.assertNotEqual(0, result.returncode)
        self.assertIn("SETUP_FAILURE", result.stderr)
        self.assertFalse(sentinel.exists(), "manifest command substitution executed")
        self.assertEqual(before, self.fingerprint(self.root), "--check wrote inside checkout")
        self.assertEqual([], self.mutation_calls())

    def test_check_rejects_mismatched_existing_tree_without_repair(self):
        shlz = self.complete_existing_dependencies()
        self.git_head = "0" * 40
        sentinel = shlz / "notes.local"
        sentinel.write_text("owner bytes\n")
        before = self.fingerprint(shlz)
        result = self.script("--check")
        self.assertNotEqual(0, result.returncode)
        self.assertIn("SETUP_FAILURE", result.stderr)
        self.assertIn("shlz-ui", result.stderr.lower())
        self.assertEqual(before, self.fingerprint(shlz))
        self.assertEqual([], self.mutation_calls())

    def test_successful_repeat_reuses_dependencies_and_preserves_user_file(self):
        shlz = self.complete_existing_dependencies()
        sentinel = shlz / "notes.local"
        sentinel.write_bytes(b"private owner data\x00\n")
        before = self.fingerprint(shlz)
        first = self.script()
        make = shutil.which("make")
        self.assertIsNotNone(make, "SETUP_FAILURE test host lacks make")
        second = subprocess.run([make, "--no-print-directory", "setup"], cwd=self.root,
                                env=self.env(), capture_output=True, text=True, timeout=20)
        self.assertEqual(0, first.returncode, first.stdout + first.stderr)
        self.assertEqual(0, second.returncode, second.stdout + second.stderr)
        self.assertEqual(before, self.fingerprint(shlz))
        self.assertEqual([], [line for line in self.mutation_calls()
                              if "docker\tbuild" not in line])

    def test_failed_fresh_shlz_build_never_publishes_destination(self):
        # Keep TCPDF valid so the failure is isolated to fresh shlz-ui publication.
        self.complete_existing_dependencies()
        shutil.rmtree(self.parent / "shlz-ui")
        self.npm_failure = True
        result = self.script()
        self.assertNotEqual(0, result.returncode)
        self.assertIn("SETUP_FAILURE", result.stderr)
        self.assertTrue(any(line.startswith("npm\t") and "run build" in line
                            for line in self.calls()), self.calls())
        self.assertFalse((self.parent / "shlz-ui").exists())

    def test_zip_adapter_lists_and_extracts_unicode_entry_without_mutation(self):
        adapter = self.root / "tools/delivery/zip-tools/unzip"
        self.assertTrue(adapter.is_file(),
                        "INTENDED_RED DEV-SETUP-001 portable unzip adapter is missing")
        archive = self.parent / "unicode.zip"
        entry = "icons/лифт-№1.svg"
        payload = b"<svg>\x00fixed bytes</svg>\n"
        with zipfile.ZipFile(archive, "w") as bundle:
            bundle.writestr(entry, payload)
        before = hashlib.sha256(archive.read_bytes()).hexdigest()
        listed = subprocess.run([str(adapter), "-Z1", str(archive)], env=self.env(),
                                capture_output=True, timeout=10)
        self.assertEqual(0, listed.returncode, listed.stderr.decode(errors="replace"))
        self.assertEqual((entry + "\n").encode(), listed.stdout)
        extracted = subprocess.run([str(adapter), "-p", str(archive), entry], env=self.env(),
                                  capture_output=True, timeout=10)
        self.assertEqual(0, extracted.returncode, extracted.stderr.decode(errors="replace"))
        self.assertEqual(payload, extracted.stdout)
        unsupported = subprocess.run([str(adapter), "-x", str(archive)], env=self.env(),
                                     capture_output=True, timeout=10)
        self.assertNotEqual(0, unsupported.returncode)
        self.assertTrue(unsupported.stderr)
        self.assertEqual(before, hashlib.sha256(archive.read_bytes()).hexdigest())


if __name__ == "__main__":
    unittest.main(verbosity=2)
