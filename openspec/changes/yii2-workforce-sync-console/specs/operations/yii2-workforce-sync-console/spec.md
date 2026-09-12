## Purpose

Дать авторизованной операторской автоматизации единый закрытый console transport для канонической синхронизации кадрового каталога с сохранением доказательности и истории.

## ADDED Requirements

### Requirement: Закрытый операторский запуск синхронизации
Production SHALL предоставлять команду `php bin/yii workforce-sync/run --interactive=0`. Команда MUST отклонять позиционные аргументы, неизвестные или сокращённые options, повторы и неверное положение `--interactive=0` до обращения к Bitrix, БД или записи фактов. Успешный terminal stdout SHALL содержать один JSON outcome, stderr SHALL быть пуст.

#### Scenario: Допустимый запуск
- **WHEN** авторизованная операторская автоматизация вызывает точный command без дополнительных inputs
- **THEN** выполняется ровно один канонический synchronization run и возвращается его закрытый outcome

#### Scenario: Недопустимый transport input
- **WHEN** передан неизвестный, сокращённый, повторный или позиционный input либо отсутствует завершающий `--interactive=0`
- **THEN** команда возвращает configuration failure без network/DB access и durable facts

### Requirement: Канонические workforce facts и повторяемость
Команда SHALL сохранять утверждённые `BITRIX-WORKFORCE-DELIVERY-001`, `BITRIX-WORKFORCE-HISTORY-001` и `WORKFORCE-CANONICAL-RUNNER-001`: полный снимок доставляется и валидируется до публикации, один run публикуется атомарно, наблюдения и run history дописываются, а неизменный повтор не создаёт ложного material change. Неопределённый результат commit MUST сверяться по durable run identity и не считается успехом без доказанного результата.

#### Scenario: Полная успешная публикация
- **WHEN** Bitrix возвращает допустимый многостраничный полный снимок и БД принимает транзакцию
- **THEN** command объявляет успех только после атомарного durable результата с теми же counts/checksum/run identity, которые требует утверждённый oracle

#### Scenario: Неизменный повтор или конкурентный run identity
- **WHEN** тот же полный кадровый снимок доставляется повторно либо два запуска используют один run identity
- **THEN** история остаётся append-only, каталог не расходится, а observable outcome однозначно отражает уже принятый или завершившийся run

#### Scenario: Неполная доставка или неопределённый commit
- **WHEN** доставка, нормализация, schema/database operation или commit/reconciliation не доказывает полный результат
- **THEN** command не объявляет успех и не публикует частичный каталог

### Requirement: Единая composition и совместимый alias
Yii2 command, scheduled workforce job и retained legacy manual path SHALL делегировать одной общей production composition и одному application owner. Ни один transport adapter MUST не владеть workforce SQL, бизнес-правилами или отдельной транзакцией. Legacy manual path SHALL сохранять подтверждённые stdout/stderr/exit и durable outcomes прямой команды.

#### Scenario: Direct и alias parity
- **WHEN** одинаковый success, unchanged repeat, configuration failure, transport failure или database failure вызывается напрямую и через retained alias
- **THEN** terminal outcomes и durable facts эквивалентны, а application owner вызывается ровно один раз на invocation

#### Scenario: Production load closure
- **WHEN** проверяется достижимый production load set команды и alias
- **THEN** загружаются Yii2 и канонические workforce owners, но не `rapid-pilot`, demo, HTTP/session, OTIZ или независимая legacy composition

### Requirement: Полномочия и redaction не ослабляются
Перенос SHALL не расширять admission операторского запуска и не менять права штатных пользователей. Credentials, token contents/path, DB coordinates/prefix, персональные строки снимка, exception/trace и SQL MUST отсутствовать в terminal output и logs, проверяемых command contract. UNKNOWN SHALL считаться failure, а не разрешением публикации или deployment.

#### Scenario: Ошибка конфигурации, транспорта или runtime
- **WHEN** обязательная конфигурация отсутствует или неверна, Bitrix/БД недоступны либо возникает неизвестный Throwable
- **THEN** command возвращает закрытый redacted failure, не раскрывает защищённые значения и не оставляет частично опубликованный каталог
