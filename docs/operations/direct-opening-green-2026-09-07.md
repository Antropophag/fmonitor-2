# Прямое открытие из карточки — focused GREEN

Выполнено новое решение владельца об отсутствии отдельного ручного применения.
Принятый original/current selected composition показывается уже до application;
карточка даёт уполномоченному actor одну форму открытия. Original uploader и
opener могут быть разными пользователями; действия original не открывают работы.

Новый public command OpenConfirmedOriginalCommand исполняется через
ProductionConfirmedOriginalOpeningFactory. Existing application/opening wrappers
сохраняются; их общие внутренние операции не владеют транзакцией. Compound owner
заранее получает reference/clock, затем одной RC transaction проверяет/применяет
composition и открывает case. Шаблон, application, audit/event и opening откатываются
вместе при поздней ошибке. Standalone apply по-прежнему требует composition.apply.
Успешный opening сохраняет requestId/fingerprint для exact replay без новых facts.

Focused compound test: success/replay, invalid date, stale original, authorization
refusal, missing-template late rollback PASS. Original uploader18 сохраняется,
application/opening actor19. Прежний standalone apply/reapply/open smoke и новый
HTTP single-command test PASS. Смена ошибочного legacy event expectation на
существующий native installation_opened_from_original не меняет production facts.

Private `runtime/direct-opening-browser-20260907/run.log`:
- uploadReturnedToCard и correctionReturnedToCard=true;
- openingActions=[open_confirmed], separateOpener=true;
- 41items,7sections,7photos,85→100%; ошибки=[];
- все9 отметок сохранены на275 frames и при pending reload;
- ПТО/декларация отправлены с default датами без взаимодействия с date fields.
Первая попытка runner имела JS duplicate-identifier setup error; отдельный
setup-syntax-failure.log сохранён, это не product RED. Исправленный runner проверен
node --check и завершился PASS. Все fixtures синтетические; объект966 не менялся.

Независимые review находятся в reviews/code/MANUAL-PILOT-CONFIRMED-ORIGINAL-OPENING,
MANUAL-PILOT-DIRECT-OPENING-CARD и MANUAL-PILOT-OPENING-TRANSITION-HTTP с датой2026-09-07.
Это focused readiness; полный make verify запускается на зафиксированном candidate.
