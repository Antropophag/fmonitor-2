# BITRIX-STARTUP-CONFIG-001 — JSON escape RED

Дата: 2026-09-08. Этот RED добавлен по Gate 5 finding после первоначальной
реализации и не заменяет исходное RED evidence.

Добавлен один негативный fixture:

```dotenv
FMONITOR_BITRIX_DEPARTMENT_IDS_JSON=["\u0037\u0031"]
```

Контракт запрещает escape-последовательности в literal `.env` значениях. Команда:

```text
php -l tests/Deployment/bitrix_startup_config_001_test.php
php tests/Deployment/bitrix_startup_config_001_test.php
```

Фактический результат текущей реализации:

```text
No syntax errors detected in tests/Deployment/bitrix_startup_config_001_test.php
TestFailure: invalid env 8 fails
Expected: true
Actual: false
exit 255
```

Тест RED по требуемой причине: JSON decoder превращает escaped digits в строку
`71`, после чего существующий `WorkerConfiguration` принимает её как department
ID. Production fix до независимого одобрения этого тестового дополнения не
вносился тестовым потоком.
