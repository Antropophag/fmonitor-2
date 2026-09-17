# Текущая цель — №181, local focused / exact-source CI placement

Поручение владельца 2026-09-17 заменяет прежнюю текущую цель №183: реализовать [№181](https://github.com/Antropophag/fmonitor-2/issues/181) одним отдельным PR от актуального `main` после merge №187 и довести до PR-ready. Исключение №181 из объёма завершённой №183 не запрещает это поручение; разовые разрешения №183 не наследуются.

Локальный focused сохраняет acceptance/regression, изменённые зарегистрированные tests, непосредственные boundary checks и известные transitive consumer verifiers. Только общая integration closure, выбранная одним консервативным semantic fallback, переносится в обязательный exact-source CI. При нескольких основаниях local побеждает. Reviewer видит local evidence и CI-pending obligations без фиктивного evidence.

Не входят product code и повтор #49, расширение FAST, новые planner/registry/admission, изменение ролей/review rules, №182, №141, №107/T07a и остальные части №153. Полный старый local benchmark и локальные `make test`/`make verify` запрещены. Merge/deploy/settings не выполнять.

Контракт: [CHANGE-VERIFICATION-PLACEMENT-181](../../specs/CHANGE-VERIFICATION-PLACEMENT-181.md). Lifecycle: [separate-local-focused-from-ci-integration](../../openspec/changes/separate-local-focused-from-ci-integration/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Сравнение использует поставленный состав №187 и `tests/fixtures/delivery/issue-49-delivery.json`; итог фиксирует before/after local команды, неизменные CI obligations и реальный bounded run.
