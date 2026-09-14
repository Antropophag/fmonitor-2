#!/usr/bin/env python3
"""YII2-LOCAL-QUICKSTART-001: canonical and operational docs stay distinct."""
from pathlib import Path

root = Path(__file__).resolve().parents[2]
readme = (root / "README.md").read_text()
development = (root / "docs/development-setup.md").read_text()
runtime = (root / "deploy/runtime/README.md").read_text()
runbook = (root / "docs/operations/production-runtime-runbook.md").read_text()

assert "git clone" in readme and "cp .env.example .env" in readme and "make up" in readme
assert "http://127.0.0.1:8093" in readme
assert "make up" in development and "актуальн" in development.lower()
assert "production-runtime-runbook.md" in runtime
for command in ("docker build", "--profile deployment run --rm prepare", "--profile deployment run --rm migrate"):
    assert command in runbook
print("PASS: YII2-LOCAL-QUICKSTART-001 documentation separation")
