#!/usr/bin/env python3
"""YII2-LOCAL-QUICKSTART-001: canonical Make lifecycle ownership."""
from pathlib import Path

root = Path(__file__).resolve().parents[2]
makefile = (root / "Makefile").read_text()
example = (root / ".env.example").read_text()
readme = (root / "README.md").read_text()
development = (root / "docs/development-setup.md").read_text()
production = (root / "docs/operations/production-runtime-runbook.md").read_text()

targets = {}
current = None
for line in makefile.splitlines():
    if line and not line.startswith(("\t", " ")) and line.endswith(":"):
        current = line[:-1]
        targets[current] = []
    elif current is not None:
        targets[current].append(line)

for name in ("up", "down", "logs", "ps", "reset"):
    body = "\n".join(targets[name])
    assert "deploy/runtime/compose.yaml" in body or "RUNTIME_COMPOSE" in body, "LEGACY_MAKE_ROUTING"
    assert "rapid-pilot" not in body and "pilot" not in body.lower(), name

assert "--volumes" not in "\n".join(targets["down"])
assert "--volumes" in "\n".join(targets["reset"])
assert "FMONITOR_INITIAL_OWNER_EMAIL" in example
assert "FMONITOR_YII_COOKIE_VALIDATION_KEY" in example
assert "FMONITOR_BITRIX_WEBHOOK_URL" in example
assert "FMONITOR_SOURCE_HOST" in example
assert "local-integration-config" in "\n".join(targets["up"])
assert "make up" in readme and "8093" in readme
assert "make up" in development and "deploy/runtime/compose.yaml" in development
assert "Production runtime: clean setup" in production
print("PASS: YII2-LOCAL-QUICKSTART-001 canonical Make boundary")
