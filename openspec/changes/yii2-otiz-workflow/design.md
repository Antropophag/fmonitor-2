## Context

См. `proposal.md` — Why. На `origin/main` после PR98 Yii2 уже обслуживает OTIZ settlement routes, но calculate и accept отсутствуют в `config/yii/web.php`, а production fallback продолжает передавать их `RapidPilotOtiz`. `app/Otiz/SnapshotPublication` уже является единым application owner для build/publish и accept; `MariaDbSnapshotBuilder`, `MariaDbSnapshotStore` и publication records сохраняют существующую mysqli-транзакцию. Read/export часть частично перенесена в `OtizSettlementController` и `MariaDbOtizSettlementView`.

Параллельный change `yii2-imports-workforce` владеет `config/yii/console.php`, import entrypoints и их verification inventory. Этот срез не изменяет эти файлы и интегрируется от актуального main после его поставки только при необходимости.

## Goals / Non-Goals

**Goals:**

- Закрепить Yii2 web как framework owner полного существующего OTIZ HTTP journey.
- Сохранить `app/Otiz` единственным владельцем финансовых транзакций и append-only фактов.
- Сделать удаление production dispatch к `RapidPilotOtiz` проверяемым на уровне реального router/runtime.
- Сохранить behavioral oracle и совместимость с текущими URL, HTML/navigation, CSRF, XLSX и отказами.

**Non-Goals:**

- Переписывать mysqli application owner на Yii DB внутри этого среза.
- Менять schema, formulas, authority model или acceptance meaning.
- Удалять весь `rapid-pilot`, его исторические verifiers либо выполнять общий deployment/cutover.
- Трогать console/import/workforce/jobs boundaries параллельной сессии.

## Decisions

1. **Расширить существующий Yii2 OTIZ adapter, не создавать второй финансовый модуль.** `OtizSettlementController` либо узко разделённые Yii2 controllers вызывают `SnapshotPublication` и существующие read/export adapters. Альтернатива — перенести SQL из `RapidPilotOtiz` в controller — отвергнута: она создаёт второго владельца фактов и нарушает архитектурную границу.

2. **Сохранить текущие публичные URL и серверный HTML.** Добавляются Yii2 rules для `POST /pilot/otiz/calculate` и `POST /pilot/otiz/snapshots/{id}/accept`; уже перенесённые routes остаются каноническими. Альтернатива — новый `/yii/otiz` namespace — отвергнута из-за ненужного изменения bookmarks, return URL и browser oracle.

3. **Использовать Yii2 Request/User/CSRF/Response для framework concerns.** Формы используют `_csrf`, как текущие Yii2 OTIZ settlement actions; compatibility с legacy `csrfToken` допускается только если exact characterization докажет необходимость и будет явно ограничена. Auth/session/permission остаются общей Yii2 composition, state-changing owner повторно проверяет actor semantics там, где это предусмотрено существующим контрактом.

4. **Persistence owner остаётся `app/Otiz/SnapshotPublication`.** Build/publish и accept выполняются через одну транзакцию owner; controller только валидирует HTTP representation, отображает известные domain failures и делает redirect. `MariaDbSnapshotPublicationRecords` продолжает владеть operation replay/receipt; отсутствие operation id у legacy accept не расширяется новым правилом без отдельного решения.

5. **Read/export консолидируются без изменения данных.** Существующий `MariaDbOtizSettlementView` расширяется только недостающими read projections; XLSX использует существующий exporter seam. Presentation formatting переносится в Yii2 views/support, а не в domain/application module.

6. **Удаление адаптера ограничено доказанным route frontier.** После GREEN production router не должен dispatch/require `RapidPilotOtiz` для перенесённых путей. Сам файл и verifiers сохраняются, пока они нужны как oracle другим незавершённым routes. Architecture check получает явный список запрещённых production includes/dispatches, не глобальный запрет исторических ссылок.

7. **Проверка строится от публичной границы.** Root создаёт нормативный refactor contract и intended RED для реального Yii2/public runtime: success, unauthorized/forbidden, bad CSRF, blockers, replay/concurrency, no-fact rejection, navigation/return, export и runtime dependency. DB fingerprints используются как evidence, но не заменяют HTTP seam.

## Risks / Trade-offs

- [Существующий `OtizSettlementController` плотный и смешивает HTML] → вынести views только настолько, насколько нужно полному route slice; не превращать migration в общий UI rewrite.
- [Legacy pilot acceptance semantics помечены `NEEDS_GRILL`] → сохранять только наблюдаемую совместимость и не заявлять закрытие redesign; любое изменение authority/evidence останавливает slice.
- [mysqli owner вызывается из Yii2 runtime] → сохранить одно соединение/транзакцию на операцию и проверить rollback/replay; отдельную миграцию на Yii DB планировать позже.
- [Verification roster может пересечься с imports/workforce] → основной slice не редактирует console files; roster merge выполнять после актуального main с механическим разрешением и повторным harness prepare.
- [Удаление fallback может скрыть неинвентаризированный OTIZ route] → до RED снять полный method/path inventory из router, JS/forms и verifiers; запретить удаление, пока каждый маршрут не имеет Yii2 owner или явный non-production статус.

## Migration Plan

1. Зафиксировать route/owner/consumer inventory и нормативный `YII2-OTIZ-WORKFLOW-001` без продуктовых изменений.
2. Подготовить harness binding и complete verification plan; написать intended RED и получить независимый Gate 3.
3. Реализовать Yii2 calculate/accept, недостающие views/assets/read/export adapters и focused GREEN.
4. Удалить только заменённый production dispatch к `RapidPilotOtiz`, пройти dependency/architecture/browser checks и независимый Gate 5.
5. После актуализации от main запустить один exact-source Quality Graph CI. Deployment не выполнять.

Rollback до общего cutover — вернуть Yii2 route wiring к предыдущему merged source; новые snapshot/events не удалять и не переписывать. Благодаря сохранённому application owner факты совместимы с прежним adapter.
