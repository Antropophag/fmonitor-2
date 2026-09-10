## Why
После очереди #76 следующий пользовательский путь должен целиком проходить
через Yii2: карточка → состав → оригинал → отдельное открытие → карточка.
Перенос сохраняет текущие native application contracts и корпоративный shlz UI.

## What Changes
- Yii controllers/session/CSRF/views/assets для текущего pre-opening journey.
- Reader карточки из PilotHttp переносится на Yii DAO; existing Selection Portal,
  Original Submission Query и History Reader подключаются целиком через их public APIs.
- Подключение существующих command owners целиком, без смешивания их mysqli
  транзакций с Yii DAO writes и без переноса предметных правил в HTTP.
- Удаление транзитивных rapid runtime asset reads у нужного PDF renderer через
  explicit owned asset с теми же bytes; сначала normative impact и RED.
- Полная HTTP/browser/authorization/history/failure/replay matrix по текущему
  owner-authorized поведению. Template сохраняет POST, raw original — PDF transport.

## Capabilities
### New Capabilities
- `runtime/yii2-preopening-journey`: текущая карточка, состав, оригинал и открытие
  через Yii2, с неизменной append-only историей и явными command owners.
### Modified Capabilities
Нет новых предметных правил; contradictory исторические HTTP/CSRF/UI clauses
будут перечислены в normative migration contract до Gate2, не ослаблены молча.

## Impact
App YiiRuntime + process read adapters/composition + owned assets; существующие
Original/Selection/Opening public command owners сохраняют целые atomic boundaries.
Общий SafeErrorHandler/security/session, verification inventory и соседние Yii
flows входят в impact. Working stand не переключается. Checklist/photos/OTIZ/
calendar/console остаются последующими срезами #76. Root автор spec/tests,
отдельный executor и независимый reviewer по development-process.

Нормативный контракт specs/YII2-PREOPENING-JOURNEY-001.md и mandatory
verification-input/plan подготовлены. Predecessor PR86 mergedf804f3f6; root
пишет полный RED candidate. Independent Gates3/5 и implementation ещё впереди.
Подробности evidence/navigation: docs/operations/yii2-preopening-preparation-2026-09-10.md.
