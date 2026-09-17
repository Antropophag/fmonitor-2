# CHANGE-VERIFICATION-PLACEMENT-181

## Простыми словами

Локальная проверка больше не запускает всю integration-категорию только из-за консервативной semantic closure. Полный обязательный набор остаётся в exact-source CI, а reviewer видит честное разделение выполненного локально и ожидаемого от CI.

## Public seam

Actor: delivery root/executor/reviewer и Quality Graph CI. Seam: существующие `change-verification.py plan/check/run`, `harness.py prepare` и существующий CI selection/aggregate. Source oracle: issue #181 и поставленный #153 Slice A. Release value: убрать дорогой local→CI дубль без потери admission.

## Normative requirements

1. Planner MUST сохранять единый список обязательных команд и явно назначать каждой место исполнения.
2. Acceptance/regression mappings, изменённые зарегистрированные tests, непосредственные boundary checks и известные transitive consumer verifiers MUST исполняться локально.
3. Integration verifier, выбранный только основанием `semantic integration closure`, MUST ожидаться в exact-source CI и MUST NOT требовать локального evidence.
4. Если одинаковый argv выбран несколькими основаниями и хотя бы одно требует local focused, команда MUST оставаться локальной, исполняться один раз на уровне и сохранять все основания выбора.
5. Reviewer package MUST различать локально выполненные obligations и CI-pending obligations; отсутствие локального evidence для CI-only obligation MUST NOT подделываться и MUST NOT блокировать package.
6. Обязательный exact-source CI MUST сохранять полный прежний состав категорий. Missing, unexpected skip, failure или cancellation обязательного CI MUST сохранять общий non-success через существующий aggregate.
7. Сломанный local direct/consumer verifier MUST обнаруживаться bounded focused run. Сломанный CI-only consumer MUST обнаруживаться итоговой CI-проверкой и не может маскироваться локальным GREEN.
8. FAST classifier, review roles/rules, registries, admission state machine и product code MUST оставаться неизменными.

## Acceptance examples

- На delivery input поставленного #187 planner до изменения назначает всю semantic integration closure локально; после изменения локальный список содержит только проверки с local основаниями, а CI obligations совпадают с прежними.
- Если integration verifier одновременно является acceptance или known consumer, он остаётся local и не дублируется в списке команд одного уровня.
- Prepare fail-closed для missing verifier/conflicting ownership/invalid policy остаётся прежним.

## Non-goals

#49, #182, #141, #107/T07a, остальные части #153, новый planner/registry/admission, merge/deploy/settings и локальный full suite не входят.
