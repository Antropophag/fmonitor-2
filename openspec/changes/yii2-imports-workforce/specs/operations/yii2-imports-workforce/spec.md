## Purpose

Дать оператору единый закрытый Yii2 console transport для существующего production case import без изменения eligibility, владельца данных или истории.

## ADDED Requirements

### Requirement: Закрытый case-import command
Production SHALL предоставлять `php bin/yii case-import/run` с обязательным последним `--interactive=0` и от `1` до `100` уникальных options `--object-id=<canonical positive int64>`. Неизвестные, сокращённые, повторные или переставленные inputs SHALL отклоняться до DB access и mutation; terminal stdout SHALL содержать один закрытый JSON outcome, stderr SHALL быть пуст.

#### Scenario: Валидная граница количества объектов
- **WHEN** оператор передаёт один либо сто уникальных допустимых object IDs
- **THEN** command принимает полный набор и вызывает case import ровно один раз

#### Scenario: Неверный transport input
- **WHEN** отсутствует ID, передан 101-й ID, duplicate, overflow, non-canonical ID, неизвестный option либо неверно расположен `--interactive=0`
- **THEN** command возвращает configuration failure без соединения и durable facts

### Requirement: Полная совместимость case import
Command SHALL сохранить `PILOT-CASE-IMPORT-001`: eligibility, all-or-nothing batch, repeat, concurrent import, schema/database failures, bounded retry и reconciliation неизвестного commit outcome. Успех SHALL объявляться только после доказательства ожидаемых durable facts.

#### Scenario: Успех, повтор и конкуренция
- **WHEN** допустимый набор импортируется впервые, повторно или конкурентно
- **THEN** observable JSON/exit и durable cases/history совпадают с утверждённым oracle, а каждый объект создаётся не более одного раза

#### Scenario: Rejection или неопределённый commit
- **WHEN** объект не проходит eligibility либо commit outcome нельзя безопасно reconcile
- **THEN** command не объявляет ложный успех, сохраняет исходные данные и возвращает установленную закрытую причину

### Requirement: Один owner, alias и package
Yii controller SHALL быть transport adapter к одной shared composition с одним mysqli lifecycle и одним `PilotCaseImporter`. SQL, import rules или второй transaction owner в controller/alias запрещены. Retained legacy path SHALL делегировать той же composition и сохранять stdout/stderr/exit/durable parity.

#### Scenario: Direct и alias parity
- **WHEN** одинаковый success, rejection, dependency failure или replay вызывается через Yii route и retained legacy syntax
- **THEN** outcomes и durable facts эквивалентны, а owner вызывается ровно один раз на invocation

#### Scenario: Production load closure
- **WHEN** проверяется production artifact и достижимый load set command
- **THEN** присутствуют Yii и case-import owner, но отсутствуют `rapid-pilot`, demo, web/session/jobs composition и независимый legacy autoloader

### Requirement: Авторизация и redaction не ослабляются
Перенос SHALL не расширять deployment/operator admission. DB credentials/coordinates/prefix, source values, exception/trace и SQL MUST отсутствовать в output; UNKNOWN не считается success или разрешением deployment.

#### Scenario: Ошибка конфигурации или runtime
- **WHEN** обязательный environment input неверен, БД недоступна либо возникает неизвестный Throwable
- **THEN** command возвращает установленный redacted outcome без pre-validation facts и без раскрытия защищённых значений
