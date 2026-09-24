## Why

Открытый backlog нельзя надёжно читать как очередь: существующие `bug`/`enhancement` не различают продукт, технический долг, delivery harness и сводные реестры, а готовность и фактические ограничения запуска не выражены согласованно. Issue #256 отдельно поручает владельцу получить фактически размеченный актуальный backlog и компактные правила сопровождения, не превращая labels в полномочие на автономную работу.

## What Changes

- Ввести и идемпотентно создать/обновить десять согласованных GitHub labels: четыре взаимоисключающих `type:*`, три `prep:*` и три независимых `status:*`, с русскими описаниями и согласованными цветами внутри групп.
- Снять paginated snapshot всех repository labels и открытых issues без pull requests, проверить актуальные descriptions, существенные comments, связанные PR и ограничения, затем подготовить и применить точечный классификационный план.
- Обеспечить ровно один `type:*` для каждой проверенной открытой issue; ровно один `prep:*` для исполняемых issues и ни одного `prep:*` для `type:tracking`; назначать `status:*` только по подтверждённому текущему основанию.
- Для `prep:needs-work` и `status:blocked` добавить короткое конкретное пояснение, не дублируя уже существующее эквивалентное пояснение при повторном запуске.
- Добавить `docs/issue-labels.md` со схемой, readiness-критерием, правилами сопровождения и готовыми GitHub filters; добавить ссылку из `AGENTS.md` без изменения delivery lifecycle или полномочий.
- Повторно прочитать GitHub после записи и сформировать компактный отчёт об охвате, времени, распределении, пробелах, новых/изменившихся issues и фактически выполненных записях.
- Сохранить все сторонние и `quality-graph:*` labels; не менять закрытые issues, pull requests, priorities, workflows, Quality Graph, CI selection, gates, admission или queue policy.

## Capabilities

### New Capabilities

- `operations/backlog-issue-classification`: Наблюдаемая GitHub-схема классификации открытых issues, безопасная точечная миграция backlog и репозиторные правила дальнейшего сопровождения.

### Modified Capabilities

Нет.

## Impact

- Внешний public seam: GitHub Issues/Labels API через штатный `gh`; state-changing операции ограничены десятью согласованными labels и необходимыми поясняющими comments.
- Репозиторий: новый `docs/issue-labels.md`, ссылка в `AGENTS.md`, OpenSpec/executable verification artifacts и delivery report; product runtime и persistence не затрагиваются.
- Source oracle: issue #256, актуальные issue #169 и #95, свежий `origin/main`, полный paginated GitHub state и фактические consumers labels.
- Release value: владелец видит тип, проработанность и подтверждённые ограничения каждой открытой задачи, но по-прежнему отдельно выдаёт поручение на исполнение.
- Non-goals: `agent-ready`, автозапуск, supervisor #95, harness/Quality Graph policy, workflow автолейблинга, новый registry/state machine/Gate, закрытие или переписывание issues, labels на PR, merge и deployment.
