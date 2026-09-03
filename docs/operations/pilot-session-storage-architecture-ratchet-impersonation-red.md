# PILOT-SESSION-STORAGE-001 — exact-owner impersonation RED

Date: 2026-09-03

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
5ecfc8f947b43cdae0b3625a45d564940605077d2a57d332c3e95412db340a89  tools/architecture/tests/test_debt_fingerprint.py
```

Public seam: `tools.architecture.check.collect()` through the repository
architecture unittest harness.

Command and observed result:

```text
python3 -m unittest tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_owner_basename_impersonation
AssertionError: 3 != 0
Ran 1 test
FAILED (failures=1)
exit=1
```

The fixture creates two production-shaped files only beneath an owned temporary
directory under `rapid-pilot/`. Their basenames impersonate the two approved
IdentityAccess owners and invoke exactly three internal factories. The expected
cardinality is independently determined from those three calls. The current
collector reports zero because it compares only `path.name`; setup and cleanup
complete normally.

No checker or other production file changed for this RED. The prior Gate 5
`CHANGES_REQUESTED` record remains immutable.
