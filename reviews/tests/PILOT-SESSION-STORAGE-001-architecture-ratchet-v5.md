# Independent Gate 3 test rereview v5 — PILOT-SESSION-STORAGE-001 exact-owner impersonation

- Date: 2026-09-03
- Reviewer: separately tasked agent `/root/session_ratchet_gate3_v5`
- Test author: not this reviewer
- Reviewed commit: `8bbe063924f27ed5ed07e26b8f5757de7bcb761c`
- Controlling contract: owner-approved `PILOT-SESSION-STORAGE-001`, sections
  7–8, OpenSpec task 3.3, and immutable Gate 5 return record
  `reviews/code/PILOT-SESSION-STORAGE-001-architecture-ratchet-v1.md`
- Public seam: `tools.architecture.check.collect()` through the repository
  architecture unittest harness
- Verdict: **APPROVED**

The reviewer did not author or edit the specification, tests, production
checker, or RED evidence. This append-only review record is the reviewer's only
change.

## Review

The corrected test pair resolves v4 finding T1 without introducing a
test-only owner exception. The positive control passes the real, unmodified
canonical files at the two exact repository paths required by the contract:

```text
app/IdentityAccess/FilesystemPilotSessionStorage.php
app/IdentityAccess/PilotSessionStorageInspector.php
```

Both files are asserted to exist and are presented directly to the public
collector seam. Their zero-finding result therefore proves that the real
canonical operation/event owner and inspector remain accepted; a nested
temporary directory with an approved basename is no longer used as positive
authority.

The negative fixture remains sensitive to the Gate 5 defect. It creates those
same two approved basenames under a temporary directory below `rapid-pilot/`,
outside both exact owners, then invokes exactly three independently countable
restricted factories: one operation-result owner factory, one filesystem-event
owner factory, and one inspection-result inspector factory. The expectation of
exactly three `session_storage_ownership` findings follows from those three
call sites and not from the checker implementation.

The pair is deterministic and isolated. `TemporaryDirectory(dir=rapid-pilot)`
owns the negative files, both writes complete before collection, and cleanup
occurs before the cardinality assertion. Before and after reproduction there
were no `rapid-pilot/tmp*` child directories. Git status was clean, and hashes
of both canonical files were identical before and after the run.

The observed result is an honest RED for the intended basename-only production
defect: the negative expectation receives zero findings, while the corrected
canonical positive control passes. Setup, discovery and the positive owner
matrix are therefore not the reason for failure. A minimal GREEN may now
compare normalized exact repository paths; it must not add a temporary-path or
basename exception.

## Independent RED reproduction

At exact reviewed SHA `8bbe063924f27ed5ed07e26b8f5757de7bcb761c`, from
`2026-09-03T22:00:34+03:00` through `2026-09-03T22:00:35+03:00`:

```text
python3 -m unittest \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_owner_basename_impersonation \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_allows_exact_internal_factory_owners

F.
AssertionError: 3 != 0
Ran 2 tests in 0.178s
FAILED (failures=1)
exit=1
```

The first test is the sole failure; the second test is the passing dot.
Temporary-directory inventories before and after were empty. Canonical hashes
before and after were respectively:

```text
5049875a12dc0cc4e3418d9440a3f23c1817df39778fa675651053457e2349ce  app/IdentityAccess/FilesystemPilotSessionStorage.php
1fc73d68a3c3175f025c2841dafce500a4b2423a763e49bb8d160d2c38016b10  app/IdentityAccess/PilotSessionStorageInspector.php
```

## Exact reviewed hashes

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
7d923592045c1e5cb4201d99b0387eaadfb1264443e0ba52ce170d060ea31d15  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
ed862d7df6b4fc8b63cb9aad9f89f11f9d087143d2d21048672ca3192866259d  openspec/changes/define-pilot-session-storage-contract/tasks.md
c720c7040f1618e870e0a7ad4fe036a5ac802e17e5c9d8cd12d8a2dbbe53f155  tools/architecture/tests/test_debt_fingerprint.py
5f68bc7f69e49e86da79af1cafcaf6fa45eb7d0e11b426f9652c8fe6fa67ff85  docs/operations/pilot-session-storage-architecture-ratchet-impersonation-red-v2.md
81b3ebc2d11be03c6be221cec54ad7cc43e1c0335025b41cc0b8e5207c4e68c4  tools/architecture/check.py
5049875a12dc0cc4e3418d9440a3f23c1817df39778fa675651053457e2349ce  app/IdentityAccess/FilesystemPilotSessionStorage.php
1fc73d68a3c3175f025c2841dafce500a4b2423a763e49bb8d160d2c38016b10  app/IdentityAccess/PilotSessionStorageInspector.php
```

## Gate consequence

Gate 3 is **APPROVED** for the exact-owner impersonation RED at the reviewed
commit and hashes above. Minimal Gate 4 production work is authorized. The
prior Gate 5 `CHANGES_REQUESTED` record remains immutable and unresolved until
the corrected checker, focused regressions, and a fresh independent Gate 5
review are complete.
