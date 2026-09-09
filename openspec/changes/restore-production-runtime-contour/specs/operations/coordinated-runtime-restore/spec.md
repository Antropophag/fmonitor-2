## Purpose

Задаёт проверяемый операторский контракт согласованного backup и восстановления
production runtime в отдельный контур с сохранением фактов, истории и закрытых файлов.

## ADDED Requirements

### Requirement: Backup bundle согласован между DB и private state

Авторизованный оператор SHALL создавать bundle только после остановки web/php и
других существующих writers. Bundle MUST содержать логический MariaDB dump,
архив state volume и manifest с версией формата, source commit, image identity,
DB/state SHA-256, а также отсортированный inventory private файлов: относительный
путь, размер и SHA-256. Manifest и архив MUST исключать injected secrets и
operator environment. Операция MUST писать новый task-owned destination и MUST
отказать, если writers активны либо destination существует.

#### Scenario: Согласованный backup

- **WHEN** оператор останавливает writers и запускает backup в новый private destination
- **THEN** команда создаёт полный bundle, атомарно публикует manifest последним и возвращает его идентификатор без секретов

#### Scenario: Активный writer или занятый destination

- **WHEN** web/php работает либо bundle destination уже существует
- **THEN** команда отказывает до публикации готового bundle и не изменяет runtime данные или существующий destination

### Requirement: Restore допускает только целый bundle и пустой target

Авторизованный оператор SHALL восстанавливать bundle только в явно заданный пустой
изолированный target. Команда MUST до первой target mutation проверить manifest,
допустимые относительные пути, размеры и SHA-256 всех компонентов, exact source/image
identity и отсутствие секретов в manifest. Restore MUST отказать при отсутствующем,
лишнем, изменённом, symlink или traversal member и при непустом DB/state target.
Pre-existing partial backup siblings MUST сохраняться exact: backup использует
только fresh unique sibling и никогда не усыновляет либо не очищает прежний partial.
Publication collision MUST сохранять foreign final destination exact и удалить
только созданный текущей операцией task-owned partial.
Restore MUST выполняться из exact source image/commit bundle, создать canonical
schema его catalogue, импортировать data-only dump и восстановить сохранённые
AUTO_INCREMENT next values, включая gap после удалённого high id.

#### Scenario: Повреждённый bundle

- **WHEN** dump, state member или manifest отсутствует, изменён либо содержит недопустимый путь
- **THEN** restore возвращает стабильный ненулевой outcome и оставляет пустой target без DB tables и private файлов

#### Scenario: Непустой target

- **WHEN** target DB или state содержит любой существующий объект
- **THEN** restore отказывает без repair, merge, overwrite или удаления target данных

### Requirement: Успешный restore сохраняет факты, историю, auth и private bytes

Restore SHALL загрузить DB и state как одну проверяемую операцию drill, восстановить
private paths с runtime UID/GID и modes и завершиться только после read-only
readiness. Проверка MUST сравнить точные отсортированные строки пользователей,
ролей, назначений, opening, checklist/photo metadata, completion и original history,
а также exact SHA-256 PDF, фото и session material. Авторизованный browser с
восстановленной сессией MUST читать связанный объект и private документ; обычная
изменяющая операция после restore MUST сохранять новую append-only историю.

#### Scenario: Полное восстановление в отдельный контур

- **WHEN** оператор восстанавливает валидный bundle в пустой target и запускает сохранённый exact image
- **THEN** readiness проходит, точные DB/file/session сверки совпадают и восстановленная авторизация допускает обычный маршрут без повторного bootstrap

#### Scenario: Ошибка DB или state после частичной загрузки

- **WHEN** target import или materialization state завершается ошибкой
- **THEN** команда не объявляет restore готовым, сохраняет диагностируемый failed target и не переключает source либо другой contour

### Requirement: Exact-image update и ограниченный rollback проверяются отдельно

Update drill MUST сначала сохранить backup и previous source/image identity, затем
запустить reviewed new exact image, выполнить отдельные migrations и проверить
readiness, существующую session, private files, историю и новый write. При
additive-compatible schema оператор SHALL иметь возможность вернуть previous web/php
image без удаления новых schema/history. Для несовместимой migration документация
MUST требовать restore-forward из backup в новый пустой contour и MUST NOT обещать
произвольный downgrade DB.

#### Scenario: Совместимое обновление

- **WHEN** новый exact image применяет reviewed additive migrations к восстановленному contour
- **THEN** все сохранённые факты и файлы доступны, новая операция проходит, а возврат previous web/php image не удаляет schema или историю

#### Scenario: Несовместимое обновление

- **WHEN** migration объявлена несовместимой с previous image
- **THEN** runbook запрещает blind image rollback и направляет оператора к restore-forward в новый пустой target

### Requirement: Evidence остаётся private и не утверждает нерешённые показатели

Drill SHALL записывать вне repository фактические timestamps начала/конца, elapsed
duration, source bundle identity, source/image IDs, команды и результаты сверки.
Секреты и primary backup material MUST оставаться private. Реализация MUST NOT
задавать retention, SLA, RPO, RTO или допустимую потерю данных без решения владельца.

#### Scenario: Отчёт выполненного drill

- **WHEN** restore/update drill завершён
- **THEN** private evidence содержит измеренную длительность и результаты, а публичный отчёт содержит только безопасные identity/outcomes и помечает retention/RPO/RTO как NEEDS_GRILL

### Requirement: Jobs/outbox recovery отложен до #34

Базовый drill SHALL явно сообщать, что worker/jobs/outbox не проверены. После #34
контракт MUST быть расширен отдельным reviewed slice для quiesce, lease, retry,
deduplication и неопределённой внешней доставки без реальных sends. Отсутствие #34
MUST NOT блокировать восстановление текущих DB/files/history/auth/session.

#### Scenario: Drill до #34

- **WHEN** текущий runtime не имеет утверждённого jobs/outbox механизма
- **THEN** базовый restore выполняется без worker и внешних отправок, а evidence явно фиксирует deferred coverage
