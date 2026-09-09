## Context

Root подготовил нормативный spec и тесты. Исполнитель и reviewer — разные
sol/low агенты. Автономность дана текущим поручением владельца 2026-09-10;
демонстрация сохраняет обычное распределение авторства для проверки #82.

## Goals / Non-Goals

Воспроизводимый исходный код для независимого review без микрокоммитов.
Нормативная цельная матрица: [REVIEW-SOURCE-001](../../../specs/REVIEW-SOURCE-001.md).
Снимки не являются переносимыми архивами и не обрабатывают недоверенный вход.

## Decisions

Delivery tooling владеет CLI; стандартные Python/Git уже входят в CI runtime.
Снимок хранится вне repo, immutable по договору review, SHA-256 binds patch.
Временный Git index позволяет включить untracked без изменения настоящего index.
Restore использует отдельный detached worktree. Сбой очищает только своё создание.
Один полный Gate3 кандидат и один Gate5 кандидат; review findings группируются.

## Dependency impact before Gate 2

- Schema frontier, включая rapid-pilot/verify-* и таблицы fixtures: неприменимо,
  инструмент не выполняет SQL и не меняет миграции/ожидаемую версию схемы.
- Fixtures: новые временные Git-репозитории; нет Docker/БД/общих пользовательских данных.
- Runtime dependencies: Python stdlib + Git; уже доступны в local/CI; runtime image
  приложения не меняется, нового пакета нет.
- Deployment/readiness/backup/restore приложения: неприменимо; восстанавливается
  только source worktree, не БД/файлы приложения. Production пути не используются.
- Verification inventory: добавить один CLI test в suites.tsv и categories.json,
  явно учесть его как добавление к историческому baseline в inventory test;
  governance focused planner test плюс test нового seam, один authoritative full CI.
- Architecture: новый tools/delivery CLI; нет новой доменной границы.

## Risks / Trade-offs

Локальный snapshot требует доступности исходного Git repo/base. Concurrent edits
во время capture исключаются организацией review: автор замораживает кандидата.
Ignored secrets исключены; reviewer хранит snapshot вне Git и не публикует его.
