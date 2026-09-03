# Independent Gate 3 test rereview v3 — PILOT-SESSION-STORAGE-001 architecture ratchet

- Date: 2026-09-03
- Reviewer: separately tasked agent `/root/session_arch_rereview_v3`
- Test author: not this reviewer
- Prior reviewers: separately tasked agents `/root/session_arch_test_review` and
  `/root/session_arch_rereview`; this reviewer is distinct from the test author
  and both prior reviewers
- Reviewed commit: `98e3741e1d6255e0607ba71f330497a092318b2b`
- Controlling contract: owner-approved `PILOT-SESSION-STORAGE-001` v9,
  sections 7–8, and OpenSpec task 3.3
- Public seam: `tools/architecture/check.py::collect()` through the existing
  architecture unittest harness
- Verdict: **APPROVED**

The reviewer did not author or edit the reviewed tests or production checker.
This append-only review record is the reviewer's only change.

## Review

All findings from the v2 rereview are closed.

- The prohibited-production fixture contains five native PHP session lifecycle
  calls, one separately detectable hardcoded compatibility root, and unsafe
  `chmod` and `chown` repair calls. The independently derived expected
  `session_storage_ownership` cardinality is exactly eight.
- The unauthorized internal-factory fixture contains exactly one operation
  result factory, one filesystem-event factory and one inspection-result
  factory. Its independently derived expected cardinality is exactly three.
- The positive fixture uses the exact allowed basenames and now covers the
  complete contract matrix: all eight operation-result factories
  (`ownerStarted`, `ownerWriteCommitted`, `ownerRegenerated`, `ownerDestroyed`,
  `ownerClosed`, `ownerNotFound`, `ownerInvalid`, `ownerUnavailable`), both event
  factories (`ownerBefore`, `ownerAfter`), and both inspector factories
  (`inspectorOk`, `inspectorUnavailable`). It requires zero findings.
- Negative and positive fixtures are isolated under production-shaped roots,
  passed to the real collector through its existing file-discovery port and
  removed automatically. They neither touch production systems nor retain
  repository fixtures.
- The RED evidence command now names all three tests, including the complete
  zero-findings allowed-owner matrix.

The suite is sensitive in both directions: the two negative tests require the
missing ratchet to report exact independently determined findings, while the
positive test prevents an implementation from rejecting any contract-authorized
factory call at its exact owner basename. The current production collector has
no `session_storage_ownership` rule, so the observed failures are the intended
missing-behavior RED rather than setup failures.

## RED replay

Command:

```text
python3 -m unittest \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_native_session_and_hardcoded_root \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_internal_factory_callers \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_allows_exact_internal_factory_owners
```

Observed at the reviewed commit: exit `1`, `Ran 3 tests`; the first negative
test fails `expected 8, actual 0`, the second fails `expected 3, actual 0`, and
the complete exact-owner positive test passes with zero findings.

Gate 3 is **APPROVED**. Task 3.3 may advance to minimal GREEN without changing
the approved expectations.

## Exact reviewed hashes

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
7d923592045c1e5cb4201d99b0387eaadfb1264443e0ba52ce170d060ea31d15  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
ed862d7df6b4fc8b63cb9aad9f89f11f9d087143d2d21048672ca3192866259d  openspec/changes/define-pilot-session-storage-contract/tasks.md
4355cf44e6748cc9940d2664b4a8736c976a1fa2f3d44b892a9426a15642948b  tools/architecture/tests/test_debt_fingerprint.py
e26a3211adde786a89ac15bd7a4fbe8dbdc058f2c56f9cc9c2cd071b985e0e0c  docs/operations/pilot-session-storage-architecture-ratchet-red-evidence.md
```
