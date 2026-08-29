# FMonitor 2.0 rapid pilot

This directory contains the fast functional pilot and its locally scoped working instructions.

See `AGENTS.md` before making changes here. The repository's existing product documents and early implementation are the design and domain foundation, while work contained in this directory is intentionally developed through rapid browser-driven iterations without the repository-wide SSD + TDD ceremony.

Install PHP dependencies with `composer install --no-dev` before starting the pilot. New assignment orders are rendered as one PDF containing the order and its appendix; already prepared HTML artifacts remain downloadable as immutable history.

## Актуальная сборка

После штатного bootstrap локальной базы обновите read-only production-снимок карточек:

```bash
FMONITOR_PILOT_ACTIVE_MANIFEST="$HOME/.local/state/fmonitor2/pilot-demo/78d99d34/active.json" \
FMONITOR_SOURCE_USER='<read-only user>' \
FMONITOR_SOURCE_PASSWORD='<read-only password>' \
php rapid-pilot/import-production-object-details.php
```

Запуск собранной rapid-pilot оболочки:

```bash
php rapid-pilot/start.php
```

Карточка объекта дополняет действующий процессный интерфейс структурированным локальным снимком production-представления «Адресный перечень». Production остаётся read-only; снимок хранится только в активной локальной pilot-генерации.
