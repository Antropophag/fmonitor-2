## Context
Owner2026-09-10 разрешил инструменты учёта, отдельную ветку и PR без merge/deployment. Baseline до изменения поведения: /tmp/fmonitor-harness-baseline/root-baseline.json. Отсутствующие счётчики UNKNOWN.
## Goals / Non-Goals
Автоматизировать механику существующих Gates 1–5. Не создавать новый scheduler, inventory, LLM hook, backend, GREEN cache или approval.
## Decisions
Публичный seam — Python CLI tools/delivery/harness.py и расширения change-verification.py. Логи вне checkout, записи append-only с уникальным ID. Дочерний argv без shell; явный bash -o pipefail для pipelines. Проверки последовательно. Source включает содержимое tracked/untracked, staged/unstaged и modes; environment/fixtures идентифицируются отдельно. Не сохранять значения секретов в metadata.
review-source остаётся владельцем reconstructible snapshots. До capture проверять состав и whitespace; форматирование по затронутым файлам до snapshot. Пакет связывает контракт, input/план, evidence и source, независимо от APPROVED.
Codex hooks использовать только после проверки установленной схемы и smoke. Фактическая telemetry через доступный интерфейс; coverage/UNKNOWN явно. GitHub ограниченный запрос на старте/refresh, без polling каждой команды.
Продуктовые DB/schema/auth/history/runtime не меняются; соответствующие migration gates не применимы. Consumer mapping — только подтверждённые владельцы InspectionEvidence/ChecklistSync, существующие suites; UI layout не наследует все E2E. Full CI не сокращается.
## Risks / Trade-offs
Hook trust/configuration и разница desktop/CLI требуют явного doctor и реального smoke. Token telemetry может быть недоступна данной сессии. Локальные логи не переносятся в CI как primary evidence; CI сам исполняет финальный commit.
