# Independent Gate 5 code review — PILOT-SESSION-STORAGE-001 architecture ratchet

- Date: 2026-09-03
- Reviewer: separately tasked agent `/root/session_ratchet_gate5`
- Implementation author: not this reviewer
- Reviewed implementation commit: `e7ddd8d3887d088736e74f80070f12a6382e0538`
- Approved Gate 3: `reviews/tests/PILOT-SESSION-STORAGE-001-architecture-ratchet-v3.md`
- Controlling contract: owner-approved `PILOT-SESSION-STORAGE-001` ownership
  rules inherited from the v10 package, especially sections 7–8, plus OpenSpec
  task 3.3
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the specification, tests or implementation.
This append-only review record is the reviewer's only change.

## Standards

No baseline update, ownership exception or new product behavior is present. The
collector change is small and remains inside the canonical architecture checker.
The focused suite is green, and the checker correctly exposes the 13 existing
native-session consumer violations without treating them as baseline debt. Those
violations intentionally keep the whole session slice non-GREEN and do not by
themselves block approval of this ratchet sub-slice.

One blocking boundary defect remains:

### S1 — basename-only factory allowlist grants authority outside the real owner

`tools/architecture/check.py` authorizes internal factory calls when
`path.name` equals `FilesystemPilotSessionStorage.php` or
`PilotSessionStorageInspector.php`. It does not verify the exact repository
path. Therefore any production file under another module, including
`rapid-pilot/FilesystemPilotSessionStorage.php`, can invoke operation/event
factories and receive zero `session_storage_ownership` findings.

This contradicts the normative rule that only the concrete real owner or real
inspector may call these factories. A filename is not an ownership boundary.
Compare exact normalized paths (for example
`app/IdentityAccess/FilesystemPilotSessionStorage.php` and
`app/IdentityAccess/PilotSessionStorageInspector.php`) rather than basenames.

Review reproduction through the real collector:

```text
fixture: rapid-pilot/<temporary>/FilesystemPilotSessionStorage.php
calls: PilotSessionOperationResult::ownerStarted(...)
       PilotSessionFilesystemEvent::ownerBefore(...)
outside_owner_findings= []
```

## Spec and test sensitivity

The approved three tests correctly cover eight prohibited native/root/repair
findings, three unauthorized factory findings, and all twelve allowed factories
in owner-named files. However, the positive fixture proves only basename
acceptance and does not distinguish the real owner path from an impersonating
path. The current implementation therefore passes the approved tests while
violating their controlling ownership invariant.

Gate 5 findings that require a test change restart at Gate 2. Add a negative
fixture with an allowed basename outside the exact owner module (and the
corresponding inspector case), capture the intended RED against `e7ddd8d`, and
obtain a fresh independent Gate 3 approval before correcting the allowlist.

## Verification evidence

At exact SHA `e7ddd8d3887d088736e74f80070f12a6382e0538`:

```text
python3 -m unittest \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_native_session_and_hardcoded_root \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_internal_factory_callers \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_allows_exact_internal_factory_owners
Ran 3 tests
OK

python3 -m unittest tools.architecture.tests.test_debt_fingerprint
Ran 21 tests
OK

make architecture-check
ARCHITECTURE CHECK FAILED
# exactly 13 session_storage_ownership findings in PilotE2ECoordinator.php,
# LocalAuth.php and UserAccessView.php; no other architecture finding

git diff --check e7ddd8d^ e7ddd8d
# exit 0, no output
```

Reviewed Gate 3 hashes still match the recorded manifest. Implementation checker
SHA-256 is:

```text
81b3ebc2d11be03c6be221cec54ad7cc43e1c0335025b41cc0b8e5207c4e68c4  tools/architecture/check.py
```

## Gate consequence

**CHANGES_REQUESTED.** Do not claim Gate 5 or GREEN for this ratchet sub-slice.
The 13 exposed consumer violations remain expected follow-on work, but S1 must
first return through RED and a fresh independent Gate 3 review.
