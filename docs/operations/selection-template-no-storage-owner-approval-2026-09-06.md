# PDF-шаблон без хранения — controlling owner approval

Owner: пользователь текущей session,2026-09-06.
После пояснения, что для пилота достаточно выдавать сформированный PDF без
хранения версий, сохраняя состав, дату последнего формирования и аудит, owner
ответил: **«согласовано продолжай без хранения шаблонов»**.

## Approved scope

- По пользовательскому запросу формируется PDF с сегодняшней датой Europe/Moscow
  и выдаётся в ответе. Повторное формирование также использует сегодняшнюю дату.
- Новые template bytes, файлы и версии не сохраняются; отдельное хранилище,
  artifact/version tables и повторное скачивание исторических новых PDF не нужны.
- Immutable selection/order identity и состав не меняются. Сохраняются дата
  последнего успешного формирования для подстановки при загрузке и append-only
  audit факта формирования (actor/time/identity/date). Дата может быть read
  projection аудита; это не PDF storage.
- Подписанные оригиналы и corrections по-прежнему сохраняются неизменно.
  Template generation не устанавливает окончательную document date и не открывает
  работы. Прямая загрузка оригинала не требует предварительного PDF.
- Последующее owner clarification: **«нет никаких исторических пдф, вообще
  никаких исторических данных нет»**. Не требуется сохранение/перенос старых PDF
  или обратная совместимость ради исторических данных; см. fresh-launch record.

Этот explicit approval supersedes требование хранения новых версий PDF из
`selection-template-rerender-owner-approval-2026-09-06.md`; тот record сохраняется
как история прежнего толкования. Earlier compatibility planning про новый
template artifact owner/storage тоже superseded в этой части. Gates сохраняются,
но отдельный template file-storage/schema package из launch path исключён.
Никакого нового template storage implementation или DDL до этого решения не было.
