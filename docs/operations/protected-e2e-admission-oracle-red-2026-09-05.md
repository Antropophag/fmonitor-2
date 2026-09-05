# Admission oracle — Gate 2 RED

Дата: 2026-09-05. Автор test: `/root`.
Base HEAD: `88a6715f0046b0c4b99d6206ffa3de59fe6c5728`.

Gate 1: owner approval в
`owner-e2e-admission-and-pending-selection-approval-2026-09-05-1842Z.md`.
Exact candidate SHA256:
`c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e`.
Public seam: `FMonitor2\Tests\Support\ProtectedE2eAdmissionOracle::matches(string): bool`.

Команда: `php tests/Verification/protected_e2e_admission_oracle_001_test.php`.
Exit: **1**. Полный вывод:

```text
RED_ASSERTION: public ProtectedE2eAdmissionOracle::matches is missing
Expected: true
Actual: false
```

Это intended missing-verifier RED: public class ещё отсутствует; bootstrap
успешен, ошибка — явный assertion, не include error. Oracle implementation
не создан. Literal matrix содержит все mandatory examples revision 3 плюс
derived checks для same-item facts, second anchor, semantic parent, invalid
UTF-8, whitespace, literal punctuation и Unicode boundary. Production renderer
не использован для ожидаемых значений. Никакая missing-production RED не заявлена.

Test SHA256:
`e098a846f8d03eb79c7c77c7dbd8b4c422b7bda757d6a7c20ff56bf66d68b9b9`.
`php -l` PASS; `git diff --check` PASS.
Protected E2E не изменён, SHA256:
`a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6`.

Независимый Gate 3 ещё требуется. Старый real-HTTP mismatch, будущий protected
patch, полный E2E и full verify имеют отдельные gates; этот RED их не заменяет.
