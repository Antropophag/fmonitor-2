# DURABLE-JOBS-RUNTIME-WIRING-001 — Gate 5 review

- Reviewer: `/root/runtime_review`
- Fixed point: `4234d97d54c0554994606b35a030bec09f2d0545`
- Verdict: **APPROVED**

Reviewed production hashes:

```text
6ff176b4c67521fd891396fc3346a95af97bca0ac0eae54add99a7a90a9613c7  app/Jobs/JobsRuntimeRequest.php
7b2fbd28b09d76f20666de7e20333bc679eff4eb7c67a08b155faceca6ae0f82  app/Jobs/JobsRuntimeCommand.php
169e8d85f1952e6a3ec8eddd6521e73ec450e5bf65dd25a511bd868352ce755f  app/Jobs/JobsSchedulerProcess.php
056e13037153780f6eedb57d8162b43a5d175efbe66b68c4224e79922f37edc2  app/Jobs/JobsRuntimeConfiguration.php
206f1686d2811ce3cdb0bd3235650f7759755c052c3fa111c4d609801fbe6c41  app/Jobs/MariaDbJobsConnection.php
08f33d7ee11b4791b9078b29bec174a874b9884ce8aa953784b6d80ec4ae207f  bin/fmonitor2-jobs.php
23be072ca5429b4e7351bc3b68b6a2a4b14453d0b8464adf336d5b2c40fdb3a0  deploy/runtime/compose.yaml
434eeacedbd3e1fe01ab2bc183e38fdd683b5649b582b6d27f0cb3bd3aeea749  rapid-pilot/workforce-worker.sh
```

The CLI grammar is closed and validates before configuration/DB access. Composition
uses direct captured config, read-only schema readiness, the fixed deployment
authority and the reviewed scheduler, worker, health, listing and retry seams.
Scheduler and worker role identities are instance-scoped; daemon signals and
heartbeat behavior delegate to the reviewed process owners.

The jobs profile resolves to two portless services using the same application image,
exact role commands and SIGTERM/60-second grace. Only the worker receives explicit
read-only Bitrix token/optional CA mounts. The scheduler has no transport config or
secret mount. Default Compose topology remains DB/PHP/web.

The historical worker script is now a single explicit-prefix `--once` adapter to the
native synchronization CLI. It preserves exit/stdout/stderr and has no manifest,
loop, sleep or ready-file ownership. The prior failure-path fixture was updated only
to supply the now-required prefix and deterministic environment.

Independent focused verification:

```text
jobs_runtime_contract_001_test.php: PASS
jobs_runtime_cli_001_test.php: PASS
legacy_workforce_once_delegation_001_test.php: PASS
workforce_worker_cli_manual_pilot_test.php: PASS
architecture check: ok=true, rules=7, errors=[]
git diff --check: PASS
```

The internal handler has its own approved Gate 5 record. Actual daemon/HTTPS Compose
smoke and repository CI remain final integration evidence rather than prerequisites
invented by this bounded source review.

## Operator runbook addendum

```text
7cbc97c4eca4ef9e162894aef45885b70a8313ff2069599bdea03a8fe7a4097f  docs/operations/production-runtime-runbook.md
```

Section 7 uses a no-network container to validate a bounded regular token, create a
private random staging directory and exclusive file, set `0600`/`10001:10001`, and
publish with an atomic same-filesystem hard link. An existing file or symlink cannot
be overwritten; cleanup removes only the owned staging link/directory. Remaining
jobs profile, diagnostic, retry, shutdown and restore-limit instructions match the
reviewed runtime wiring. **Addendum verdict: APPROVED.**

The recipe was executed in the existing runtime image with a synthetic token:
initial publication passed, existing-file refusal passed, a symlink victim remained
unchanged, and no staging directory leaked. Private evidence is retained at
`/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fm2-token-atomic-a61qb1u4`.

## Exact-image integration addendum

Reviewed operational identities:

```text
source: 116f65c4cc0bc15991bb749126dbcb87e63ee6f0
candidate: a2b6dcdc7d8518416ac7748a53ed245cd936a63a
image: sha256:ab51b9f4b2e9175f1679741b3ea4ee1bae044564d2d9cea23217dcd413874428
OCI revision: 116f65c4cc0bc15991bb749126dbcb87e63ee6f0
browser test: b460561892bb07322628c026a262ad5fe5ba76c3385c71147de9b6a410c5713d
```

Private evidence root `/private/tmp/fm2-jobs-image-smoke-lDdNnV` is mode0700 and
all contained files are mode0600. Safe summaries prove clean build input, exact OCI
identity, canonical migrations1–23, exact DML-only grants with no table/column
privileges, host-local verified TLS pagination, 51 Workforce rows, one completed
run/job and one schedule slot, current worker/scheduler role heartbeats and healthy
public status. Restart preserves counts/history and returns healthy status; SIGTERM
stops both services with exit0. Final `down --volumes` removed project containers,
network and volumes, and the local TLS process was reaped.

The first harness network failure is retained as failed evidence and was not reused
as success; the final result comes from a fresh isolated contour. No live external
endpoint was called. The v23 production browser regression also passes after the
reviewed distinct legacy OTIZ fixture correction, including post-OTIZ native private
PDF access.

**Integration verdict: APPROVED.** Source and built-image evidence close the bounded
runtime-wiring Gate5. Repository full CI remains pending and is not implied by this
approval.
