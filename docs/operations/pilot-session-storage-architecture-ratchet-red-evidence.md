# PILOT-SESSION-STORAGE-001 — architecture ratchet RED

Date: 2026-09-03

Public seam: `tools/architecture/check.py::collect()` through the existing
architecture unittest harness. The two isolated production-shaped fixtures
contain direct native-session ownership/hardcoded compatibility root, unsafe
`chmod/chown` repair and unauthorized `@internal` result-factory calls. Exact
owner/inspector-named fixtures separately require zero findings.

```text
python3 -m unittest \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_native_session_and_hardcoded_root \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_internal_factory_callers

FAIL: expected 8 session_storage_ownership findings, actual 0
FAIL: expected 3 session_storage_ownership findings, actual 0
```

Оба fixture-файла создаются внутри разрешённых production roots, передаются
реальному collector через его существующий file-discovery port и автоматически
удаляются `NamedTemporaryFile`. RED вызван отсутствующим ratchet behavior, а не
setup failure. Production checker этим commit не меняется.
