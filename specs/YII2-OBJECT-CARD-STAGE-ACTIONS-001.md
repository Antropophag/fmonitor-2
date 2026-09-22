# YII2-OBJECT-CARD-STAGE-ACTIONS-001 — состав и главное действие карточки

## Простыми словами

Карточка не забывает сохранённый состав, пока пользователь получает подписанный оригинал, и всегда показывает следующий шаг текущего этапа монтажного дела. Ожидающий новый состав остаётся отдельным от действующей бригады; документы показываются только когда они действительно приняты. Срез не меняет статусы, права, команды, готовность к открытию или хранение фактов.

## 1. Authority, seam и наследуемые контракты

Actor — аутентифицированный пользователь с `objects.read`. Публичный seam — существующий `GET|HEAD /pilot/objects/{positive-id}` через Yii session/RBAC. State-changing ссылки и формы ведут только к существующим owners selection, original upload, confirmed opening, checklist и documentary completion.

Полностью наследуются `YII2-PREOPENING-JOURNEY-001`, `YII2-DOCUMENTARY-CLOSURE-001`, `YII2-OBJECT-CARD-PRESENTATION-001`, контракты selection/original/opening и status projection. `order` продолжает означать применённое либо подтверждённое основание с реальным оригиналом; `confirmedOriginal` — принятую revision; `opened` — состоявшееся открытие. Новый read-only `pendingComposition` не изменяет их смысл и не становится readiness fact.

## 2. Read-model ожидающего состава

Карточка читает latest canonical selection с теми же integrity/current semantics, что `/assignment-order/selection`. Если latest selection не имеет принятого оригинала, read model содержит отдельный `pendingComposition`: `orderId`, `version`, installers и engineer из неизменяемых selection snapshots. Поле не создаёт и не меняет facts/events/tasks/files.

До открытия pending composition виден после save → leave → return и после refresh. Верхний блок содержит точный текст `Состав сохранён. Ожидается подписанный оригинал`. «Команда» показывает выбранных монтажников и явно называет их ожидающим составом; она не показывает `Состав ещё не выбран`.

После открытия applied `order.installers` остаются действующей бригадой. Если подготовлен новый latest selection без оригинала, он показывается отдельным ожидающим составом и никогда не заменяет applied crew, application basis, status или checklist attribution.

## 3. Документы и доступ

Pending composition без accepted revision не создаёт document row, `Подписанный оригинал.pdf`, дату распоряжения, download/history/correction links или `confirmedOriginal`. Вкладка сообщает `Подписанный оригинал ожидается`.

Exact existing `assignment_order.original.upload` access даёт ссылку с текстом `Загрузить оригинал` и href `/pilot/objects/{objectId}/assignment-orders/{pendingOrderId}/originals/submit`. Без этого доступа состояние остаётся видимым, но link/button/URL отсутствует. Existing server-side authorization не меняется.

## 4. Единственное главное действие

В workspace присутствует ровно один `.fm2-next-action`. Он выбирается в порядке ниже; terminal/exceptional branch имеет приоритет над общим opened branch.

| Факты | Заголовок/состояние | Доступная команда |
|---|---|---|
| status `Требуется изменение` | `Требуется изменение` | нет обычного `Перейти к чек-листу` |
| status `Работы завершены` | `Работы завершены` | нет continue/mutation CTA; ссылки просмотра остаются по прежним правам |
| status `Документарное закрытие`, нет ПТО | `Требуется акт ПТО` | при `installation.completion.pto.record` ссылка `Перейти к акту ПТО` на `#completion` |
| status `Документарное закрытие`, ПТО есть, декларации нет | `Требуется декларация` | при `installation.completion.declaration.record` ссылка `Перейти к декларации` на `#completion` |
| status `Монтажные работы` | `Монтажные работы` | при checklist read `Перейти к чек-листу` |
| original принят, `opened=false` | `Открыть монтажные работы` | при `installation.open` существующая open-confirmed форма |
| pending composition без original | `Загрузить подписанный оригинал` | при upload access `Загрузить оригинал` на exact pending order |
| selection отсутствует | `Требуется распоряжение` | при selection access `Выбрать состав` |

Если соответствующего command/read grant нет, тот же блок объясняет требуемое состояние без недоступной команды, hidden command fields или command URL. Ограниченная роль не получает право по наличию ссылки. Open form сохраняет exact fields/owner; upload не разрешает opening; original acceptance сохраняет прежнее opening behavior.

## 5. Согласованность и соседние потоки

Верхний блок, «Команда», «Документы» и readiness label одного ответа не утверждают одновременно наличие и отсутствие состава либо документа. Каждый GET/HEAD отображает согласованный на чтение набор persisted facts; повтор и concurrent later mutation не создают side effects.

Редактор реквизитов, validation/modal return, append-only `object_details_changed` и cursor `Показать ещё` из PR #226 сохраняются. Existing completion forms/owners, deadline certificate link, technical documents, original history/download and checklist view links сохраняются. Construction-control readiness consumer, ОТиЗ, #171 filters, deadlines и PТО/declaration persistence не изменяются.

## 6. Acceptance matrix

A. Selection saved, no original: GET/HEAD and repeated refresh show exact waiting copy, selected installers, no absent-composition copy, no original artifact, no opening form, and no writes.

B. Authorized upload actor gets exact pending-order submit link in primary block and documents; reader without upload sees state but neither link nor URL.

C. Accepted current original restores existing ready/open-confirmed presentation and command fields; original acceptance itself does not open work.

D. Opened case with later pending selection shows applied crew and separately pending crew; status/readiness/checklist continue to use applied facts.

E. Working below 85 links to checklist only when readable. `Требуется изменение` never exposes ordinary continue CTA.

F. At 85 without PTO primary action anchors existing PTO form only with record grant; with PTO/no declaration it anchors declaration form only with record grant.

G. Completed 100 shows completion and no montage continuation or completion mutation as primary action while preserving authorized view links.

H. No selection shows select CTA only with exact access; all restricted-role variants have one informative block and no forbidden command.

I. Existing object-card presentation, editor/history pagination, preopening authorization/opening, documentary closure and construction-control readiness focused regressions remain GREEN on an isolated task-owned database.

## 7. Verification boundaries

Tests use real Yii login/session/HTTP, unique canonical MariaDB schema/prefix, private runtime/session/storage paths and teardown only their resources. Expected labels/hrefs are literals from this contract. GET/HEAD before/after facts prove no writes. No local full `make test`/`make verify`; planner-selected focused checks, independent required reviews and one exact-source CI run are mandatory.
