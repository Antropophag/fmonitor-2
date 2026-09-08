# Test review: ASSIGNMENT-ORDER-ORIGINAL-DATA-MARIADB-FRESH-001 v1

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed test/source commit: `a49fd3f0ba3bb88eaf4b8a1effa886119f60b537`
- Specification SHA-256: `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Test SHA-256: `e8c8d8db891f3ee739ce88e58f6795db9f0527289823a00c2ff944cad39bf65c`
- Verdict: **APPROVED**

The reviewer authored neither test nor future implementation.

The 25-case suite provides the required real counterpart to the public recovery RED. It creates a random schema and an exact task-owned SELECT-only MariaDB principal with account `MAX_STATEMENT_TIME 2`, then removes that exact principal and schema in bounded cleanup. The privilege control proves reads are allowed and DML is denied without modifying global server settings.

Factory construction is lazy: credential files may appear only after construction, and invalid/missing configuration returns typed unavailable without creating or repairing paths/facts. A valid open proves exactly one new server connection ID distinct from the writer, a constrained fresh-reader surface rather than a write repository, one terminal lookup, one native close, cached repeat close and no mutations. Open-result private construction, clone and serialization boundaries protect reader ownership.

The pending-writer case is deterministic and sensitive to an ordinary snapshot false miss. The writer holds an uncommitted exact request insert while a bounded child invokes the public reader. Correct behavior waits or times out and returns UNAVAILABLE, never NOT_FOUND. Separate committed and rolled-back controls require validated FOUND and reliable NOT_FOUND. A missing request row with its revision/audit backing still present returns UNAVAILABLE and performs no repair.

The child has a ten-second parent deadline, bounded output, termination/reaping and fixed redacted failure protocol. The account's two-second statement limit bounds the lock wait. No sleep, same-connection fallback, shared credential or production data participates.

Production composition is exercised through both degraded `create` and required `createRecoveryReady`. `OriginalIntegrityAckLossMysqli::commit` calls the real parent native commit first; only after an independent connection observes the durable request does its test closure close the supplied task-owned writer and throw acknowledgement loss. This is an injected public mysqli method boundary explicitly permitted by v0.6. It is not global/native interception, metadata substitution, privilege manipulation or a safe-log mechanism. The ready factory must recover through one new connection; degraded construction returns outcome unknown; later same-request replay performs no new write or fresh open.

Invalid safe-log ordering proves zero writer/provider calls and no private-root/path creation. Fresh-close observer Throwable still attempts and confirms native connection closure, caches FAILED and emits no second close attempt. The test does not claim constructor file-I/O or worker ordering solely from dynamic behavior; those remain exact-source and worker proof obligations.

One passing control and 24 intended failures are sufficient because successful factory/open/read/close behavior is asserted repeatedly inside the failing cases; the explicit absent API marker cannot masquerade as setup failure. Real source API remains absent at RED, while child exit 42 is deliberately classified as intended missing factory only.

Exact supporting hashes:

```text
7c1de9364e9f57ace5bfca3778a03cf46d368759e91be0be5c52b79d1add98e1  tests/Support/AssignmentOrderOriginalIntegrityDatabase.php
5c8f528db01187e27786bb5ce4944adecbac9101a1ac4bc2e8fd51ca96267ba6  tests/Support/AssignmentOrderOriginalIntegrityPersistenceObserver.php
f6dcee7eb6f4722b89e903e37cb755d6fd4f25f9692d1f65c0382c9d29462ac5  tests/Support/AssignmentOrderOriginalIntegrityFreshProcess.php
a1c2ae20ac6443fb2d0d09db9420df446b83d54622cc4ebac69c65f52d77054b  tests/Support/AssignmentOrderOriginalIntegrityFactoryWiring.php
65a28345138929b7b168e030d71f679a7ff71d8195b84d16593d900f73fe2e89  private real-RED evidence.json
```

No changes required. Minimal implementation may proceed for this approved real fresh-reader/factory boundary. Worker safe-log-first wiring and old-fixture patches retain separate gates.
