# YII2-CALENDAR-003 — read-only календарь инспекций Yii

## Простыми словами

Пользователь с правом видеть объекты снова получает календарь уже запланированных инспекций в Yii и ссылку на него в разделе «Монтаж». Просмотр ничего не записывает и не меняет правила планирования.

## Actor и seam

Actor — аутентифицированный пользователь Yii. Public seam — `GET`/`HEAD `/pilot/calendar` и `/pilot/calendar/`, общий sidebar и ссылки на `/pilot/objects/<id>`. Источник — принятые строки canonical inspection schedule schema и существующая object display identity. Timezone — `Europe/Moscow`.

## A1. Authorization и HTTP boundary

Оба path variants SHALL требовать session и `objects.read`, возвращать `200` разрешённому actor и `403` actor без permission. Guest SHALL пройти общий login return-path. Ответ MUST иметь `Cache-Control: no-store`. GET/HEAD MUST NOT выполнять DDL, DML, runtime repair, audit или scheduling command.

## A2. Deterministic projection

Видимый период SHALL быть от current Moscow date минус 30 дней до current date плюс 6 календарных месяцев включительно. Проекция SHALL содержать существующие inspection schedule facts в периоде, соединённые с object display identity. Месяцы и даты идут хронологически; на одной дате события идут по numeric `legacy_object_id`, затем numeric schedule `id`. Physical insertion order не влияет на результат. События вне периода отсутствуют.

Пример при clock `2026-09-19T12:00:00+03:00`: rows для объекта 42 (`2026-11-03`), 19 (`2026-10-15`) и 7 (`2026-10-15`), сохранённые в этом порядке, отображаются как октябрь: 7, 19; затем ноябрь: 42.

## A3. Date selection и safe failures

Без `date` выбран current Moscow day. Exact `date=YYYY-MM-DD` внутри периода выбирает этот день и agenda содержит только его события в A2 order. Malformed, repeated/array либо out-of-range `date`, а также неизвестные query keys SHALL вернуть `400` без partial calendar HTML. Missing/incompatible planning schema или bounded source overflow SHALL вернуть `503` с безопасным сообщением, без partial HTML, DDL или repair.

## A4. Presentation и navigation

Страница SHALL использовать общий Yii shell и существующие pinned `shlz-ui` Calendar Grid/calendar assets. Каждый event показывает дату, object registration/address и ссылку на существующий `/pilot/objects/<id>`; output HTML-escaped. `Календарь` SHALL быть единственным пунктом после `Объекты монтажа` в группе `Монтаж`, присутствовать только при `objects.read` и иметь единственный `aria-current="page"` на обоих calendar paths.

## A5. Preservation

Повторные GET/HEAD при одинаковых clock/facts SHALL давать одинаковый semantic event order, а schedules, schedule events, object facts, audit и schema — оставаться byte-equivalent. Срез MUST NOT менять authorization, acceptance, replay, concurrency, persistence или audit semantics существующих scheduling commands. `rapid-pilot/Calendar.php` остаётся oracle/adapter и не получает новой domain logic.
