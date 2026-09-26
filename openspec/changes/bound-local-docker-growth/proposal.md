## Why

Локальная работа над FMonitor 2 способна за сутки накопить сотни гигабайт Docker-образов, BuildKit cache и анонимных volumes, оставляя на рабочем диске менее 20 ГиБ. Репозиторий должен ограничивать собственный рост, не удаляя данные локального стенда и не полагаясь на ручной глобальный `docker system prune`.

## What Changes

- Вводится единый project-owned seam для измерения Docker storage, проверки минимального свободного места и ограниченной сборки focused-профилей.
- Focused images получают стабильную dependency-addressed identity и project-owned GC label; source revision остаётся provenance, но не создаёт отдельную тяжёлую identity.
- Перед новой тяжёлой сборкой применяется fail-safe disk guard; после неё допускается только bounded cleanup неиспользуемых project-owned images и BuildKit cache по возрасту/бюджету.
- Disposable Compose lifecycle обязан завершаться явным teardown своих ephemeral resources даже после ошибки или прерывания.
- Добавляются детерминированные fake-Docker тесты, которые доказывают argv, порядок операций, пороги, отсутствие глобального prune и сохранность чужих/stand volumes.
- Не изменяются product behavior, production deployment, CI admission, пользовательские данные локального стенда и Docker Desktop settings на машине владельца.

## Capabilities

### New Capabilities

- `delivery/local-docker-storage-budget`: Контракт безопасного ограничения Docker storage, принадлежащего локальным focused/disposable workflow FMonitor 2.

### Modified Capabilities

Нет.

## Impact

Затрагиваются `tools/delivery/run-in-profile`, новые bounded maintenance/measurement seams, focused verification callers, disposable Compose cleanup и их verification inventory. Внешние API и прикладная модель не меняются. Docker Desktop GC остаётся рекомендуемой host-level страховкой, но репозиторий не редактирует пользовательские настройки автоматически.
