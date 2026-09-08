## Why

Подписанный original уже принимается через native HTTP, но ещё не создаёт
действующего закрепления. ФКР нужен отдельный переход от принятого документа
к применённому составу; directory и original-read scope должны читать этот факт.

## What Changes

- Public application seam применения initial и последовательного нового
  распоряжения, с exact permission, request replay и append-only audit.
- Immutable application facts, текущий состав/назначенный инженер и проекции
  закреплений монтажников. Старые checklist attribution не переписываются.
- Effective date из документа по existing owner decision2026-09-02; отдельное
  opening не выполняется этим действием. HTTP и directory wiring проходят gates.

## Capabilities

### New Capabilities
- `pilot/assignment-order-composition-application`: применение принятого original.

### Modified Capabilities

## Impact

Owning module планируется AssignmentOrderComposition; оригинал и проверка его
immutable evidence остаются у AssignmentOrderOriginal. Общий case lock защищает
upload/correction/selection/application от гонок. Новая additive schema получает
canonical version только на actual frontier после gated engine/command readiness.

Не входят: legacy writer migration, historical imports, manual registration,
автоматическое opening, официальный premium calculation, изменение protected E2E.
Read-only original evidence dependency может разрабатываться независимо.

## Owner decision — 2026-09-07

Ранее вопрос повторного применения после correction до opening был NEEDS_GRILL.
Владелец явно подтвердил: разрешить отдельное повторное применение исправленного
оригинала с сохранением прежнего application fact. Загрузка/исправление сами
ничего не применяют. После открытия history/checklist не переписываются.
Дата действия нового распоряжения уже утверждена как documentDate.

Также разрешено назначение по подтверждённому текущему employed status полного
кадрового снимка при неизвестной дате приёма; дата остаётся неизвестной. Это
не отменяет отдельные gates normalization/publication/catalog/eligibility и не
задаёт freshness threshold. Controlling record:
`docs/operations/composition-reapply-unknown-employment-owner-decision-2026-09-07.md`.

## Next planning frontier

Конкретный owner blocker снят. Следом создаются executable application contract,
design/delta/tasks и проводится independent Gate1. UNIQUE/order, correction
applicability, schema/version и code ещё не выбраны/не утверждены. Read-only
application-reference и history/download dependencies уже прошли свои gates.
