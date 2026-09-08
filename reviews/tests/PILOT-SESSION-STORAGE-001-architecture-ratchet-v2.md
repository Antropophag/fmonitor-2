# Independent Gate 3 test rereview — PILOT-SESSION-STORAGE-001 architecture ratchet

- Date: 2026-09-03
- Reviewer: separately tasked agent `/root/session_arch_rereview`
- Test author: not this reviewer
- Prior reviewer: separately tasked agent `/root/session_arch_test_review`; this
  reviewer is distinct from both the test author and prior reviewer
- Reviewed commit: `0f657f46375cd0560b286b61ed460c10ac1cae2a`
- Controlling contract: owner-approved `PILOT-SESSION-STORAGE-001` v9,
  sections 7–8, and OpenSpec task 3.3
- Public seam: `tools/architecture/check.py::collect()` through the existing
  architecture unittest harness
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the reviewed tests or production checker.
This append-only review record is the reviewer's only change.

## Findings

### 1. The unsafe-repair sensitivity gap is resolved

The first isolated production-shaped fixture now contains the five prohibited
native session lifecycle calls, one hardcoded compatibility root occurrence
reported in addition to its native `session_save_path` call, and the two unsafe
repair primitives `chmod` and `chown`. Its independently derived expected
`session_storage_ownership` cardinality is therefore exactly eight. This covers
the unsafe-repair prohibition in section 1 and task 3.3.

### 2. The exact allowed-owner matrix remains incomplete

The positive fixture correctly requires zero findings for three allowed calls:
`ownerStarted` and `ownerBefore` in exact basename
`FilesystemPilotSessionStorage.php`, and `inspectorOk` in exact basename
`PilotSessionStorageInspector.php`. It does not exercise `ownerAfter`,
`inspectorUnavailable`, or the other seven operation-result factories
(`ownerWriteCommitted`, `ownerRegenerated`, `ownerDestroyed`, `ownerClosed`,
`ownerNotFound`, `ownerInvalid`, and `ownerUnavailable`).

Consequently, a checker that over-broadly rejects any of those unexercised
factories even in their exact permitted owner/inspector class would pass the
reviewed suite while violating the exhaustive section 8 public construction
contract. The prior review explicitly required both event phases, both
inspection factories, and an equivalently complete exact-owner matrix. That
blocking mutation-sensitivity gap is not resolved.

### 3. Negative cardinality and isolation are sound

The unauthorized-call fixture has exactly three calls—one operation-result
factory, one event factory, and one inspection-result factory—and therefore an
independently derived expected cardinality of three. The temporary negative
fixtures remain under production roots and patch the collector discovery port
to the exact fixture. The positive fixtures live in a temporary directory under
the production `app/IdentityAccess` root, use the exact permitted basenames,
patch discovery to exactly those two paths, and are removed on context exit.
No production systems or persistent repository fixtures are involved.

The RED evidence document also says that exact owner/inspector fixtures require
zero findings, but its recorded command lists only the two negative tests. It
therefore does not itself record execution of the new positive zero-findings
test. The fresh replay below covers all three tests.

## RED replay

Command:

```text
python3 -m unittest \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_native_session_and_hardcoded_root \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_internal_factory_callers \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_allows_exact_internal_factory_owners
```

Observed at the reviewed commit: `Ran 3 tests`; the two negative tests fail with
`expected 8, actual 0` and `expected 3, actual 0`; the positive exact-owner test
passes with zero findings. The negative failures are the intended
missing-ratchet RED, not setup failures.

## Required changes

Extend the isolated exact-owner/inspector zero-findings fixture to exercise all
eight `PilotSessionOperationResult` owner factories, both
`PilotSessionFilesystemEvent` phases, and both `PilotSessionInspectionResult`
factories. Record the positive test in the RED evidence replay command. Then
request another fresh independent Gate 3 review before Gate 4.

## Exact reviewed hashes

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
7d923592045c1e5cb4201d99b0387eaadfb1264443e0ba52ce170d060ea31d15  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
ed862d7df6b4fc8b63cb9aad9f89f11f9d087143d2d21048672ca3192866259d  openspec/changes/define-pilot-session-storage-contract/tasks.md
f371b2c61f8c5f2ef61a97c4b74787b1f227f04cb8365ace2349c2048dcbb60e  tools/architecture/tests/test_debt_fingerprint.py
1e0e97a98c4df8aa0ff88045ad417eb85cba607d5b31860e6aad57e6a8a1152f  docs/operations/pilot-session-storage-architecture-ratchet-red-evidence.md
```
