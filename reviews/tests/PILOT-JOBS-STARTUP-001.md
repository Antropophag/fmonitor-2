# PILOT-JOBS-STARTUP-001 — bounded Gate 3

Reviewer: `/root`; test author: `/root/review_startup`.
Baseline: `41e39498`. Verdict: **APPROVED for the fast root-caller regression**.

The test resolves the committed root Compose and invokes its actual configured
entrypoint and health command. It detects the removed no-argument caller contract,
requires worker/scheduler startup together, reaches native Jobs with a valid pilot
manifest and unavailable DB, rejects missing/ambiguous/non-ready manifests, and
checks private-token cleanup. It does not claim a successful real DB/HTTPS startup;
the separate Compose smoke required by the specification remains pending.

Initial independent RED: `php tests/InstallationProcess/pilot_jobs_startup_001_test.php`
exited255: `workforce-sync invokes exact pilot Jobs adapter mode`, actual
`[rapid-pilot/workforce-worker.sh]` versus the required worker adapter invocation.
Review caught and corrected the initial positive manifest fixture, Compose `CMD`
prefix handling and Make variable case before implementation. Expectations follow
PILOT-JOBS-STARTUP-001 and the existing native CLI exit contract.

## Isolated Compose acceptance addendum

Reviewer: `/root/review_startup`; test author: `/root/root_compose_smoke`.
Verdict: **APPROVED** as the built-image integration extension.

```text
caa0b77f10c1c5a465661ff0733ef6dce71c80f585214c5cf1ceadfd1797813b  tests/Deployment/pilot_jobs_compose_001_test.py
```

The test keeps the committed root Compose as the caller and uses an override only
to isolate its exact image, ports, project, private fixture paths and volumes. It
builds the real image, boots a fresh pilot and MariaDB, then starts both configured
jobs services. A local certificate chain and HTTPS Bitrix fixture exercise the
native two-page workforce delivery without a real external endpoint.

The assertions are sensitive to the material integration failures: both services
must remain running, native health must become healthy, the fixture must receive
only the two expected `user.get` pages, and the generated pilot prefix must own 51
catalog rows, one completed sync run, one job, one scheduler slot and at least three
job lifecycle events. The test waits for this complete persisted condition rather
than treating HTTP receipt or Jobs health as delivery completion. It then restarts
both roles, waits for native health again, proves workforce/Jobs facts remain, and
rejects duplicate schedule keys while allowing a legitimate next hourly slot.
Service logs are checked for the fixture token.

Review identified and corrected the initial delivery-commit race before the exact
accepted run. The final test has bounded process, delivery, persistence, restart and
cleanup waits; uses a unique Compose project/image and temporary fixture root; binds
no host port; and removes its volumes, containers, image and fixture state in
`finally`.

Exact accepted execution:

```text
python3 -m py_compile tests/Deployment/pilot_jobs_compose_001_test.py && \
python3 tests/Deployment/pilot_jobs_compose_001_test.py
PASS: PILOT-JOBS-STARTUP-001 isolated root Compose delivery and restart

git diff --check
exit 0, no output
```

This extension inherits the qualifying root-caller RED recorded above. It was added
to the `e2e` suite and categorized `e2e`; it does not replace full CI.
