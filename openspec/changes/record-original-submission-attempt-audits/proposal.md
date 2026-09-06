## Why

Владелец утвердил отдельную запись каждого отказа из-за отсутствия прав. Текущая команда пропускает такие записи, а существующие UNIQUE и FK аудита не допускают повторных отказов и аудита retryable ошибок без terminal request. Это обязательный блокер доказуемой загрузки оригинала.

## What Changes

- Slice `ASSIGNMENT-ORDER-ORIGINAL-ATTEMPT-AUDIT-001`: один прежний public application seam `submitAssignmentOrderOriginal` сохраняет каждую запрещённую попытку, не раскрывая и не изменяя ранее принятый результат.
- Отдельный audit persistence port сохраняет denial и best-effort STREAM/STORAGE audit; terminal business rejection остаётся на прежнем атомарном repository port.
- Forward schema v3 снимает только препятствующие UNIQUE/FK ограничения аудита и разрешает два retryable failure reason. Исторические строки и все другие таблицы сохраняются.
- Канонический migration runner получает оригинальную семью v3 на актуальном frontier после revalidation; runtime DDL запрещён.
- Exact безопасные diagnostics фиксируют недоступность persistence/audit без payload, повторных операций и ложного успеха.

## Capabilities

### New Capabilities

- `pilot/assignment-order-attempt-audit`: отдельный факт каждой запрещённой попытки и безопасный аудит ошибок original submission.

### Modified Capabilities

Нет: parent original capability пока находится в активном change, а не main specs. Новый executable contract явно уточняет его audit section; старые evidence/reviews остаются неизменными.

## Impact

Actor — вызывающий original command пользователь; oracle — `original-denied-attempt-owner-approval-2026-09-06.md`, parent ORIGINAL-UPLOAD и pre-clock audit contract audit. Release value — полная история отказов при сохранении неизменности оригинала и retryable загрузки. Затрагиваются application finisher/dependencies, MariaDB audit writer/reader, InstallationProcess migration и canonical runner.

Вне scope: UI группировка журнала, новые права, выбор состава, HTTP, открытие, maintenance, Quality Graph и launch approval. Product NEEDS_GRILL отсутствует: политика каждой попытки уже утверждена. Технический Gate1, RED, independent Gate3 и Gate5 обязательны; этот proposal не является их заменой.
