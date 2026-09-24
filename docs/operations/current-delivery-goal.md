# Текущая цель — №255, Yii-планирование инспекций

Поручение владельца 2026-09-24: реализовать issue #255 от verified `main` `b81b08d91ae5639f08413628b411587ae28176df` и довести до проверенного PR-ready без merge/deploy/изменения рабочего стенда. Зависимость #14 поставлена merged PR #260 с exact-source CI GREEN.

Результат: строка стройконтроля создаёт, переносит и отменяет один object-bound current plan через canonical `YiiInspectionPlanning`; calendar и queue согласованы; server-side `Europe/Moscow` today marker и стабильный priority применяются внутри scope/filters до COUNT/LIMIT; один доступный shlz-ui dialog сохраняет контекст при отказе и не выполняет hidden retry.

Не входят persistence/migrations, собственная модель назначения, #45, результаты/нарушения/уведомления, checklist/progress writers, assignment changes, ОТиЗ и invitations. Не менять shared `object-ui.js`, picker, `navigation.js`, `pilot.css`.

Lifecycle: `openspec/changes/inspection-planning-ui-255/`. Контракт: `specs/YII2-INSPECTION-PLANNING-UI-255.md`. Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers выполняют planner-required Gates 3/5. Только focused local checks; full local `make test`/`make verify` запрещён; обязателен один exact-source GitHub CI run.

Параллельная #258 выполняется в `codex/issue-258-installer-utilization-stage1`. Её workforce/directory/person-card/selection context и собственные artifacts/tests не входят в #255; общие registrations меняются только additively с сохранением чужих записей.

Исторические завершённые задачи из прежнего указателя не возобновляются.
