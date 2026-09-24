## Context

См. `proposal.md` и delta spec. Сейчас оба Yii views печатают `objectId`; controllers передают только original form/history projection. Публичный owner `InstallationProcess\\MariaDbEffectiveObjectDetails` уже объединяет legacy import с ручными поправками #222/#226, а original controllers уже владеют проверкой прав и доступности конкретного object/order.

## Goals / Non-Goals

**Goals:**

- После успешной существующей авторизации добавить к form/history projection только четыре effective-реквизита и отобразить их единообразно.
- Оставить state-changing original application seam, replay и append-only историю полностью неизменными.
- Получить реальные HTTP/browser доказательства для initial/correction/history, security и responsive rendering на disposable fixture.

**Non-Goals:**

- Не создавать второй effective resolver, object-card projection или cached copy и не изменять общий owner.
- Не менять navigation, `ViewSupport`, CSS/JS, schema, PDF generation, transport/writers, календарь/MariaDbYiiObjectQueue, ОТиЗ, integrations, deployment или CI policy.
- Не проводить общий аудит остальных views и не возобновлять #49/#240.

## Decisions

1. **Owning module и dependency.** `PreopeningResources` экспонирует узкий вызов существующего публичного `InstallationProcess\\MariaDbEffectiveObjectDetails`; нового production resolver/class нет. Yii controllers вызывают его только после действующей original authorization и передают закрытую projection view. Альтернатива — читать таблицы или object-card query прямо в view/controller — отвергнута как второй resolver и нарушение владельца.
2. **Authorization ordering.** Effective read выполняется только после того, как существующий form/history query подтвердил доступность объекта, распоряжения и право actor. Ошибка effective read остаётся безопасным недоступным outcome через текущую HTTP error boundary; реквизиты не попадают в denied response. Альтернатива — общий публичный details endpoint — расширяет attack surface и scope.
3. **Presentation shape.** Views получают нормализованный закрытый массив `registrationNumber`, `address`, `entrance`, `factoryNumber`; пустые номера преобразуются в явные русские подписи только на presentation boundary и всегда HTML-экранируются. ID остаются только в технических местах. Общий helper/ViewSupport и CSS не меняются: используются существующие блочные элементы `.fm2-order-object`, естественный wrap и shlz-компоненты.
4. **History semantics.** История показывает текущий effective-контекст объекта, но её revision rows и файлы остаются историческими snapshots. Это не ретроактивное изменение документа и не запись факта.
5. **Verification.** Root пишет нормативный executable spec и focused RED HTTP/browser test. Planner определяет lane/reviews; отдельный executor реализует, независимые reviewer(s) проверяют требуемые Gates. Проверка использует уникальные disposable DB/storage/session/runtime resources; architecture impact ограничен соблюдением существующих dependency rules, без policy changes.

## Risks / Trade-offs

- [Effective read после authorization может добавить один DB read на GET] → запрашивать только четыре поля через существующего owner, не загружать object-card projection.
- [History может быть ошибочно воспринята как snapshot реквизитов] → явно назвать контекст текущим и не помещать effective values внутрь строк редакций.
- [Пустые и whitespace-only legacy значения могут дать неясную подпись] → presentation normalization считает их отсутствующими; owner semantics и сохранённые данные не меняются.
- [Длинные/враждебные значения ухудшат layout или XSS] → обязательные escaping assertions и narrow viewport browser evidence без нового CSS.

## Migration Plan

DDL/data migration и deployment ceremony не нужны. После Gates 1–5 branch публикуется как PR; rollback — revert ограниченных read/view/test изменений, поскольку persistence и документы не меняются.
