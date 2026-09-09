# PRODUCTION-JOBS-RECOVERY-001 — bounded Gate 5 review

- Reviewer: `/root/runtime_review`
- Verdict: **APPROVED**

Reviewed production artifacts:

```text
f57355b403616b2638b8a9e952176ae403abff0ace73ece0e896275183936347  app/RuntimeRestore/RuntimeRecovery.php
60fa1fd41837e5df38936b55416f6ed511c0db86b53d5783a7fc9116e878d90b  app/RuntimeRestore/RuntimeRecoverySchemaV23.php
07d78c9341380923b350dd29c2c74077d98619bdb404967d23547d231f8e19d8  app/RuntimeRestore/RuntimeRecoverySchemaV22.php (unchanged)
```

`RuntimeRecoverySchemaV23` contains the exact independently reviewed, sorted 69
base-table and 39 AUTO_INCREMENT identities and technical `deferred=[]` literal.
Backup and restore now bind to that current-image contract. Preflight rejects wrong
frontier/table/AUTO/deferred metadata before target DDL. The historical v22 class is
byte-identical and historical bundles remain owned by the exact v22 image.

The recovery path contains no JobQueue, scheduler, outbox, handler or operator
transition call. It restores exact data-only rows, AUTO values and private state,
then applies schema/storage readiness. Stale heartbeat, expired lease and dead
backlog remain operational Jobs state rather than restore corruption.

Reviewed executable evidence:

```text
8493212bca5db40f345a571c0cc88ffc05cc7123a9c062d4cb903623d3780d17  tests/Runtime/runtime_recovery_forward_update_001_test.php
181779c898de26ba797df7bcc3f9317a5d5c3ceaf95f4b77acf56085b83c0125  tests/Runtime/runtime_jobs_recovery_001_test.php
```

The forward test is GREEN for exact historical v22 archive/image restore, 63/35
rows/state/AUTO preservation through migration23, six initially empty Jobs tables,
v23 bundle generation and v22-image zero-mutation rejection. The populated test is
GREEN for exact v23 roundtrip, literal69/39, meaningful deleted-high Jobs AUTO gap,
all six Jobs families, zero transition, corrupt-manifest preflight, DML-only fake
resume, lease attempt5/stale-token behavior and outbox linked recovery without
non-Jobs mutation. Logs:

```text
/tmp/fmonitor-jobs-recovery-forward-green.log
/tmp/fmonitor-v23-recovery-final-green.log
```

PHP syntax and `git diff --check` pass; the exact test image tag was cleaned. This
approval closes the bounded source behavior. A retained exact-image operational
drill, runbook update and authoritative repository CI remain required before final
production-integration/Done claims.

## Recovery test-container portability addendum

The production recovery implementation is unchanged. All three recovery launchers
now use one test-only topology selector so a host-loopback MariaDB remains private:
native Linux uses Docker host networking and the original DB host; Darwin retains
the existing `host.docker.internal` gateway alias. Unsupported platforms fail setup
explicitly.

```text
7ae03cec1dcb2c323cf1587dd111118d60442aa3cb05f75db882a965c0688b70  tests/Support/RecoveryContainerNetwork.php
e943b2e71e5f8d5f92e5bc8dc9c0bdb06a3cb0865e86e1bd2ac5df2d96bac9ec  tests/Runtime/runtime_recovery_001_test.php
9ed90f9328c738bc49da155ea93df7a789e1b9ba6aa96c427d0206dc80dbe2f8  tests/Runtime/runtime_jobs_recovery_001_test.php
1b3cf0751c3239a2d2b4c459b87d2128b08a5aa3630a603a810c88ce34df530d  tests/Runtime/runtime_recovery_forward_update_001_test.php
```

The v22 body below its launcher is unchanged. Exact-tag/image cleanup remains
bounded. A Linux container SELECT1 probe through host networking passed without
widening the DB bind, followed by all three full tests GREEN; evidence is under
`/tmp/fmonitor-recovery-portability/`. **Test portability verdict: APPROVED.**

## V23 operational drill addendum

