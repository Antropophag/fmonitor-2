#!/usr/bin/env python3
"""FOCUSED-CHECK-CACHE-180 cheap regression for cache-key placement/identity."""

from pathlib import Path
import re


ROOT = Path(__file__).resolve().parents[2]
recipe = (ROOT / "tools/delivery/Dockerfile.focused-checks").read_text()
launcher = (ROOT / "tools/delivery/run-in-profile").read_text()

common = recipe.split("FROM ${PHP_IMAGE}@${PHP_IMAGE_DIGEST} AS common", 1)[1]
common = common.split("FROM common AS governance", 1)[0]

dependency_runs = [
    "apt-get update",
    "composer validate",
    "composer install",
    "uv sync",
]
for command in dependency_runs:
    assert command in common, f"FCC180 setup: missing dependency command {command}"

label_pos = common.index("LABEL org.fmonitor.composer-lock-sha256")
for argument in ("COMPOSER_LOCK_SHA256",):
    declarations = list(re.finditer(rf"(?m)^ARG {argument}$", common))
    assert len(declarations) == 1, f"FCC180: common must declare {argument} exactly once"
    assert declarations[0].start() < label_pos, f"FCC180: {argument} must precede LABEL"
    for command in dependency_runs:
        assert declarations[0].start() > common.index(command), (
            f"INTENDED_RED FCC180-01: metadata-only {argument} invalidates {command}"
        )

assert common.index("COPY composer.json composer.lock") < common.index("composer install"), (
    "FCC180-02: Composer inputs must remain before Composer installation"
)
assert common.index("COPY pyproject.toml uv.lock") < common.index("uv sync"), (
    "FCC180-02: uv inputs must remain before uv installation"
)
assert 'org.fmonitor.composer-lock-sha256="$COMPOSER_LOCK_SHA256"' in common
assert "EXECUTABLE_SOURCE" not in common
assert "org.fmonitor.executable-source" not in common

for build_argument in (
    '--build-arg "COMPOSER_LOCK_SHA256=$composer_lock_sha"',
):
    assert build_argument in launcher, f"FCC180 identity input lost: {build_argument}"
for inspection in (
    'type=bind,src=$materialized,dst=/workspace,readonly',
    'FMONITOR_EXECUTED_SOURCE=$executable_source',
):
    assert inspection in launcher, f"FCC180 stale identity guard lost: {inspection}"

result_body = launcher.split('print("RUN_IN_PROFILE_RESULT "', 1)[1]
assert '"duration_seconds"' in result_body
assert '"wall_time_seconds"' not in result_body, (
    "FCC180-04: external wall time must not change RUN_IN_PROFILE_RESULT"
)

print("FOCUSED-CHECK-CACHE-180 PASSED")
