# PILOT-SESSION-STORAGE-001 — exact-owner impersonation RED v2

Date: 2026-09-03

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
c720c7040f1618e870e0a7ad4fe036a5ac802e17e5c9d8cd12d8a2dbbe53f155  tools/architecture/tests/test_debt_fingerprint.py
```

The corrected pair exercises the repository collector through its public test
seam. The negative fixture owns temporary impersonating basenames below
`rapid-pilot/` and contains exactly three forbidden internal-factory calls. The
positive control passes the real, unmodified canonical files
`app/IdentityAccess/FilesystemPilotSessionStorage.php` and
`app/IdentityAccess/PilotSessionStorageInspector.php` to the same collector.

```text
python3 -m unittest \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_owner_basename_impersonation \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_allows_exact_internal_factory_owners

F.
AssertionError: 3 != 0
Ran 2 tests
FAILED (failures=1)
exit=1
```

Thus the canonical exact owners are accepted while the three outside calls are
incorrectly accepted by the current basename-only implementation. Temporary
fixture cleanup completed, and no production file was changed. Prior evidence
and the v4 `CHANGES_REQUESTED` review remain append-only history.
