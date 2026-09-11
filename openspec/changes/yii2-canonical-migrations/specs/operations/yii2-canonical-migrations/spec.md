## Purpose

Дать оператору единый production Yii2 console seam для воспроизводимого и безопасного применения canonical schema migrations на новой и существующей базе без второго ledger или изменения данных при повторе.

## ADDED Requirements

### Requirement: Единый публичный migration seam
Production migration SHALL запускаться как `php bin/yii schema-migrate/run` через общий Yii2 console entrypoint и SHALL делегировать ровно одному canonical migration application и ledger. Команда SHALL принимать конфигурацию БД и допустимый table prefix только из явно перечисленных environment inputs, не из demo manifest или web runtime.

#### Scenario: Успешный fresh install
- **WHEN** оператор запускает canonical migration command с полной валидной конфигурацией против пустой совместимой MariaDB
- **THEN** команда применяет утверждённый каталог ровно один раз, выводит один JSON outcome, завершает работу кодом `0`, а итоговая schema проходит существующий exact catalogue oracle

#### Scenario: Повторный запуск
- **WHEN** оператор повторяет ту же команду против уже полностью мигрированной БД
- **THEN** команда возвращает успешный idempotent outcome без повторного выполнения завершённых фаз и без изменения schema или строк

#### Scenario: Upgrade существующей БД
- **WHEN** БД содержит совместимое завершённое состояние предыдущей canonical версии и сохранённые production rows
- **THEN** команда применяет только отсутствующие утверждённые фазы, сохраняет прежние rows/history и приводит schema к exact текущему каталогу

### Requirement: Fail-closed конфигурация и ошибки
Команда SHALL проверять обязательные параметры, порт и table prefix до соединения или mutation. Ошибки SHALL иметь стабильные sysexits и один JSON outcome; stdout/stderr SHALL не раскрывать пароль, DSN, token или необработанный exception/stack trace.

#### Scenario: Неверная конфигурация
- **WHEN** обязательный параметр отсутствует, порт вне диапазона или prefix не соответствует разрешённой грамматике
- **THEN** команда завершается кодом `64` с причиной `CONFIGURATION_INVALID`, не подключается к БД и не создаёт schema facts

#### Scenario: БД недоступна
- **WHEN** валидная конфигурация указывает на недоступную БД либо charset negotiation завершается ошибкой
- **THEN** команда возвращает существующий redacted database-unavailable outcome и sysexit без DDL/DML после отказа

#### Scenario: Неожиданная ошибка
- **WHEN** canonical owner выбрасывает нераспознанную ошибку
- **THEN** команда возвращает общий redacted software-failure outcome, не печатает внутренние детали и не объявляет частичный результат успешным

### Requirement: Сериализация и восстановимость
Конкурентные migration commands SHALL сериализоваться существующей database lock boundary. Ни один запуск SHALL не использовать Yii migration ledger параллельно canonical ledger; после остановки или отказа следующий запуск SHALL либо безопасно продолжить незавершённую restartable фазу, либо fail closed на несовместимом состоянии.

#### Scenario: Два конкурентных запуска
- **WHEN** два независимых процесса запускают команду одновременно для одной БД и prefix
- **THEN** только владелец lock выполняет migration work, второй получает установленный bounded lock outcome, а schema/ledger не содержат двойных фаз

#### Scenario: Совместимый restart
- **WHEN** предыдущий процесс остановился после durable завершения части каталога в состоянии, которое существующий canonical owner определяет как restartable
- **THEN** следующий запуск продолжает с первой незавершённой фазы без удаления или повторной записи завершённых facts

#### Scenario: Несовместимое частичное состояние
- **WHEN** schema или ledger не соответствуют ни завершённому, ни разрешённому restartable состоянию
- **THEN** команда fail closed, сохраняет исходные schema/data и не выполняет destructive rebuild

### Requirement: Production callers и временная совместимость
Production Compose и Make migration callers SHALL вызывать Yii2 console seam. Если legacy bin path временно сохраняется, он MUST быть тонким alias к тому же Yii command и SHALL иметь идентичные outcomes; отдельная migration логика, autoload catalogue или ledger в alias запрещены.

#### Scenario: Production packaging
- **WHEN** собран production runtime image и разрешён deployment profile
- **THEN** migration service запускает Yii2 console command из общего Composer/configuration runtime и не загружает `rapid-pilot` либо demo bootstrap транзитивно

#### Scenario: Legacy alias
- **WHEN** retained caller запускает прежний migration bin path с теми же inputs
- **THEN** observable JSON, exit code, schema и ledger совпадают с прямым Yii2 command, а alias не содержит собственной migration composition