Private evidence root `/tmp/fmonitor2-restore36-v23-drill.BxOeX7` is mode0700;
operational evidence, scripts and credentials are mode0600. Reviewed identities:

```text
source/OCI revision: e5d1b34e2f902b9aec9a99a00c632e82b21fef5b
image: sha256:82c0a8214b35ab80de1c036f9a91a2c9451909f473c3202426e89a08c8a158cb
schema/tables/AUTO: 23 / 69 / 39
bundle manifest SHA-256: 3d6c1158778b43754ad60d2e60b21649d69d9291d31559a304e8d37cb1973200
```

The operator stopped scheduler, then worker, then web/php with each Compose command
returning0. A public fixture had already recorded one completed job and one unknown
leased attempt before that stop; process-level graceful/forced signal causality
remains evidence from the dedicated approved worker tests rather than being
reclaimed by this drill.

Backup ran with the source runtime DB identity, distinct from the migration identity;
restore deliberately used the deployment identity for canonical schema creation.
Backup and restore returned their exact success JSON in 1.190s and 1.787s. Exact
pre-resume source/target data dumps and state inventories compare byte-for-byte,
proving restore performed no queue/outbox transition. The initial restored Jobs
health correctly returned70 for stale scheduler/worker heartbeats while runtime
readiness remained healthy.

The retained target at `http://127.0.0.1:18196/` is currently healthy. Browser
evidence proves owner progress100/private PDF327 and one restored OTIZ row/XLSX7364
without console/network errors. DML fake resume settled two jobs, swept one pending
intent, made one fake transport call and completed the intent. No live external call
or retained existing stand was used. The inspected screenshot shows the restored
completed object at100% without an error overlay.

**Operational drill verdict: APPROVED for v23 exact restore and fake resume.** Tasks
4.3/4.4 for a second-image update and HTTP-only rollback boundary, runbook final
cross-check, retained-contour cleanup decision and authoritative CI remain pending;
this addendum does not mark Done.

## Update and HTTP-only rollback addendum

The additive-compatible HTTP rollback used exact historical f22 image
`sha256:8acc52ff74b6004cc0a3c7a25eeede62fe286a492a0801af339d52547fe78d93`
with Jobs services stopped and no migration/downgrade. It preserved health200,
owner session/private PDF327, engineer checklist revision58/seven photos and
authenticated OTIZ payments. The approved cross-version test separately proves v22
tooling rejects a v23 bundle without mutation, so full contour/schema/Jobs rollback
remains unsupported and forward restore is required.

After returning web/php to exact e5 image
`sha256:82c0a8214b35ab80de1c036f9a91a2c9451909f473c3202426e89a08c8a158cb`,
the final appendix SHA-256
`b04ee5ca5cac45e2725fd0d6fe6b05b5930e226bcfd9609c80c3b5e24ef23c1a`
proves owner progress100/PDF327, engineer revision58/seven photos, three HTTP200
append operations, final revision61/41 items/seven photos, exact prior checklist/
installer history inclusion and exact prior state-member preservation. Source DB is
stopped with volumes retained; target18196 remains healthy for review.

**Tasks 4.3 and 4.4 operational verdict: APPROVED.** Final runbook safety review,
delivery report, target cleanup decision and authoritative CI remain pending.

## Recovery runbook review

```text
62954d31e4d15c73fab0654c281902b7c48be493d027db881418040f32531b65  docs/operations/runtime-recovery-runbook.md
```

The executable recipe preserves source project/session/image identity, ordered
quiesce with DB running, private bundle ownership, an absent parent-mounted target
state root, migration-to-runtime credential replacement, readiness/JobsHealth
separation and the forward-only rollback boundary. Fake/local acceptance explicitly
keeps production jobs stopped and clears ambient Bitrix config; production resume is
a distinct operator-authorized branch with exact jobs-env and transport-file checks.

Before the first target Docker mutation it rejects any container with the target
project label, all three target volumes and the target default network, so no foreign
resource can be adopted or overwritten. Elapsed CLI measurements are not presented
as RTO. Source volumes and retained target cleanup have explicit separate policies.

**Runbook verdict: APPROVED.** Delivery report finalization, retained-target cleanup
decision and authoritative CI remain pending.
