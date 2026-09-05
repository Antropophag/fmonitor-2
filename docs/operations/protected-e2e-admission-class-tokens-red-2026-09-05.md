# Admission oracle — class-token correction RED

Дата: 2026-09-05. Test author: `/root`. Base: `3a63e44670329972f7f9d91c2a41d9e1c6779bc0`.
Independent Gate 5 finding:
`reviews/code/PILOT-E2E-ADMISSION-ORACLE-001-v1.md`, CHANGES_REQUESTED.

Gate 1 наследуется без изменения политики: owner-approved admission revision 3
запрещает HTML class token `shlz-table-wrap` внутри main. ASCII whitespace для
HTML class tokens — TAB/LF/FF/CR/SPACE; Unicode NBSP не является разделителем.
Candidate hash: `c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e`.
Seam: `ProtectedE2eAdmissionOracle::matches(string): bool`.

Добавлен отдельный test `tests/Verification/protected_e2e_admission_class_tokens_001_test.php`.
Исходный approved 28-case test не изменён. Literal positive из spec остаётся true.
Первый negative добавляет только `div` с классом `x\fshlz-table-wrap\fy`.

Команда: `php tests/Verification/protected_e2e_admission_class_tokens_001_test.php`.
Exit **1**. Полный вывод:

```text
RED_ASSERTION: forbidden class token separated by HTML FF
Expected: false
Actual: true
```

Это intended public verifier behavior RED на существующем implementation hash
`7859809e0286d25cb20aa27a0b1537048b4777b3e367a17b4d3c63bc1ab9d5fd`,
а не setup failure. Test также фиксирует остальные четыре ASCII separators
и три near-token positives, включая NBSP. `php -l` и `git diff --check` PASS.
Нужен independent Gate 3 перед correction; никакой protected patch не применён.
