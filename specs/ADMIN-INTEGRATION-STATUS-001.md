# ADMIN-INTEGRATION-STATUS-001 — read-only состояние интеграций

## Простыми словами

Системный администратор получает одну страницу, где реальные сохранённые попытки и успехи кадровой и ERP-синхронизации видны раздельно, а проблемные записи и failed jobs доступны небольшими страницами. Экран ничего не запускает и не раскрывает технические секреты; отсутствующие факты честно остаются неизвестными.

## Actor и public seam

Actor: активный локальный пользователь с `access.administer`.

Public seam: `GET|HEAD /pilot/admin/integrations` и его server-rendered HTML. Все требования delta spec `openspec/changes/add-integration-status-admin/specs/admin/integration-status/spec.md` нормативны и включены сюда ссылкой; при расхождении этот стабильный контракт и issue #30 от 24.09.2026 имеют приоритет.

## Acceptance matrix

- A1: canonical server authorization покрывает admin, non-admin, guest и blocked actor; ссылка имеет ту же capability.
- A2: Bitrix workforce и ERP equipment показывают отдельные latest attempt/latest success, result, allowlisted failure и реально доступные counts; never-run, empty-success, failed-after-success и unavailable различимы.
- A3: workforce missing, ERP missing/ambiguous и dead jobs читаются отдельными bounded SQL pages; invalid/out-of-range input безопасен.
- A4: GET/HEAD и rejected methods не изменяют ни одной таблицы и не вызывают transport/job/retry/outbox.
- A5: HTML использует shlz-ui contracts, экранирует значения, сохраняет независимые page parameters и работает при narrow viewport/keyboard navigation.
- A6: payload_json, receipt_json, raw exception/stack, DSN, URL, tokens и secrets никогда не публикуются.
- A7: gaps первого slice явно названы: workforce identity-conflict row details и явный configured/disabled state сейчас не сохраняются; retry и остальные integrations остаются в #30.

## Persistence, replay и adjacent flows

Экран не является command seam, поэтому replay/concurrency означают повторяемые read-only GET/HEAD без новых facts. Параллельный writer может дать один из согласованных committed snapshots; reader не держит write locks. Schema, backup/restore, imports, cron, deployment readiness, calendar, effective values, ОТиЗ и financial flows не меняются.
