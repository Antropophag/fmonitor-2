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

## NEEDS_GRILL

Владельцу задан один новый вопрос: после application, но ДО открытия original
исправлен на другую дату — допускается ли отдельное повторное применение новой
revision с сохранением прежнего application fact, либо applied date фиксируется.
Нельзя заранее выбрать UNIQUE/order или correction semantics, пока ответа нет.
Дата действия нового распоряжения уже утверждена как documentDate и повторно
не спрашивается. После открытия history/checklist не переписываются.
