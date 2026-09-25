# Текущая цель — #258, этап 3: прогноз загрузки монтажников

Поручение владельца 2026-09-25: применить `add-installer-utilization-forecast` от clean predecessor `f9b05a298550f5b33e26fd6ab7328f25865150e6` и довести candidate до PR-ready. Шесть календарных недель показывают live-прогноз из authoritative assignments и effective dates независимо от daily observations; история остаётся отдельным блоком.

Scope: weekly `busy/free/unknown` partition, overlays `releasing/conflict`, effective factual/planned/PTO boundaries, dashboard chart, authorized GET|HEAD drill-down, fail-safe unavailable/UNKNOWN, bounded bulk reads и responsive/accessibility. Draft selection не занимает человека. No writes/backfill/schema migration/auto-allocation.

Не входят capture-job correction этапа 2, financials, assignment/PTO writers, calendar, checklist, inspection, общий shell, merge/deploy и закрытие #258. WIP №157 и другие worktrees не менять.

Root (`/root`) пишет scope/spec/tests. Owner authorization распространяется на автономную доставку текущего assignment в указанном scope; отдельный `gpt-5.6-sol/low` executor реализует, независимые `gpt-5.6-sol/low` reviewers решают planner-required Gates 3/5. Контракт: `specs/INSTALLER-UTILIZATION-FORECAST-001.md`; lifecycle: `openspec/changes/add-installer-utilization-forecast/`.

Локально только bounded focused checks; полный `make test`/`make verify` запрещён. Один exact-source CI после required reviews. Поручением владельца 2026-09-25 разрешены создание PR и merge после GREEN CI и обязательных approvals; deploy не разрешён.
