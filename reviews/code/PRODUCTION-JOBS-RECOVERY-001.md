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
