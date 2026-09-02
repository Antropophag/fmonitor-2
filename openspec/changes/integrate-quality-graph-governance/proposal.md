## Why

Текущий SSD/TDD harness уже имеет repository-owned команды, исполнимые спецификации, RED evidence и независимые review gates, но CI не может fail-closed доказать их полноту, взаимную принадлежность и привязку к точному commit. Изменение вводит Quality Graph как тонкий governance-слой над существующими проверками и добавляет недостающий machine-verifiable lineage без переноса тестовой логики из репозитория.

## What Changes

- Добавить минимальный Quality Graph, узлы которого вызывают существующие repository-owned команды, включая `make architecture-check` и `make verify`, и отклоняют отсутствующие, устаревшие или неполные результаты.
- Ввести repository-owned manifest/receipt и fail-closed checker для цепочки `change → executable spec hash → intended RED → independent test approval → GREEN → independent code approval → reviewed commit`.
- Сохранить существующие форматы evidence и независимые review gates источником истины; manifest индексирует и хеширует их, но не дублирует выводы тестов или review.
- Зафиксировать exact-version Quality Graph toolchain и trusted publishing provenance: PR, head SHA, workflow run/attempt и graph digest.
- Добавить dual-run migration: старый работающий CI/harness остаётся обязательным до документированной parity на representative PR; никакое переключение required checks не выполняется автоматически этим slice.
- Разделить bootstrap proof: runner/governance parity проверяется на незамерженном representative PR, а publisher/dashboard parity новых nodes остаётся отдельным обязательным доказательством после появления доверенной topology в base branch. До обоих доказательств миграция fail-closed считается незавершённой.
- Не менять доменную логику, публичные application seams, append-only историю продукта и поведение pilot.

## Capabilities

### New Capabilities

- `delivery/quality-graph-governance`: fail-closed CI/governance graph, machine-verifiable SSD/TDD provenance, dual-run parity и безопасные условия переключения.

### Modified Capabilities

Нет.

## Impact

- Delivery surface: Quality Graph declaration/generated graph, CI workflows, repository-owned governance checker, provenance manifest/receipts и документация операций.
- Existing commands/evidence: `Makefile`, `tools/verification/`, `tools/architecture/`, `specs/`, `reviews/tests/`, `reviews/code/`, `docs/operations/` переиспользуются и не заменяются.
- Dependency: exact pinned pre-release Quality Graph release/action set; его обновление требует отдельной проверяемой версии и parity evidence.
- Актор slice: разработчик/ревьюер PR и CI publisher. Source oracle: `docs/development-process.md`, существующие repository-owned команды и review/evidence records. Target public seam: одна repository-owned команда проверки delivery graph, вызываемая локально и CI.
- Release value: merge eligibility становится машинно доказуемой и fail-closed без ослабления независимых Gate 3/Gate 5.
- Non-goals: merge PR, изменение branch protection, удаление старого CI, перенос тестов в Quality Graph, автоматическое одобрение review, изменение продуктового поведения.
