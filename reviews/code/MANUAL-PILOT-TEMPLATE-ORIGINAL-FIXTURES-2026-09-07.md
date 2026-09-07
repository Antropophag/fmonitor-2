# Независимый review template/original fixtures — 2026-09-07

Reviewer: `/root`, не автор изменений (автор `/root/verify_triage`).
Verdict: **APPROVED** для перечисленных изменений tests/fixture.
Base HEAD: `3d176dd20935397db55d0b624d35162656c82f2b`.

## Exact artifacts

- `app/InstallationProcess/MariaDbAssignmentOrderOriginalVerificationFixture.php`:
  `f5bf41e8bd3415c2b70334e8e47484a7401c55ade034ee87d4bb30b619cd51cb`.
- `tests/AssignmentOrderComposition/template_generation_boundaries_001_test.php`:
  `97d242259f78f66ccb532e41b702dd8512699f7062893361744f36d268ad3e92`.
- `tests/InstallationProcess/assignment_order_original_database_setup_001_test.php`:
  `ba7e45510b10b6e512eae68a509cfdd865bb51493744f45147aeb2fb8aa5072f`.

## Test review

Отсутствующая или некорректная плановая дата не запрещает разрешённый pilot flow
по PRODUCT, pilot spec и `pilot-data-transfer-plan-2026-09-07.md`. Два бывших
refusal случая теперь проверяют реальный результат generate через прежний public
seam: точный envelope, PDF, null/fallback plan date, один clock, полный literal
audit, неизменность остальных domain rows и отсутствие сохранённого template PDF.
Неверный actor/объект, stale composition, ПТО/завершение и renderer failures остаются
отрицательными assertions. Отказ не заменён условным PASS или пропуском.

Database setup test сохраняет exact repeat, SERIALIZABLE contention, drift refusal,
rollback и полное восстановление baseline после cleanup. Новая явная проверка
двух role permissions закрепляет текущую локальную authority fixture.
Исходные RED и команды автора сохранены в
`docs/operations/template-and-original-fixture-alignment-2026-09-07.md`.

## Code review

Ролевые permissions уже входят в validate/families fixture. Их INSERT/DELETE
перенесены внутрь существующей seed/clean транзакции: повтор больше не вставляет
дубликат после успешного no-op, а cleanup не удаляет permissions до проверки drift.
Новых application writes, schema changes, прав реальных пользователей или
изменений разрешённого original workflow нет.

## Независимая проверка

Reviewer запустил оба focused PHP test файла с `PATH=/opt/homebrew/bin:$PATH` и
настроенными disposable test DB credentials (секреты здесь не приводятся).
Оба завершились exit0; template boundary matrix PASS,
`ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK`.
Private logs: `runtime/review-template-boundaries-20260907.log` и
`runtime/review-original-fixture-20260907.log` под manual-pilot state directory.
`git diff --check` PASS. Stand, object966 и global test DB reset не использовались.

Найденных блокирующих замечаний нет. Это не approval полного `make verify`,
архитектурного среза, production integration или CI.
