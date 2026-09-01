## 1. Gate 1 — executable schema contract

- [x] 1.1 Получить fresh independent Gate 1 review executable spec `INSPECTION-EVIDENCE-SCHEMA-001` с exact final fingerprints, двумя разрешёнными predecessor forms, family preflight, inherited UCA-alias normalization, literal v8 после exact landed v1–v7 и runtime-no-DDL outcomes; verification: review manifest фиксирует hashes и verdict `READY_FOR_OWNER_APPROVAL`.
- [x] 1.2 Получить явное owner approval reviewed executable spec до test edits; verification: владелец ответил `ок` 2026-09-01 для exact reviewed artifact из commit `308145d`.

## 2. Gates 2–3 — RED и independent test review

- [x] 2.1 Свежий RED-agent добавляет dedicated MariaDB schema/runner test для clean, repeat, both upgrades, compatible partial, incompatible family, prefix isolation и no-runtime-DDL; verification: RED доказывает отсутствие canonical ownership, а не setup failure.
- [x] 2.2 Свежий independent reviewer проверяет expected fingerprints против approved schema evidence и записывает `APPROVED` в `reviews/tests/INSPECTION-EVIDENCE-SCHEMA-001.md`; verification: spec/test hashes и RED transcript зафиксированы.

## 3. Gate 4 — minimal ownership implementation

- [x] 3.1 Реализовать strict four-table migration с family-wide preflight и зарегистрировать её после landed prerequisites; verification: focused clean/repeat/partial/conflict runner cases GREEN без изменения reviewed expectations.
- [x] 3.2 Реализовать два exact additive upgrades с сохранением sentinel rows и auto-increment state; verification: template identity columns и `assignment_source` появляются, legacy evidence byte-equivalent.
- [x] 3.3 Удалить production DDL и schema repair из `ChecklistSync`, оставить fail-closed precondition; verification: static/runtime no-DDL cases GREEN и architecture debt уменьшается без новых violations.
- [x] 3.4 Прогнать current-crew, template binding, offline/prefetch, completion и native OTIZ characterization; verification: observable checklist/downstream behavior остаётся GREEN.

## 4. Gate 5 и Done

- [x] 4.1 Выполнить focused DB, canonical runner, `make architecture-check`, lint и full `make verify`; verification: v8 focused/runner/architecture/lint GREEN, full verify вернулся к baseline 8 DB + 1 E2E failures, characterization PASS.
- [x] 4.2 Свежий independent code reviewer проверяет spec, reviewed RED, migration/runner/runtime diff, debt reduction и regression и записывает `APPROVED` в `reviews/code/INSPECTION-EVIDENCE-SCHEMA-001.md`.
- [x] 4.3 Done: canonical runner единолично создаёт/обновляет exact family; runtime не выполняет её DDL; clean/repeat/upgrade/conflict/characterization проходят; обе independent reviews approved; calibration dependency READY.
