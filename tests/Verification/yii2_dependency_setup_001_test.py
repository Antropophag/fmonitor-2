"""YII2-DEPENDENCY-001: repository-owned locked Composer bootstrap."""
from pathlib import Path
import hashlib
import json
import os
import re
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[2]
COMPOSER_VERSION = "2.10.3"
COMPOSER_SHA256 = "7a2d379d5b8ffdaa028580ef26494c36d2feef4b178d3dd1473a4dbc5e17c8d6"


class Yii2DependencySetup(unittest.TestCase):
    def test_repository_dependency_graph_is_exact_and_locked(self):
        manifest = json.loads((ROOT / "composer.json").read_text())
        requirements = manifest.get("require", {})
        self.assertEqual("2.0.55", requirements.get("yiisoft/yii2"),
                         "INTENDED_RED YII2-DEPENDENCY-001 Yii2 exact requirement")
        self.assertEqual("6.11.4", requirements.get("tecnickcom/tcpdf"),
                         "INTENDED_RED YII2-DEPENDENCY-001 TCPDF exact requirement")
        self.assertEqual("8.4.0", manifest.get("config", {}).get("platform", {}).get("php"),
                         "INTENDED_RED YII2-DEPENDENCY-001 PHP platform contract")

        lock = json.loads((ROOT / "composer.lock").read_text())
        packages = {package["name"]: package for package in lock.get("packages", [])}
        self.assertEqual("2.0.55", packages.get("yiisoft/yii2", {}).get("version"),
                         "INTENDED_RED YII2-DEPENDENCY-001 locked Yii2")
        self.assertEqual("6.11.4", packages.get("tecnickcom/tcpdf", {}).get("version"),
                         "INTENDED_RED YII2-DEPENDENCY-001 locked TCPDF")

    def test_setup_uses_verified_composer_and_real_locked_install(self):
        pins = (ROOT / "tools/delivery/dependencies.env").read_text()
        self.assertRegex(pins, rf"(?m)^COMPOSER_VERSION={re.escape(COMPOSER_VERSION)}$")
        self.assertRegex(pins, rf"(?m)^COMPOSER_SHA256={COMPOSER_SHA256}$")

        composer_setup_path = ROOT / "tools/delivery/setup-composer.sh"
        self.assertTrue(composer_setup_path.is_file(),
                        "INTENDED_RED YII2-DEPENDENCY-001 lightweight Composer bootstrap")
        setup = (ROOT / "tools/delivery/setup.sh").read_text()
        composer_setup = composer_setup_path.read_text()
        dependencies = (ROOT / "tools/delivery/setup-dependencies.sh").read_text()
        combined = composer_setup
        self.assertIn("composer.phar", combined,
                      "INTENDED_RED YII2-DEPENDENCY-001 pinned Composer bootstrap")
        self.assertRegex(combined, r"sha256|shasum",
                         "INTENDED_RED YII2-DEPENDENCY-001 Composer digest verification")
        self.assertRegex(combined, r"php[^\n]*install[^\n]*--no-interaction",
                         "INTENDED_RED YII2-DEPENDENCY-001 locked non-interactive install")
        self.assertNotIn("git clone --branch \"$TCPDF_VERSION\"", setup)
        self.assertNotIn("cat rapid-pilot/tcpdf-autoload.php", setup)
        self.assertNotIn("check_git vendor/tecnickcom/tcpdf", dependencies)
        self.assertIn("tools/delivery/setup-composer.sh", setup)

    def test_ci_uses_repository_bootstrap_without_floating_composer(self):
        action = (ROOT / ".github/actions/setup-runtime/action.yml").read_text()
        self.assertIn("tools/delivery/setup-composer.sh", action,
                      "INTENDED_RED YII2-DEPENDENCY-001 action calls lightweight bootstrap")
        self.assertNotIn("tools/delivery/ci-setup.sh", action)
        setup_php_block = re.search(r"uses:\s*shivammathur/setup-php@.*?(?=\n\s*- uses:|\Z)",
                                    action, re.S)
        if setup_php_block:
            self.assertNotRegex(setup_php_block.group(0), r"tools:\s*composer(?::[^\s]+)?",
                                "YII2-DEPENDENCY-001 action must not select Composer independently")

    def test_bootstrap_rejects_bad_digest_and_partial_install_without_publication(self):
        script = ROOT / "tools/delivery/setup-composer.sh"
        self.assertTrue(script.is_file(),
                        "INTENDED_RED YII2-DEPENDENCY-001 behavioral Composer seam")
        with tempfile.TemporaryDirectory(prefix="yii2-composer-") as directory:
            checkout = Path(directory) / "repo"
            (checkout / "tools/delivery").mkdir(parents=True)
            for relative in ["composer.json", "composer.lock", "tools/delivery/dependencies.env",
                             "tools/delivery/setup-composer.sh"]:
                target = checkout / relative
                target.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(ROOT / relative, target)
            binary = Path(directory) / "bin"
            binary.mkdir()
            payload = b"fixture composer phar\n"
            digest = hashlib.sha256(payload).hexdigest()
            pins = checkout / "tools/delivery/dependencies.env"
            pins.write_text(re.sub(r"(?m)^COMPOSER_SHA256=.*$", f"COMPOSER_SHA256={digest}",
                                   pins.read_text()))
            trace = Path(directory) / "trace"
            self._executable(binary / "curl", """out=''; while [ "$#" -gt 0 ]; do
case "$1" in -o|--output) out="$2"; shift 2;; *) shift;; esac; done
printf '%s' "${CURL_PAYLOAD}" > "$out"
""")
            self._executable(binary / "php", """printf '%s\\n' "$*" >> "$TRACE"
case "$*" in
*check-platform-reqs*) test "${PLATFORM_FAIL:-0}" = 0 || exit 72;;
*InstalledVersions*) test "${GRAPH_FAIL:-0}" = 0 || exit 73;;
*install*) mkdir -p "${COMPOSER_VENDOR_DIR:?}"; printf '<?php\\n' > "$COMPOSER_VENDOR_DIR/autoload.php"; test "${INSTALL_FAIL:-0}" = 0 || exit 71;;
esac
""")
            environment = dict(os.environ, PATH=str(binary) + ":/usr/bin:/bin",
                               TRACE=str(trace), CURL_PAYLOAD="corrupt", INSTALL_FAIL="0")
            bad = subprocess.run(["/bin/bash", "tools/delivery/setup-composer.sh"], cwd=checkout,
                                 env=environment, capture_output=True, text=True, timeout=10)
            self.assertNotEqual(0, bad.returncode)
            self.assertFalse((checkout / "vendor").exists())
            self.assertNotIn("composer-2.10.3.phar",
                             trace.read_text() if trace.exists() else "",
                             "unverified Composer payload was executed")

            (checkout / "vendor").mkdir()
            (checkout / "vendor/owner.bin").write_bytes(b"owner bytes\x00\n")
            preserved = self._fingerprint(checkout / "vendor")
            bad_existing = subprocess.run(["/bin/bash", "tools/delivery/setup-composer.sh"],
                                          cwd=checkout, env=environment,
                                          capture_output=True, text=True, timeout=10)
            self.assertNotEqual(0, bad_existing.returncode)
            self.assertEqual(preserved, self._fingerprint(checkout / "vendor"))
            shutil.rmtree(checkout / "vendor")

            environment["CURL_PAYLOAD"] = payload.decode()
            environment["INSTALL_FAIL"] = "1"
            partial = subprocess.run(["/bin/bash", "tools/delivery/setup-composer.sh"], cwd=checkout,
                                     env=environment, capture_output=True, text=True, timeout=10)
            self.assertNotEqual(0, partial.returncode)
            self.assertIn("install", trace.read_text(),
                          "fixture did not reach the intended Composer install failure")
            self.assertFalse((checkout / "vendor").exists(), "partial vendor was published")

            environment["INSTALL_FAIL"] = "0"
            installed = subprocess.run(["/bin/bash", "tools/delivery/setup-composer.sh"],
                                       cwd=checkout, env=environment,
                                       capture_output=True, text=True, timeout=10)
            self.assertEqual(0, installed.returncode, installed.stdout + installed.stderr)
            self.assertTrue((checkout / "vendor/autoload.php").is_file())
            complete = self._fingerprint(checkout / "vendor")
            repeated = subprocess.run(["/bin/bash", "tools/delivery/setup-composer.sh"],
                                      cwd=checkout, env=environment,
                                      capture_output=True, text=True, timeout=10)
            self.assertEqual(0, repeated.returncode, repeated.stdout + repeated.stderr)
            self.assertEqual(complete, self._fingerprint(checkout / "vendor"))

            environment["PLATFORM_FAIL"] = "1"
            trace.write_text("")
            incompatible = subprocess.run(["/bin/bash", "tools/delivery/setup-composer.sh"],
                                          cwd=checkout, env=environment,
                                          capture_output=True, text=True, timeout=10)
            self.assertNotEqual(0, incompatible.returncode)
            self.assertIn("check-platform-reqs", trace.read_text())
            self.assertEqual(complete, self._fingerprint(checkout / "vendor"))

            environment["PLATFORM_FAIL"] = "0"
            environment["GRAPH_FAIL"] = "1"
            trace.write_text("")
            stale = subprocess.run(["/bin/bash", "tools/delivery/setup-composer.sh"],
                                   cwd=checkout, env=environment,
                                   capture_output=True, text=True, timeout=10)
            self.assertNotEqual(0, stale.returncode)
            self.assertIn("InstalledVersions", trace.read_text())
            self.assertEqual(complete, self._fingerprint(checkout / "vendor"))

    def test_check_is_read_only_and_allows_missing_installations(self):
        script = ROOT / "tools/delivery/setup-composer.sh"
        self.assertTrue(script.is_file(),
                        "INTENDED_RED YII2-DEPENDENCY-001 behavioral Composer seam")
        with tempfile.TemporaryDirectory(prefix="yii2-composer-check-") as directory:
            checkout = Path(directory) / "repo"
            (checkout / "tools/delivery").mkdir(parents=True)
            for relative in ["composer.json", "composer.lock", "tools/delivery/dependencies.env",
                             "tools/delivery/setup-composer.sh"]:
                target = checkout / relative
                target.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(ROOT / relative, target)
            before = self._fingerprint(checkout)
            result = subprocess.run(["/bin/bash", "tools/delivery/setup-composer.sh", "--check"],
                                    cwd=checkout, capture_output=True, text=True, timeout=10)
            self.assertEqual(0, result.returncode, result.stdout + result.stderr)
            self.assertEqual(before, self._fingerprint(checkout))

    @staticmethod
    def _executable(path, body):
        path.write_text("#!/bin/sh\nset -eu\n" + body)
        path.chmod(0o700)

    @staticmethod
    def _fingerprint(root):
        rows = []
        for path in sorted(root.rglob("*")):
            relative = str(path.relative_to(root))
            rows.append((relative + "/", "directory") if path.is_dir()
                        else (relative, hashlib.sha256(path.read_bytes()).hexdigest()))
        return rows


if __name__ == "__main__":
    unittest.main(verbosity=2)
