## 1. Gate 1 — executable schema contract

- [x] 1.1 Obtain explicit owner approval of `CHECKLIST-TEMPLATE-SCHEMA-001` after strict OpenSpec validation and a fresh independent technical Gate 1 verdict `READY_FOR_OWNER_APPROVAL`; the contract fixes literal v7 after exact landed catalogue v1–v6 and inherits the approved MariaDB UCA-alias normalization.

## 2. Gates 2–3 — RED and independent test review

- [x] 2.1 A dedicated RED agent adds a MariaDB migration/runner test; RED must prove missing canonical ownership, not setup failure.
- [x] 2.2 A fresh independent reviewer records approval in `reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md` with hashes and RED evidence.

## 3. Gate 4 — minimal implementation

- [x] 3.1 Implement and register the strict family migration as literal v7 immediately after canonical v1–v6.
- [x] 3.2 Remove both runtime creates and replace them with fail-closed schema preconditions.
- [x] 3.3 Run focused migration, importer/linker, binding, architecture and regression verification.

## 4. Gate 5 and Done

- [x] 4.1 A fresh independent code reviewer records approval in `reviews/code/CHECKLIST-TEMPLATE-SCHEMA-001.md`.
- [x] 4.2 Done: canonical migration owns both exact tables, runtime paths issue no DDL, data behavior is unchanged, and all required gates are approved.
