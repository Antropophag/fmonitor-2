# Owner verification decision — 2026-09-11

Локальные `make test` и `make verify` больше не запускать в delivery-задачах без
нового прямого поручения владельца. Локально выполнять только bounded focused/fast
checks; полный matrix выполняется один раз параллельным exact-source GitHub CI.
Прерванный локальный запуск 2026-09-11 подтвердил чрезмерную последовательную
стоимость и отдельно встретил недоступную PDF renderer dependency; повторный
локальный full run не является способом исправления этой среды.

# Текущая цель — №76, фоновые процессы через Yii2 console

Владелец поручил продолжить рефакторинг №76. Предыдущий документарный срез
поставлен PR #91: exact commit `47bb6b438d667ef807ff273b9efa4577e8780bd6`,
Quality Graph CI 34522776165 SUCCESS, merge
`aa4d20f30edb4b5e9c7de7abad8ac2d016ec4952`.

Справочник монтажников поставлен PR #92, harness hardening — PR #93. Следующий
bounded slice — production `worker`, `scheduler` и jobs `health`: сохранить
durable queue/lease/retry/deduplication, heartbeat, безопасную Bitrix-конфигурацию
и restart behavior, переведя entrypoints на общий Yii2 console runtime без
production-загрузки `rapid-pilot/jobs-entrypoint.php`.

[OpenSpec](../../openspec/changes/yii2-jobs-console/) создаёт planning
input. До реализации root обязан подготовить verification input/harness package,
написать нормативный spec и intended RED; отдельный sol/low reviewer решает
Gate 3, отдельный sol/low executor реализует, независимый sol/low reviewer решает
Gate 5. Фактические source/PR/CI всегда получать через harness state.

Imports/migrations, web runtime retirement, карточка монтажника, новые
conflict/staleness rules, checklist/photo/offline, ОТиЗ и общий cutover остаются
в следующих срезах №76. Рабочий rapid-pilot stand не переключается. Deployment
требует отдельной авторизации после общего upgrade/rollback evidence.
