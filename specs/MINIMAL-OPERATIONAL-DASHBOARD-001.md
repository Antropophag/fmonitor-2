# MINIMAL-OPERATIONAL-DASHBOARD-001 — минимальная операционная сводка

## Простыми словами

Пользователь с доступом к объектам получает один компактный экран: сколько объектов доступно, сколько находится в монтаже, сколько просрочено и сколько стартует в ближайшие 14 дней. Экран объясняет дату и смысл чисел и ведёт к реальным объектам. Он ничего не записывает и не притворяется аналитической платформой.

## 1. Actor и public seam

Actor — активный локальный пользователь с действующим `objects.read`. Public seam — `GET|HEAD /pilot/dashboard`, основная Yii-навигация и обычные ссылки `/pilot/objects...` и `/pilot/objects/{id}`. Единственная дата среза определяется один раз на запрос как календарная дата `Europe/Moscow`.

Неаутентифицированный запрос получает обычный безопасный login redirect с return path. Аутентифицированный actor без `objects.read` получает 403 без значений и строк дашборда. Пункт «Дашборд» виден только с этим разрешением, идёт первым в группе «Монтаж» и не меняет относительный порядок существующих разрешённых ссылок. Корневой redirect остаётся `/pilot/objects`.

## 2. Четыре показателя

В области объектов, доступных actor по действующей canonical policy, экран MUST показать ровно четыре верхнеуровневых числа:

1. `total` — все доступные объекты.
2. `active` — текущий canonical статус «Монтажные работы» или «Документарное закрытие».
3. `overdue` — объект не завершён, имеет известную плановую дату окончания строго раньше даты среза.
4. `upcoming` — известная плановая дата начала находится в закрытом интервале `[cutoff, cutoff + 13 days]`.

Неизвестная дата не входит в `overdue` или `upcoming`. Завершённый объект не является просроченным. Каждый показатель показывает название, значение, текстовое основание расчёта и ссылку: `total` — в `/pilot/objects`, `active` — в существующий реестр с поддерживаемым status filter, `overdue` — к списку `#overdue`, `upcoming` — к списку `#upcoming`. Цвет или изображение не являются единственным носителем смысла.

Worked example: на `2026-09-20` доступны пять объектов — завершённый со сроком `2026-09-10`, active со сроком `2026-09-19`, documentary closeout со сроком `2026-09-25`, ожидающий распоряжение со стартом `2026-09-20` и объект с неизвестными датами. Ожидается `total=5`, `active=2`, `overdue=1`, `upcoming=1`. Старт `2026-10-03` входит в окно, `2026-10-04` — нет.

## 3. Списки внимания

Экран MUST показать не более пяти upcoming объектов, упорядоченных по start, registration number, object id, и не более пяти overdue объектов, упорядоченных по самому раннему finish, registration number, object id. Верхние числа сохраняют полный итог. Строка содержит реальную идентичность, релевантную дату и ссылку на exact `/pilot/objects/{id}`; карточка сохраняет собственную server-side authorization.

## 4. Bounded read и история

Итоги рассчитываются server-side одним bounded aggregate query либо эквивалентным потоковым чтением; каждый список использует bounded `LIMIT 5`. Query count не растёт с числом объектов. В PHP и HTML не materialize полный реестр; суммарно материализуется не более десяти list rows, в том числе на fixture 30 000. Источник — canonical current facts; новый DDL, cache, summary table и копия предметного состояния запрещены.

GET, HEAD, repeat и concurrent reads MUST быть детерминированы при неизменных facts/cutoff и MUST NOT создавать, изменять или удалять domain/audit facts. Несовместимая schema или инфраструктурная ошибка приводит к единому безопасному «Данные временно недоступны» без частичных чисел и внутренних подробностей. Пустой успешный набор показывает четыре нуля и единый empty state.

## 5. UI и responsive contract

Используются только публичные закреплённые assets `shlz-ui`: Dashboard, Chart Widget, status/link/button/empty-state. Alternative UI/chart dependency и private export запрещены. На 390 CSS px всё содержание остаётся в одном document-flow столбце, читается и управляется без horizontal scrolling. Desktop witness — 1440 CSS px. Экран сохраняет существующий Yii shell.

## 6. Демонстрация

Repository MUST содержать сценарий: открыть экран, назвать cutoff и основания четырёх показателей, перейти к overdue/upcoming объекту и вернуться к реестру. Отдельно перечисляются вопросы о решениях, периодах и разрезах следующего шага; они явно названы будущими идеями, не реализованными обещаниями.

## 7. Acceptance matrix

- **A — authorized GET:** 200, текущая навигация, дата и четыре показателя.
- **B — anonymous:** login redirect и safe return path.
- **C — forbidden:** 403, nav item/данные отсутствуют.
- **D — formulas:** worked example даёт `5/2/1/1` независимо от implementation.
- **E — boundaries:** cutoff+13 включён, cutoff+14 и unknown исключены.
- **F — canonical parity:** все canonical queue statuses совпадают с active/completed semantics.
- **G — stable lists:** два top-5, полный count и declared tie-breakers.
- **H — navigation:** dashboard first; старые разрешённые ссылки сохраняют порядок; links exact.
- **I — empty:** четыре нуля и единый honest empty state.
- **J — failure:** единый safe error без partial numbers/internal details.
- **K — read-only/replay:** GET/HEAD/repeat/concurrent не меняют fact fingerprint.
- **L — bounded scale:** 30k, constant query count, ≤10 list rows, no hidden dataset.
- **M — shlz-ui/responsive:** только public exports; 1440/390 без overflow и потери semantics.
- **N — demo honesty:** сценарий соответствует реализованному, future ideas отделены.

## 8. Non-goals и Done

Не входят chart/trends, ОТиЗ KPI, произвольные периоды, персонализация, сохранение layout, новые permissions/DDL/facts, смена landing route и `rapid-pilot/`. Done требует GREEN planner-selected focused evidence, planner-required independent reviews, один GREEN exact-source CI и PR-ready candidate. UNKNOWN не считается GREEN; merge/deploy/settings не входят.
