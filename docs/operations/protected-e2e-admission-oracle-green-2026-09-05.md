# Admission oracle — Gate 4 GREEN и отдельное HTTP mismatch evidence

Дата: 2026-09-05. Автор implementation: `/root`.
Gate 3: `reviews/tests/PILOT-E2E-ADMISSION-ORACLE-001-v1.md`, APPROVED.
Implementation base: `fac9f59` (полный SHA доступен в git).

Добавлен только test-support public oracle из exact owner-approved candidate
`c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e`.
DOM parsing — UTF-8, LIBXML_NONET, diagnostics suppressed; проверяются sole main,
semantic membership, canonical anchor, exact text projection/fact boundaries и
запрет table/wrapper. Нет DB/network вызовов или записи файлов в oracle.

```text
php tests/Verification/protected_e2e_admission_oracle_001_test.php
PROTECTED_E2E_ADMISSION_ORACLE_OK cases=28
```

Exit 0; approved test bytes не менялись.
`php -l tests/Support/ProtectedE2eAdmissionOracle.php`: PASS.
`make architecture-check`: PASS, 7 rules. `git diff --check`: PASS.

```text
7859809e0286d25cb20aa27a0b1537048b4777b3e367a17b4d3c63bc1ab9d5fd  tests/Support/ProtectedE2eAdmissionOracle.php
e098a846f8d03eb79c7c77c7dbd8b4c422b7bda757d6a7c20ff56bf66d68b9b9  tests/Verification/protected_e2e_admission_oracle_001_test.php
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

## Свежий unchanged protected E2E

На base `41247c5a5911f34f632df89c3af0d027bd5f2c36` исполнен неизменённый
protected verifier через private diagnostic wrapper `capture.php`. Wrapper
делает `require` исходного файла и в `finally` сохраняет только уже полученный
fictional HTTP body/status после выполнения verifier cleanup. Он не ловит и не
подавляет Throwable, не меняет expectations, не останавливает downstream run.

Private primary archive:
`/Users/antropophag/.local/state/fmonitor2-verification/admission-20260905-w7wmv_ye`.
Command: `php <archive>/capture.php > <archive>/unchanged-e2e.log 2>&1`.
Exit **255**; HTTP status **200**. Original failure:

```text
Uncaught TestFailure: actor18 admission reaches one exact canonical object link
Expected: 1
Actual: 0
```

Stack points to unchanged protected line 153. Его finally cleanup исполнялся
нормально; никакого success/skip conversion нет. Затем независимый от renderer
approved literal oracle применён к сохранённому actual body:
`php <archive>/check-response.php`, exit 0,
`REAL_HTTP_ADMISSION_SEMANTIC_LIST_OK`.
Это fixture/oracle mismatch evidence, не missing-production RED и не E2E GREEN.

```text
6cd9638a470728bd069bd6481ceb2dd16e15040e0267f8e2b032581d34e7776b  capture.php
021076c1ae9d02f8b5fc8e22c0c4c6e68ee246bbbd17bb736bb775f855a15b44  admission.html
0fab206b0b6df958be90d7956892c3d5c2c783bd83dd6c3efbf9501ab69b7a5f  unchanged-e2e.log
```

Gate 5 oracle ещё требуется. Protected assertion patch не применён.
Full E2E/verify downstream failures, original-first delivery, CI и clean launch
остаются отдельными обязательствами; VERIFY_OK не получен.
