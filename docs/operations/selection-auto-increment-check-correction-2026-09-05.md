# Selection schema — AUTO_INCREMENT CHECK correction

Дата: 2026-09-05. Автор `/root`.
В процессе exact registry drafting обнаружена невозможность прежних CHECK на
AUTO_INCREMENT registry/event/audit IDs. Это техническая ошибка draft v0.4–0.6,
не реализованная таблица и не отмена уже записанных independent typed-flow reviews.

MariaDB запрещает AUTO_INCREMENT column в CHECK expressions:
[официальный CONSTRAINT contract](https://mariadb.com/docs/server/reference/sql-statements/data-definition/constraint).
В task-owned disposable schema выполнен private data-free DDL constructibility
probe. Source script находится в private archive admission-20260905-w7wmv_ye,
`registry-ddl-constructibility.php`; никаких application code/tests не создано.
Transcript stdout из tool execution, exit0:

```text
AUTO_INCREMENT_CHECK_REJECTED errno=1901
AUTO_INCREMENT_WITHOUT_CHECK_CREATED
OWNED_SCHEMA_CLEANUP_OK
```

Ни одной существующей таблицы/данных probe не менял. Созданная случайная database
удалена exact identity в finally; независимый catalog query подтвердил отсутствие.
Это только Gate1 constructibility evidence, не missing-production RED или GREEN.

Исправление в selection v0.7 и registry draft: physical type остаётся BIGINT
UNSIGNED AUTO_INCREMENT для совместимости FK. Positive/PHP-int bounds остаются
обязательны в preflight, allocator/storage pre-commit validation и read integrity;
invalid generated ID не commit-ится, invalid persisted ID не читается как valid.
Не добавлены триггеры, второй allocator или новые product outcomes.
Fresh independent technical review требуется до RED.
