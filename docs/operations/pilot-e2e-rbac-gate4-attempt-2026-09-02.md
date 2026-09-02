# PILOT-E2E-RBAC-FIXTURES-001 — попытка Gate 4

Дата: 2026-09-02  
Роль: отдельно назначенный GREEN implementer `/root/e2e_rbac_green`  
Статус: **PREDECESSOR_BLOCKED; Gate 4 не завершён**

## Выполненный минимальный production diff

- Configured `GET/HEAD /pilot/objects` передан существующему canonical local
  authorization seam вместо legacy process-capability ветки E2E coordinator.
- Nonmigrated `GET/HEAD /pilot/objects/{id}` сохраняет прежний card admission и
  не получает новое требование local `objects.read`.
- Tests не изменялись; client headers, cookies и `REMOTE_USER` не получили
  authority.

## Фактическая граница

После изменения утверждённый verifier проходит actor 18 admission, actor 19
denial, authority matrix, repeat/snapshot checks, canonical list DOM и открытие
карточки. Следующая ошибка:

```text
launch action visible Сформировать распоряжение
Expected: true
Actual: false
```

Это не RBAC RED. Ожидание относится к прежнему journey формирования шаблона,
тогда как принятый владельцем pilot workflow допускает прямую загрузку PDF-
оригинала и больше не гарантирует эту launch action. Возвращать старую кнопку
для прохождения fixture запрещено.

## Predecessor dependency

Продолжение этого E2E slice требует сначала завершить
`replace-pilot-registration-with-original-upload`, затем согласовать отдельный
composition/opening slice и обновить golden journey через новый Gate 2 и fresh
independent Gate 3. После этого `PILOT-E2E-RBAC-FIXTURES-001` можно повторно
провести до approved prepare/artifact boundary.

## Проверки

- `php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php`
  — PASS два раза.
- `git diff --check` — PASS.
- `openspec validate pilot-e2e-rbac-fixtures --strict` — PASS.
- `make architecture-check` — внешняя concurrent regression:
  `sql_ownership` и `hotspot_ratchet` в `app/PilotHttp/PilotHttp.php`; файл не
  изменялся этим implementer в данном Gate 4 attempt.

Task 3.1 остаётся OPEN; GREEN и Done не заявлены.
