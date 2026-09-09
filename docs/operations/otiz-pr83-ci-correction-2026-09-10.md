# PR83 — полная диагностика первого CI

Run34412982874, source39df0ffb: plan/fast/unit/governance/e2e/integration1 — SUCCESS.
В полном логе integration2 ровно один REGRESSION_FAILURE:
`rapid-pilot/verify-calendar-projections.php`, exit255, ожидание terminal v23.
Итоговый verify отклонён именно из-за этой failed-категории; иных первичных
падений нет. Логи сохранены приватно в `/tmp/fm2-pr83-integration2.log` и
`/tmp/fm2-pr83-verify.log`.

Root исправил только текущую версию фикстуры и дополнил точный ordered list
миграцией24 в f748c3f4. Все проверки календарных данных/DOM/overflow сохранены.
Изолированный targeted запуск с явными тестовыми DB-параметрами — PASS.

Перегенерация обязательного плана затем выявила дефект планировщика: он отвергает
уже зарегистрированный legacy verifier, поскольку тот расположен вне tests/.
Действующий CHANGE-VERIFICATION-001 требует учитывать каждый зарегистрированный
effective test, поэтому ограничение должно различать доверенный inventory и
произвольный незарегистрированный скрипт, а не исключать проверку календаря.

Root-authored public-CLI test:
`test_registered_legacy_verifier_is_required_but_arbitrary_script_is_rejected`.
RED exit1: `test path outside tests: rapid-pilot/verify-calendar-projections.php`.
Тест требует ровно одну команду зарегистрированного verifier и сохраняет отказ
для произвольного незарегистрированного PHP-скрипта вне tests/.

Для этого ограниченного corrective delta план подготовлен относительно f748c3f4
через `.local/verification/legacy-verifier-input.json` и
`.local/verification/legacy-verifier-plan.json` до написания RED; это позволяет
проверить исправление самого планировщика без обхода ошибки полного PR-плана.
Production-изменение планировщика выполняет отдельный агент после Gate3.
Повторный полный CI потребуется для исправленного exact head; предыдущий run
не объявляется GREEN и не удаляется из истории.
