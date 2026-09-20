## 1. Contract и Gate 2

- [x] 1.1 Создать `specs/ORDINARY-CHANGE-PROCESS-001.md` как единственный нормативный acceptance source и проверить его трассировку ко всем сценариям OpenSpec delta.
- [x] 1.2 Создать `verification-input.json`, обновить current delivery goal и выполнить `harness.py prepare` для чувствительной policy-доработки; проверить выбранные Gate 3 + final и полный перечень obligations до написания тестов.
- [x] 1.3 Написать RED regressions для трёх обычных классов из разных модулей, независимости ceremony/CI, FAST presentation closure, sensitive mixed/post-prepare diff и fail-closed checks; проверить intended RED адресными командами.
- [x] 1.4 Материализовать replay fixtures #187/#194/#209 и unseen example без использования их identifiers в policy; проверить factual diff/class/risk/preserved-check assessment и независимую ожидаемую матрицу без заявления о повторном historical lifecycle.
- [x] 1.5 Подготовить Gate 3 reviewer package с полной RED evidence и получить независимый APPROVED verdict до реализации.

## 2. Реализация существующих seams

- [x] 2.1 Расширить existing compact lifecycle declaration на `PRESENTATION`, `READ`, `APPLICATION_TEST_OR_REFACTOR`; проверить одного автора + один final для ordinary и Gate 3 + final для sensitive/unknown.
- [x] 2.2 Отделить `required_reviews` от FAST/FULL CI selection в planner/harness result; проверить ordinary FULL CI без Gate 3.
- [x] 2.3 Расширить existing presentation ownership/oracle closure на связанные changed tests, consumers и environment checks; проверить FAST при полном mapping и FULL при неполном.
- [x] 2.4 Добавить diff-aware mixed-file и post-prepare sensitive detection, используя существующие sensitive boundaries/method signals; проверить stale/reprepare escalation.
- [x] 2.5 Сохранить fail-closed admission для missing/failed/cancelled/incomplete/unknown mandatory checks; проверить отсутствие общего GREEN.

## 3. Сквозная проверка и публикация

- [x] 3.1 Прогнать bounded маршрут `prepare → focused → reviewer package → CI selection` на всех acceptance fixtures и применимый architecture check; не запускать локальный полный suite.
- [x] 3.2 Обновить process/harness docs и delivery record с классами до/после, reviews/artifacts, выбранными checks, отрицательными сценариями и ограничениями; token usage оставить `UNKNOWN`.
- [x] 3.3 Подготовить exact-source final reviewer package и получить независимый APPROVED либо исправить findings с regression и повторным delta-review.
- [ ] 3.4 Commit/push отдельную ветку, открыть один PR от актуального `origin/main`, запустить ровно один выбранный exact-source CI и проверить полный failed-job/`REGRESSION_FAILURE` inventory до PR-ready verdict.

## Done definition

- [ ] D.1 Все cases A–L проходят через public route; Gate 3 и final APPROVED на применимых exact sources; focused checks и один selected exact-source CI GREEN; отдельный PR открыт от актуального `origin/main`; UNKNOWN, deferred gates и ограничения явно записаны.
