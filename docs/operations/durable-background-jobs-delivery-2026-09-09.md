# Durable background jobs — integration evidence, 2026-09-09

Issue34 candidate uses one MariaDB Jobs queue/outbox owner, canonical migration23,
separate worker/scheduler CLI services and the existing native workforce sync seam.
No production external transport has been enabled. Main user stand was not switched.

## Source and exact image

Reviewed runtime source `116f65c4cc0bc15991bb749126dbcb87e63ee6f0`, clean archive build;
image `sha256:ab51b9f4b2e9175f1679741b3ea4ee1bae044564d2d9cea23217dcd413874428`,
OCI revision equal to that source. Later commit `a2b6dcdc7d8518416ac7748a53ed245cd936a63a`
changes only four shared browser fixture files and their independent review record;
`git diff116f65c4..a2b6dcdc -- app bin public rapid-pilot deploy` is empty.

## Executed checks

24 Jobs executables are explicitly inventoried:23 focused tests passed together,
plus independently reviewed real connection-loss and late-event rollback test passed.
Canonical v23 affected regressions, architecture54 unit tests/actual7 rules and
verification inventory15 passed. OpenSpec strict and diff checks passed.

Exact-image Compose smoke used an isolated disposable project, canonical v23 and
DML-only principal (SELECT/INSERT/UPDATE/DELETE; no table/column privilege overrides).
Both portless jobs services used the same image. Verified local HTTPS and private
read-only token/CA produced2 test requests,51 workforce entries,1 completed native
run,1 completed job and1 scheduler slot. Health was successful. Restart preserved
these counts and3 job events; both services were healthy afterward. SIGTERM stopped
both with exit0. Process-level tests separately cover forced termination, grace
expiry, lease loss, stale settlement and reclaim. Containers/volumes were removed
and the fake TLS process reaped.

The first smoke harness lost its temporary TLS server between execution cells and
recorded transport failures. That failed isolated attempt was retained as evidence;
a fresh disposable DB was used for the successful run after fixing harness lifetime.
No production code or TLS verification was weakened.

Production browser regression passed on v23: native41 items/7 photos/85→100,
restart and exact history/private-file/session preservation, distinct legacy OTIZ
calculation/replay/accept/XLSX, then native original327 read with the existing session.
The fixture now registers legacy order80 on separate case4520 before identity backfill;
it no longer adds an invalid opposite-source row over native selection81. This does
not claim the native #24 deadline input gap is resolved.

Private primary smoke evidence: `/private/tmp/fm2-jobs-image-smoke-lDdNnV`
(directory0700/files0600). Focused root browser log:
`/tmp/fmonitor-jobs-valid-browser.log`. Payloads/tokens/dumps remain outside git.

## Remaining integration

Independent bounded source reviews and exact-image/browser integration review are
approved. One full CI on the exact published candidate remains before merge.
Issue36 will separately prove backup/restore/update of v23 Jobs state. Product
notification triggers/templates, operator UI30 and real external activation remain
separate work. Existing primary8092 and retained8093 were untouched; the older8093
synthetic OTIZ lineage limitation remains documented in the overnight checkpoint.

## First full CI and bounded correction

PR64 head49b7540b full Actions34315020151 failed only two executables in
Integration2/2; the other six executing groups succeeded, including E2E and
Integration1/2. The verify aggregate correctly failed. Full log and complete failure
inventory were collected before edits at `/tmp/pr64-49b7540b-full.log` and
`/tmp/pr64-49b7540b-failure-inventory.log`.

The calendar verifier still asserted terminal22. Demo provisioning/ready markers
already used23, but its read-only table-name catalogue remained63 entries, so status
reported incomplete. Independent Gate3 approved literal verifier/test frontier23
updates and exactly six Jobs names in the demo catalogue. Local demo RED reproduced
ready→incomplete/null before that source change. Calendar and complete public demo
launch/walkthrough/persistence/reset/cleanup now pass; syntax/diff and architecture7
rules pass. Corrected full CI is still required.

This correction changes only the disposable demo table-name catalogue in production
source. Earlier exact116f65c4 image remains the workforce/daemon evidence source;
queue/outbox/worker/scheduler/native workforce/runtime configuration files are
unchanged. The old image must not be labelled as the later corrected source.
