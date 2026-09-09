# Code review: PILOT-JOBS-STARTUP-001

- Reviewer: `/root/review_startup` (independent test author; did not author production implementation)
- Baseline: `origin/main` / `41e39498`
- Reviewed candidate: shared worktree before commit, 2026-09-09
- Specification: `specs/PILOT-JOBS-STARTUP-001.md`
- Test review: `reviews/tests/PILOT-JOBS-STARTUP-001.md`
- Verdict: **APPROVED** for the bounded startup implementation

Reviewed content hashes:

```text
c47d0105478ee1dbb0c715e8a4ec85f1d3adca690485dace77bae7a4990081e5  Makefile
143d1c2b587b4d780cff2031597d5b94268feb2da2ff396ef36185194d38f7ec  compose.yaml
eb96cc950ede1731e5a8392f0e07c2113d9e058d51ecbf91ff627529f8737ea3  tools/delivery/compose.yaml.in
722cd01bcfa487f0ee1d46fd3315a41360572d8f76af423f47a06d55af0e4de6  rapid-pilot/jobs-entrypoint.php
8a820ec9e7a26ab96dfffc87b0c51907eb381dbb32e271c0ecf6c733d8ea72f1  tests/InstallationProcess/pilot_jobs_startup_001_test.php
998fa32f3089cff3afb579e7a601b8b1732e4a8ca3ef61c8c87f6fa8b48619a7  specs/PILOT-JOBS-STARTUP-001.md
```

## Findings

No blocking source finding remains.

The root Compose caller, its generated template and `make up` now agree on one
topology: `workforce-sync` invokes the native Jobs worker through the pilot-only
adapter, `workforce-scheduler` invokes the native scheduler, and Make starts and
waits for both services together. Both roles retain the existing persistent
database and pilot-state volume, fixed `pilot` instance, SIGTERM and 60-second
grace. The production `deploy/runtime/compose.yaml` and historical explicit-prefix
`workforce-worker.sh --once` paths are unchanged.

`rapid-pilot/jobs-entrypoint.php` is a bounded deployment translation. It accepts
only worker/scheduler/health, validates exactly one readable ready manifest and a
nonempty safe prefix, then calls the public `JobsRuntimeCommand`. It does not own
scheduling, migrations or business writes. Only worker parses the existing private
Bitrix document. Its token is staged as a private file, passed by path, excluded
from output and removed by `finally` after ordinary return, failure or the native
SIGTERM return path. Scheduler and health receive no Bitrix configuration.

Both health checks invoke native Jobs health with the same manifest prefix and
instance. The stale `/tmp/workforce-ready` marker is no longer referenced, so a
successful old synchronization cannot mask queue/process failure.

The reviewed public regression resolves the actual root Compose model and executes
the extracted commands. Its pre-implementation RED failed on the old zero-argument
shell entrypoint. The current candidate passes worker, scheduler and health routing,
missing/ambiguous/non-ready manifest rejection, staged-token cleanup, Compose
shutdown/readiness attributes and the joint Make target.

## Focused verification

```text
php tests/InstallationProcess/pilot_jobs_startup_001_test.php
pilot_jobs_startup_001_test: PASS

php tests/Jobs/legacy_workforce_once_delegation_001_test.php
PASS: JOBS-RUNTIME-WIRING-001 legacy once delegation

php tests/Jobs/jobs_runtime_contract_001_test.php
PASS: JOBS-RUNTIME-WIRING-001 resolved Compose boundary

php tests/InstallationProcess/workforce_worker_cli_manual_pilot_test.php
workforce_worker_cli_manual_pilot_test: PASS

php -l rapid-pilot/jobs-entrypoint.php
No syntax errors detected

git diff --check
exit 0, no output
```

The specification's isolated built-image Compose restart and local HTTPS delivery
smoke is reviewed separately below. This verdict does not claim full CI, image
digest, or installation on the preserved main stand.

## Built-image integration addendum

The independently authored and reviewed E2E test at exact hash
`caa0b77f10c1c5a465661ff0733ef6dce71c80f585214c5cf1ceadfd1797813b`
passed against the exact implementation hashes recorded above. It built the root
image, started an isolated fresh root stack, completed native workforce delivery
through a local verified HTTPS fixture, observed the complete durable Jobs state,
restarted worker and scheduler, regained native health, and preserved facts without
duplicate scheduler keys. All owned containers, volumes, image and temporary
fixture resources were cleaned afterward.

```text
python3 -m py_compile tests/Deployment/pilot_jobs_compose_001_test.py && \
python3 tests/Deployment/pilot_jobs_compose_001_test.py
PASS: PILOT-JOBS-STARTUP-001 isolated root Compose delivery and restart
```

Integration verdict: **APPROVED** for PILOT-JOBS-STARTUP-001 on the exact hashes in
this record. Full CI and installation on the preserved main stand remain separate
delivery steps.
