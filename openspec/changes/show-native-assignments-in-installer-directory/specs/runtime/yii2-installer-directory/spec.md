## ADDED Requirements

### Requirement: Справочник показывает authoritative native закрепления
Система SHALL после штатного применения распоряжения показывать в справочнике
монтажников состав последней application каждого монтажного дела, не создавая
новых facts и не используя legacy или rapid-pilot как источник истины.

#### Scenario: Применённое распоряжение видно в справочнике
- **WHEN** уполномоченный пользователь штатно выбирает состав, загружает original, workflow создаёт application, а затем читает `/pilot/installers`
- **THEN** выбранный монтажник видит объект и документ-основание, а application history остаётся неизменной

#### Scenario: Новый состав заменяет только текущую проекцию
- **WHEN** у дела есть несколько append-only applications с разными составами
- **THEN** справочник использует только максимальную application sequence, не изменяя и не удаляя прежние applications

#### Scenario: У монтажника нет текущих закреплений
- **WHEN** delivered монтажник отсутствует во всех последних application snapshots
- **THEN** его строка содержит «Нет действующих закреплений», фильтр `free` включает его, а `assigned` исключает

#### Scenario: Дело ещё не перешло на native application
- **WHEN** у дела отсутствуют application rows, но существует действующее совместимое registered-order закрепление
- **THEN** справочник сохраняет прежнюю read-only проекцию этого закрепления

#### Scenario: Current application повреждена
- **WHEN** последний обязательный application snapshot отсутствует или некорректен
- **THEN** чтение завершается sanitized `503` без fallback к stale legacy составу и без новых facts
