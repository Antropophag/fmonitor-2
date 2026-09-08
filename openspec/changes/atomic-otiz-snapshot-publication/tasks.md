## 1. Contract and RED

- [x] 1.1 Оформить нормативную `specs/OTIZ-SNAPSHOT-PUBLICATION-001.md`: confirmed application seam, точные outcomes и независимые числовые примеры из одобренных продуктовых правил; сверить с delta и ADR0002. Не считать старую DRAFT characterization утверждением новых правил.
- [x] 1.2 Подтвердить A01 через реальный login/CSRF/POST calculate и POST accept на изолированной БД: контролируемый отказ чтения native inputs после header оставляет принимаемый draft в старом коде. Сохранить команду, exact source и ошибку требуемого assertion в `reviews/tests/OTIZ-SNAPSHOT-PUBLICATION-001.md`; SETUP_FAILURE не является RED.
- [x] 1.3 Добавить focused tests публичной новой операции: успешная полная публикация, отказ после первого объекта/перед commit, наблюдение вторым соединением и изменение соседних входных фактов во время build (один consistent cut), проверка digest по DB rows, replay/conflict, incomplete acceptance и отказ в доступе. Expected rows/counts/events задать независимо; получить RED по отсутствующему поведению.
- [x] 1.4 Получить независимый Gate3 review spec/tests/RED до production implementation; сохранить явный verdict и reviewed source.

## 2. Implementation

- [x] 2.1 Добавить additive publication receipt через каноническую миграцию на актуальном frontier; проверить повторное применение, сохранность accepted/draft history и отсутствие фиктивного backfill.
- [x] 2.2 Реализовать buildAndPublish и accept в app/Otiz с одной транзакцией публикации, replay receipt и проверкой полноты; focused tests GREEN, rollback и concurrent visibility подтверждены на MariaDB.
- [x] 2.3 Перенести нужные native readers/формулы без изменения расчёта; удалить calculate/closedBefore/closureEvidence/issue и SQL acceptance из Otiz.php по ADR. Проверить старые и новые callers, отсутствие второй реализации и сохранение формул на независимых примерах.
- [ ] 2.4 Подключить существующие POST формы к application owner, сохранить operation identity при повторе; проверить обычный маршрут calculate → snapshot → accept → export, роли, CSRF и отображение incomplete.
- [ ] 2.5 Создать и зарегистрировать additive evidence/quarantine ledger migrations, проверить необходимые projection tables и все транзитивные ensureSchema вызовы оставшихся routes (включая rebuildDecisionState). Удалить runtime DDL, сохранив readiness-проверки, и покрыть достижимый migration tooling checker-ом. Проверить GET/POST с DML-only account и readiness failure без нужной схемы; baseline не расширять.

## 3. Verification and Done

- [ ] 3.1 Выполнить focused regression ОТиЗ, syntax, `git diff --check`, `make architecture-check`, headless browser smoke в изолированном контуре. Сохранить время build, source и результаты без запуска на данных владельца.
- [ ] 3.2 Получить независимый Gate5 review production diff/spec/approved tests/evidence, включая перечень реально удалённого кода и оставшихся временных adapters; сохранить явный verdict.
- [ ] 3.3 Провести один полный CI по согласованной матрице на кандидате; зафиксировать результат. Не дублировать local-full → CI-full. Если CI не запускался, явно оставить gate pending.
- [ ] 3.4 Сверить Done: A01 RED→GREEN, один application owner, удалён заменённый путь, нет runtime DDL, сохранены UI/доступ/история, reviews и проверки завершены. A02/A03 и последующие #24 slices остаются открытыми; #33 следующий. Архивировать только после Done, не по наличию planning artifacts.
