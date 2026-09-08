# Test review: ASSIGNMENT-ORDER-ORIGINAL-DATA-WORKER-001 v1

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed commit: `30609a8f4bb5c9542e2a5b7303737bcfd97a72fa`
- Specification SHA-256: `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Test SHA-256: `ea4bde48b6ac265b8e91d166feec723dcfb911ab821517a4a89a1ab22d8a10d0`
- Worker helper SHA-256: `4b7373d6b13f7552ea5cb43c3ee31d52454cf27f1ea3cb48606f5450a6925043`
- Verdict: **APPROVED**

The reviewer authored neither the test/helper nor future implementation.

The ten-case public worker suite has three exact intended REDs and seven positive controls. Its final aggregate failure identifies only:

```text
real-worker-commit_unknown_not_found-release1
worker-safe-log-first-missing-password-1
real-worker-different-correction-race-same-pdf-precheck
```

No setup, include, type, declaration, database, timeout or cleanup failure is counted as RED.

The six unknown-outcome combinations exercise real worker commit/rollback state, typed FOUND/NOT_FOUND/UNAVAILABLE results, later durable replay and zero/one release diagnostics. The missing not-found release phase is sensitive: a failed release after reliable NOT_FOUND must report `unknown_not_found`, not disguise the selected recovery as generic rollback.

The two stable missing-path cases preserve the approved safe-log-first ordering boundary. Missing safe log with valid password is a control through the existing fixed transport. Missing safe log plus missing password detects the current premature password read/warning. Exact expected channels are exit 70, empty stdout/ready/result and one fixed stderr line, with no created file or database fact. This does not use FIFO, permission transition, interval observer, native interception or any rejected safe-log mechanism. Gate 5 must still inspect exact source ordering because suppressed file I/O cannot be completely proven by this dynamic axis alone.

The correction races are causally distinct. Same PDF with a different date uses `AFTER_FINGERPRINT_MISS_BEFORE_CAS`; after A commits, B must perform v06 step-11 current validation and select STALE before allocation/finalize. It owns no lease or extra blob, and its configured release fault remains unused. A same-PDF test expecting a release diagnostic would preserve obsolete post-finalize behavior.

The distinct 328-byte PDF case uses `AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT`. Both workers hold different digest leases before release; A commits first, B then loses CAS, selects STALE, releases once with `commit_conflict`, and leaves one private unreferenced loser blob. That orphan is permitted storage state and never becomes revision/event evidence.

The test does not claim the worker barrier alone proves the exact post-CAS repository method. Approved pure application tests and mandatory Gate 5 source inspection retain that proof. Worker outputs, revision/event/request counts, safe-log correlation/phase and private inventories independently prevent fabricated barriers, blind writes, duplicate facts and false lease ownership.

`OriginalIntegrityWorker` uses the established four socketpair protocol without shell interpolation. It enforces a 15-second monotonic deadline, bounded channel buffers, exact READY/RELEASE identity, single release, process termination and reaping. Every case owns a random database, configuration files, safe log and private root through the existing exact cleanup fixture.

Exact supporting hashes:

```text
dbb17374533cd542757f41d5ed4a0f76eb48c0d1b7adac22a789dab9bbe23794  tests/Support/AssignmentOrderOriginalIntegrityCommits.php
7c1de9364e9f57ace5bfca3778a03cf46d368759e91be0be5c52b79d1add98e1  tests/Support/AssignmentOrderOriginalIntegrityDatabase.php
a05c4a22f3c0360ecb865c44ffb5043aa55b02e3ed11374c8743a7537114ce75  tests/Support/AssignmentOrderOriginalIntegrityFixture.php
dfbf0849e34ea941477b238049b29a7e3cdf3566b8bd8b3fdb9931ac14ee551e  tests/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php
3a368153a81ac34805e3f2b2affc560f8586e0fa236077baafeab9c5c4db353f  private worker RED evidence.json
```

No changes required. Gate 4 may implement the approved worker/data-integrity behavior. The old worker-transport patch, production fresh factory/source ordering and cumulative command review remain separate required evidence.
