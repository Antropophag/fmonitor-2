# BITRIX-STARTUP-CONFIG-001 — correction of test harness

Дата: 2026-09-08. Это дополнение к исходному RED, а не его замена или
ретроспективное свидетельство.

После появления production implementation новый публичный тест
`tests/Deployment/bitrix_startup_config_001_test.php` стал GREEN. Существующий
`rapid-pilot/verify-deployment-contract.php` остался RED по причине двух дефектов
наблюдателя:

```text
RuntimeException: make up does not start the complete stack after preparation
```

Characterization искал literal `compose up --detach --wait`, хотя Make штатно
вызывает ту же команду через объявленную переменную `$(COMPOSE)`. Следующая
проверка host PHP также ошибочно совпадала бы с `--entrypoint php` внутри
`docker run`, то есть с PHP контейнера.

Исправление распознаёт `$(COMPOSE) up --detach --wait` и удаляет неоднозначный
статический поиск слова `php`. Поведение не ослаблено: публичный изолированный
Make harness подменяет host `php` завершающимся с кодом 97 скриптом, проверяет
успешный Docker-only запуск, порядок preparation → Compose startup, а также
ненулевой результат и отсутствие Compose startup при отказе подготовки.

После коррекции требуются независимый повторный test review и GREEN обоих
focused checks.
