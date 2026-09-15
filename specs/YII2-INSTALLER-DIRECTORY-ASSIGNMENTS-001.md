# YII2-INSTALLER-DIRECTORY-ASSIGNMENTS-001 — актуальные закрепления из native application

Status: `ACCEPTED_FOR_GATE_2`
Actor: активный пользователь FMonitor с exact capability `installers.read`
Public seam: штатный native application workflow и последующий `GET|HEAD /pilot/installers`

## Простыми словами

После применения подписанного распоряжения штатным workflow справочник монтажников
должен сразу показывать закреплённые объекты. Источник текущего закрепления — последняя
append-only application запись монтажного дела, а не прежний registration status и не
rapid-pilot/legacy поля. История application записей остаётся неизменной; свободный
монтажник явно видит «Нет действующих закреплений».

## A1. Authoritative current assignment

После успешной последовательности native public actions: выбор состава, загрузка
приемлемого original и его штатное application — `GET /pilot/installers` показывает
для каждого выбранного монтажника объект из последней по `application_sequence`
строки `fm2_assignment_order_applications` соответствующего дела.

Состав читается из immutable `selected_snapshot_json.selectedInstallers`; ссылка,
адрес и существующий регистрационный номер объекта — из
`fm2_installation_cases.legacy_installation_object_id` и `fm_maintable`. Эти legacy
поля описывают объект, но не определяют наличие или состав закрепления.

Ни `fm_maintable.installator*`, ни rapid-pilot, ни registration-state старых
`fm2_assignment_orders` не являются источником current assignment. Read model не
создаёт и не изменяет facts.

## A2. History and replacement

Если дело имеет несколько application записей, текущим является только максимальный
`application_sequence`. Монтажники предыдущего состава, отсутствующие в последнем
immutable snapshot, больше не считаются закреплёнными; новая application не изменяет
и не удаляет прежние application rows или их JSON snapshots.

Один монтажник может одновременно иметь текущие закрепления на нескольких делах;
они показываются один раз каждое в устойчивом порядке по tabId и object id.
Повторный read не меняет application history.

## A3. Empty state and filters

Delivered монтажник, которого нет ни в одном последнем application snapshot,
показывается с текстом «Нет действующих закреплений». Фильтр `assigned` включает
только монтажников из authoritative current applications, `free` — только остальных;
summary `assigned` считается по уникальным монтажникам с таким текущим закреплением.
Пустой кадровый каталог сохраняет отдельный существующий empty state.

## A4. Compatibility and failures

Исторические registered-order данные могут читаться только как bounded compatibility
fallback для дел, у которых ещё нет ни одной native application записи. Как только
первая application существует, она полностью владеет current composition данного
дела и legacy rows не смешиваются с ней.

Malformed/missing application schema или некорректный обязательный current snapshot
даёт существующий sanitized `503`/`Retry-After: 60`, а не молчаливый fallback к stale
legacy composition. Query/read остаётся bounded, без per-row SQL, DML, runtime DDL,
rapid-pilot load и раскрытия внутренних данных.

## A5. Scope and verification

Срез меняет только read model закреплений справочника. Он не redesign-ит раздел #19,
не добавляет управление инженером #52, checklist/offline #131, Bitrix #15 или OTIZ
#66 и не меняет native application command owner.

Gate 2 обязан воспроизвести RED сквозным regression через реальные native HTTP
selection/upload/application и затем directory GET, отдельно доказать replacement,
history preservation, explicit free empty state, assigned/free filters, read-only
поведение и отсутствие rapid-pilot в runtime closure. Focused GREEN, architecture
check, planner-selected independent reviews и один exact-source CI обязательны.
