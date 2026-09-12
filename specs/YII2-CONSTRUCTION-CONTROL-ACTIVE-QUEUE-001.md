# YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001

## Простыми словами

После регистрации акта ПТО объект больше не является рабочей задачей инженера
стройконтроля и исчезает из его очереди. Сам объект, акт, декларация, инспекции и
история сохраняются; расчёты ОТиЗ и карточка объекта не меняются.

## Основание и public seam

Actor — активный пользователь с exact permission `construction_control.read`.
Основание — issue #39 и продуктовый статус «Документарное закрытие». Public seam:
реальный Yii2 `GET /pilot/construction-control`; Yii identity является источником
actor. Read owner возвращает страницу размера 50, а controller/view не владеют
правилом состава очереди.

Этот контракт уточняет YII2-INSPECTION-JOURNEY-001 A8: переключатель завершённых
не разрешает возвращать в рабочую очередь дело после начала документарного
закрытия. Он может оставаться как совместимый UI-control для допустимых строк,
но не расширяет server-side projection.

## Нормативное поведение

1. В выдачу входят только `working` cases без принятого append-only completion
   fact `pto_act`. Дело без акта ПТО остаётся в выдаче независимо от наличия
   checklist activity. Дело только с актом ПТО и дело с актом ПТО плюс декларацией
   отсутствуют.
2. `total`, `pages`, `LIMIT/OFFSET` и page-boundary validation вычисляются после
   того же предиката исключения. На 51 допустимом деле страницы содержат 50 и 1;
   исключённые дела не создают пустую хвостовую страницу.
3. Сохраняются действующие latest engineer, mine/all/search client controls,
   сортировка по checklist activity и object ID, ссылки, Yii shell и safe
   HTML-escaping. Клиент не получает исключённые строки для последующего скрытия.
4. GET/HEAD остаются read-only: не обновляют case, completion facts, checklist
   operations, events, assignment/original или schema. Повтор без новых фактов
   возвращает тот же состав.
5. Гость сохраняет действующий redirect contract. Активный пользователь без
   exact `construction_control.read` получает 403 без фактов. Неверная страница
   и infrastructure failure следуют действующему Yii safe-error contract.
6. ОТиЗ, completion-команды, вычисление 85/100%, очередь ФКР, карточка объекта,
   `rapid-pilot`, schema и deployment не меняются.

## Независимые примеры

Для actor 73 с `construction_control.read` заданы три `working` cases:

- object 4512/case 6101 без completion facts и с checklist activity — присутствует;
- object 4513/case 6102 без completion facts и без checklist activity — присутствует;
- object 4514/case 6103 с `pto_act` и `declaration` — отсутствует.
- object 4516/case 6105 только с `pto_act` — отсутствует.

С 49 дополнительными допустимыми делами ответ содержит 51 строку на страницах
50/1 и `total=51`; исключённые дела не создают хвостовую страницу. До и после
GET/HEAD все fixture rows и completion facts совпадают. После добавления `pto_act` для case
6101 следующий GET возвращает 50 строк с `total=50`, а прежняя page2 отклоняется, не удаляя ни одного
факта. Ожидания происходят из issue #39 и определения этапа, не из SQL реализации.
