# Admission oracle — class-token correction GREEN

Дата: 2026-09-05. Implementation author: `/root`.
Gate 3: `reviews/tests/PILOT-E2E-ADMISSION-CLASS-TOKENS-001-v1.md`, APPROVED,
review commit `d59aacf`. Код изменён только после этого review.

XPath whitespace normalization для class заменена на разделение exact HTML
ASCII TAB/LF/FF/CR/SPACE. Near-token и NBSP остаются допустимыми. Table check,
остальной oracle и оба одобренных test files не менялись.

Verification (все exit 0):

```text
php tests/Verification/protected_e2e_admission_class_tokens_001_test.php
PROTECTED_E2E_ADMISSION_CLASS_TOKENS_OK cases=9
php tests/Verification/protected_e2e_admission_oracle_001_test.php
PROTECTED_E2E_ADMISSION_ORACLE_OK cases=28
php /Users/antropophag/.local/state/fmonitor2-verification/admission-20260905-w7wmv_ye/check-response.php
REAL_HTTP_ADMISSION_SEMANTIC_LIST_OK
```

`php -l tests/Support/ProtectedE2eAdmissionOracle.php` PASS; `git diff --check` PASS.
Architecture boundary не менялась после PASS 7 rules в prior oracle GREEN.

```text
496b11c87227abec92c56a977c95dc7935dd81b38e7de28512af10b3be6c1ef2  tests/Support/ProtectedE2eAdmissionOracle.php
0b05d0fa48e256a4584f0937285c892598a4a23110d7e60f9bb921560cf44049  tests/Verification/protected_e2e_admission_class_tokens_001_test.php
e098a846f8d03eb79c7c77c7dbd8b4c422b7bda757d6a7c20ff56bf66d68b9b9  tests/Verification/protected_e2e_admission_oracle_001_test.php
```

Combined oracle Gate 5 rereview нужен до protected patch apply. Сам patch имеет
distinct Gate 3 APPROVED с этим явным prerequisite. Full E2E/verify и launch
готовность не заявлены.
