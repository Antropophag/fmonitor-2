# YII2-CONSTRUCTION-CONTROL-PREOPENING-001

## Простыми словами

После выбора состава и принятия подписанного оригинала назначенный инженер видит объект в своей очереди, открывает работы над ещё заблокированным чек-листом и продолжает работу там же. Просмотр ничего не открывает, а чужой инженер не получает объект или действие.

## Authority и public seams

Источник решения — issue #40 и владелец продукта 2026-09-08. Наследуются `YII2-PREOPENING-JOURNEY-001`, `YII2-INSPECTION-JOURNEY-001` и `YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001`. Actor берётся только из Yii session.

Наблюдаемые seams: `GET|HEAD /pilot/construction-control`, `GET|HEAD /pilot/construction-control/objects/{id}/checklist` и существующая команда `POST /pilot/objects/{id}/execution` с action `open_confirmed`. Controller/view/read model не создают второго writer; opening facts принадлежат существующему confirmed-original application owner.

## A1. Состав рабочей очереди

Для активного actor с exact `construction_control.read` очередь MUST включать:

- `working` cases без `pto_act` по действующему #39 contract;
- неоткрытый case, который существующий authoritative preopening projection уже классифицирует как `Готов к открытию`, с назначенным инженером.

Готовая строка имеет status `Готов к открытию`, `completed=false` и ведёт на construction-control checklist. Она не считается открытой. Selection без accepted current original и case с `pto_act` не входят как ready. Этот slice MUST потреблять authoritative readiness существующего preopening projection, а не реализовывать второй упрощённый lineage predicate; stale-lineage contract и его regression остаются у `YII2-PREOPENING-JOURNEY-001`.

Представление «Мои» MUST быть защищено сервером: обычный инженер получает только строки, где current engineer user ID равен actor ID. Manager-wide поведение может сохранять действующий доступ, но JS-фильтр не является authorization boundary. Действующие pagination rules не меняются.

## A2. Предоткрывающий checklist

Назначенный инженер с exact `installation.open` получает HTTP 200 и видит над чек-листом status `Готов к открытию`, поле обязательной фактической даты и кнопку `Открыть работы`. Форма содержит native CSRF и exact current `orderId`, `revisionId`, `sequence`, новый UUIDv4 request ID; её action использует существующий execution seam. Checklist sections, photos, completion, sync-context и offline mutation остаются disabled/inert до `working`.

Неназначенный actor не получает готовую строку в своей server-side очереди и не получает форму открытия. Назначенный actor без `installation.open` может видеть правдиво заблокированный экран, но не форму. Отзыв exact capability между GET и POST даёт отказ owning command без фактов.

## A3. Успешное открытие и return path

При допустимой дате от document date до controlled Moscow today включительно existing owner атомарно применяет/reuses current composition и добавляет ровно один opening fact/event. Success возвращает actor на `/pilot/construction-control/objects/{id}/checklist`. После refresh строка остаётся в очереди как открытая, форма исчезает, checklist actions разрешаются по унаследованным ролям.

Semantic replay, concurrency, invalid date/identity, absent grant и infrastructure outcomes наследуются без изменения от existing opening owner. Этот slice доказывает, что отрендеренная форма передаёт его current intent и не создаёт обходного writer.

## A4. Read-only, история и безопасность

GET/HEAD queue/checklist не изменяют case, application, original, event, checklist, completion или schema facts; HEAD возвращает пустое body. Guest redirect, exact `construction_control.read`, 404 resource isolation and safe HTML inherited boundaries сохраняются. Новый путь не загружает `rapid-pilot`/`PilotHttp` runtime.

## Независимый пример

Case 6101/object 4512: selection order 81 revision 1, installer 7001, engineer 73, accepted original revision R1 от 2026-09-01, case ещё не открыт, PTO отсутствует. Actor 73 имеет `construction_control.read`, `checklist.read`, `installation.open`.

До POST очередь содержит 4512 один раз как `Готов к открытию`; checklist inert и содержит opening form. GET/HEAD сохраняют все facts. POST с actualStartDate `2026-09-02` возвращает к construction-control checklist; case становится `working`, `opened_by_user_id=73`, одна application и один `installation_opened_from_original` event. После refresh 4512 остаётся один раз, opening form отсутствует и checklist enabled. Actor 95 и actor 73 после отзыва `installation.open` не создают ни одного нового факта.

## Done

Gate 3 APPROVED для полного root-owned test candidate; отдельный executor; focused plan GREEN; Gate 5 APPROVED; один exact-source Quality Graph CI GREEN. Deployment не входит.
