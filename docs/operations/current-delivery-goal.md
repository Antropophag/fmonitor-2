# Текущая цель — №76, workforce sync через Yii2 console

Владелец 2026-09-12 поручил автономно довести следующий не-OTIZ срез №76 до PR merge-ready. Рабочий checkout: `/Users/antropophag/code/fmonitor-2-yii2-workforce-sync-76`, branch `codex/yii2-workforce-sync-76-20260912`.

Срез переносит ручной workforce sync на `php bin/yii workforce-sync/run --interactive=0`, оставляет прежний entrypoint тонким alias и объединяет manual/jobs composition без изменения Bitrix/workforce domain owners. [Контракт](../../specs/YII2-WORKFORCE-SYNC-CONSOLE-001.md), [OpenSpec](../../openspec/changes/yii2-workforce-sync-console/), [delivery record](yii2-workforce-sync-console-delivery-2026-09-12.md).

Gate 3 и Gate 5 APPROVED; focused checks GREEN. Кандидат — PR #101. Первый CI `34698699427` выявил четыре исправленные regression groups и один отдельно перепроверенный browser timeout; correction Gate 3/5 APPROVED. Фактические commit/PR/CI проверять через `python3 tools/delivery/harness.py state`. Новый exact-source CI требуется до merge-ready; stand/deployment и общий #76 этим срезом не закрываются. Параллельный OTIZ checkout и его WIP не затрагиваются.

# Предыдущий указатель — история

# Owner verification decision — 2026-09-11

Локальные `make test` и `make verify` больше не запускать в delivery-задачах без
нового прямого поручения владельца. Локально выполнять только bounded focused/fast
checks; полный matrix выполняется один раз параллельным exact-source GitHub CI.
Прерванный локальный запуск 2026-09-11 подтвердил чрезмерную последовательную
стоимость и отдельно встретил недоступную PDF renderer dependency; повторный
локальный full run не является способом исправления этой среды.

# Текущая цель — №76, полный HTTP workflow ОТиЗ через Yii2

Рабочий checkout: `/Users/antropophag/code/fmonitor-2-yii2-otiz-http-76`, branch
`codex/yii2-otiz-http-76-20260912`. PR100 поставил case import в Yii2 console;
этот независимый срез переносит calculate/accept/export, settlement adjacency,
reconciliation/quarantine и historical reads в Yii2 web. Контракт и evidence:
[`YII2-OTIZ-WORKFLOW-001`](../../specs/YII2-OTIZ-WORKFLOW-001.md),
[`OpenSpec`](../../openspec/changes/yii2-otiz-workflow/),
[`delivery record`](yii2-otiz-workflow-delivery-2026-09-12.md).

Gate 3 APPROVED. Gate 4 focused/architecture checks GREEN. Следующий шаг —
exact-source Gate 5, PR и один полный Quality Graph CI. Deployment остаётся
UNKNOWN и не выполняется без отдельной авторизации.

## Предыдущий указатель — история

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

Кандидат поставки — PR #96; exact-source Quality Graph `34593480014` GREEN и
`VERIFY_OK`. После merge этот документ становится checkpoint; следующий срез
№76 начинать от актуального main, сохраняя историю failures/reviews этого среза.
