# YII2-CONSTRUCTION-CONTROL-PREOPENING-001

## Простыми словами

После выбора состава и принятия подписанного оригинала объект появляется у назначенного инженера в «Мои». Открыть его над заблокированным чек-листом может также другой уполномоченный инженер, если назначенный недоступен.

## Authority и public seams

Источник решения — issue #40 и владелец продукта 2026-09-08. Наследуются `YII2-PREOPENING-JOURNEY-001`, `YII2-INSPECTION-JOURNEY-001` и `YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001`. Actor берётся только из Yii session.

Наблюдаемые seams: `GET|HEAD /pilot/construction-control`, `GET|HEAD /pilot/construction-control/objects/{id}/checklist` и существующая команда `POST /pilot/objects/{id}/execution` с action `open_confirmed`. Controller/view/read model не создают второго writer; opening facts принадлежат существующему confirmed-original application owner.

## A1. Состав рабочей очереди

Для активного actor с exact `construction_control.read` очередь MUST включать:

- `working` cases без полного документального завершения;
- завершённые `working` cases, у которых одновременно существуют append-only
  факты `pto_act` и `declaration`, с `completed=true` для клиентского фильтра;
- неоткрытый case, который существующий authoritative preopening projection уже классифицирует как `Готов к открытию`, с назначенным инженером.

Готовая строка имеет status `Готов к открытию`, `completed=false` и ведёт на construction-control checklist. Она не считается открытой. Selection без accepted current original не входит как ready. `working` case только с `pto_act`, но без `declaration`, находится в документальном закрытии и в очередь стройконтроля не входит. Этот slice MUST потреблять authoritative readiness существующего preopening projection, а не реализовывать второй упрощённый lineage predicate; stale-lineage contract и его regression остаются у `YII2-PREOPENING-JOURNEY-001`.

Очередь сохраняет действующий общий server response и client-side «Мои/Все»: current engineer ID управляет персональным отображением, но не является authorization boundary. Завершённые строки входят в server-side pagination и total, скрыты клиентом по умолчанию и появляются после включения «Показывать завершённые». GET, фильтрация и повторное переключение не меняют completion facts. Действующие pagination rules не меняются.

## A2. Предоткрывающий checklist

Любой активный `construction_control_engineer`, которому действующие правила разрешают checklist и exact `installation.open`, получает HTTP 200 и видит форму открытия. Совпадение с назначенным инженером не требуется: это штатное замещение. Checklist mutations остаются disabled/inert до `working`.

Actor без checklist role access или exact `installation.open` не получает форму. Отзыв exact capability между GET и POST даёт отказ owning command без фактов. Legacy/non-local authorization этим slice не меняется и не проверяется.

## A3. Успешное открытие и return path

При допустимой дате от document date до controlled Moscow today включительно existing owner атомарно применяет/reuses current composition и добавляет ровно один opening fact/event. Success возвращает actor на `/pilot/construction-control/objects/{id}/checklist`. После refresh строка остаётся в очереди как открытая, форма исчезает, checklist actions разрешаются по унаследованным ролям.

Semantic replay, concurrency, invalid date/identity, absent grant и infrastructure outcomes наследуются без изменения от existing opening owner. Этот slice доказывает, что отрендеренная форма передаёт его current intent и не создаёт обходного writer.

## A4. Read-only, история и безопасность

GET/HEAD queue/checklist не изменяют case, application, original, event, checklist, completion или schema facts; HEAD возвращает пустое body. Guest redirect, exact `construction_control.read`, 404 resource isolation and safe HTML inherited boundaries сохраняются. Новый путь не загружает `rapid-pilot`/`PilotHttp` runtime.

## Независимый пример

Case 6101/object 4512: selection order 81 revision 1, installer 7001, engineer 73, accepted original revision R1 от 2026-09-01, case ещё не открыт, PTO отсутствует. Actor 73 имеет `construction_control.read`, `checklist.read`, `installation.open`.

До POST очередь содержит 4512 один раз как `Готов к открытию` и помечает engineer 73 для фильтра «Мои». Замещающий engineer 95 с теми же checklist/open rights видит inert checklist и opening form. POST с датой `2026-09-02` сохраняет `opened_by_user_id=95`, возвращает в checklist и разблокирует его. После отзыва `installation.open` тот же POST даёт 403 без фактов.

## Done

Gate 3 APPROVED для полного root-owned test candidate; отдельный executor; focused plan GREEN; Gate 5 APPROVED; один exact-source Quality Graph CI GREEN. Deployment не входит.
