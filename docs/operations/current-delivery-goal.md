# Текущая цель — №76, справочник монтажников Yii2

Владелец поручил продолжить рефакторинг №76. Предыдущий документарный срез
поставлен PR #91: exact commit `47bb6b438d667ef807ff273b9efa4577e8780bd6`,
Quality Graph CI 34522776165 SUCCESS, merge
`aa4d20f30edb4b5e9c7de7abad8ac2d016ec4952`.

Следующий bounded slice — read-only `/pilot/installers`: сохранить поиск,
status/availability filters, 50-row pagination, текущие зарегистрированные
закрепления, authorization, HEAD/failure contracts и responsive UI, переведя
маршрут на Yii2 controller/query/view без runtime-загрузки rapid-pilot.

[OpenSpec](../../openspec/changes/yii2-installer-directory/) создаёт planning
input. До реализации root обязан подготовить verification input/harness package,
написать нормативный spec и intended RED; отдельный sol/low reviewer решает
Gate 3, отдельный sol/low executor реализует, независимый sol/low reviewer решает
Gate 5. Фактические source/PR/CI всегда получать через harness state.

Карточка монтажника, новые conflict/staleness rules, workforce sync/import,
checklist/photo/offline, ОТиЗ, console/runtime retirement и общий cutover остаются
в следующих срезах №76. Рабочий rapid-pilot stand не переключается. Deployment
требует отдельной авторизации после общего upgrade/rollback evidence.
