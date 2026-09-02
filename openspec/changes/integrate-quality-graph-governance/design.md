## Context

См. `proposal.md` и `docs/quality-graph-primary-source-research-2026-09-02.md`. Репозиторий уже владеет агрегатором `make verify`, специализированными runner'ами, architecture policy, исполнимыми спецификациями и Markdown evidence/reviews. Их содержание богаче Result v0 Quality Graph, который умеет привязывать nodes к PR/head/run/attempt/graph digest, но не моделирует spec hashes, RED→review→GREEN lineage или независимость reviewers.

Quality Graph v0.1.7 является pre-release. Trusted publisher использует graph topology из base branch; следовательно, первый незамерженный PR с новыми nodes может доказать runner behavior, но не полную publisher/dashboard parity этих nodes.

## Goals / Non-Goals

**Goals:**

- Один repository-owned governance seam для локального и CI запуска.
- Thin graph nodes над существующими командами и evidence.
- Детерминированный fail-closed lineage contract и exact-run provenance.
- Проверяемая dual-run миграция без преждевременной замены старого CI.

**Non-Goals:**

- Новая тестовая система или перенос команд из `Makefile`/`tools`.
- Изменение product/domain persistence или `rapid-pilot`.
- Автоматическая оценка качества текста review.
- Merge, изменение branch protection либо удаление legacy workflow в этом change.

## Decisions

### 1. Quality Graph — оркестратор, repository checker — владелец governance semantics

Graph declaration содержит зависимости и команды nodes. Отдельная repository-owned команда проверяет receipts и referenced evidence; Quality Graph запускает её как blocking node. Это сохраняет локальную воспроизводимость и не связывает delivery constitution с ограниченной upstream Result v0 schema.

Альтернатива — кодировать hashes/reviewer rules в workflow YAML или graph declaration. Отклонена из-за дублирования логики и невозможности одинаково проверить её локально.

### 2. Lineage receipt индексирует существующие artifacts

На slice хранится immutable versioned machine-readable receipt chain со stable slice/spec identifiers, base commit, относительными путями и `A|M|D`/nullable-SHA полного `git diff --no-renames`, author/reviewer identities, verdicts и reviewed implementation commit. Spec/evidence/review Markdown содержат canonical `delivery-metadata` block и остаются authoritative records; checker выводит RED/GREEN/review commits только как unique first-introduction commits соответствующих evidence blobs, без self-commit полей в receipt. Удаления проверяются в parent commit; type changes отклоняются.

Checker запрещает path escape, дубли stable IDs, неизвестные schema versions, missing files, hash drift, любое расхождение с полным `git diff --name-status` (включая helpers и удаления), не-APPROVED verdict, одинаковые author/reviewer identities, нестрогую Git chronology и commit mismatch. Ни evidence/review, ни receipt не заявляют RED/GREEN self commits. Correction добавляет новый receipt с `supersedes`; старый не изменяется.

Альтернатива — парсить свободный Markdown эвристиками или использовать отдельные sidecars. Отклонена: canonical fenced JSON metadata внутри authoritative Markdown проверяется строго и не создаёт второй narrative artifact.

### 3. Минимальный graph переиспользует крупные стабильные seams

Начальный graph содержит declaration drift/validation, lineage governance и существующий full verification seam. `make verify` остаётся агрегатором unit/db/characterization/e2e/architecture/lint/diff stages; graph не размножает эти стадии, кроме случаев, где отдельный node нужен для provenance или зависимости publisher.

Owning module: delivery tooling под `tools/` и CI configuration. Allowed dependencies: standard shell/PHP tooling уже используемое repository и exact pinned Quality Graph packages/actions. Persistence owner: Git-tracked declarations, receipts и evidence; никакой runtime DB. `rapid-pilot` adapter: отсутствует. Architecture check должен запрещать обход repository seam и unpinned/несогласованный Quality Graph toolchain.

### 4. Exact pins образуют один проверенный release set

CLI/packages фиксируются на одной точной версии v0.1.7-compatible set, GitHub Action — на dereferenced commit SHA официального release. Обновление pins меняет graph digest и требует отдельного dependency/parity review. Stale snippets с 0.1.2 не используются.

### 5. Dual-run имеет два доказательства и не переключает checks

Actual representative GitHub PR должен включать как минимум: успешный полный run; намеренную порчу declaration/generated parity; missing/stale lineage; failing repository command; stale head/run provenance. Старый и новый механизмы запускаются на тех же head commits, результаты сохраняются в operations evidence с command/result matrix; Quality-Graph-only negative cases отмечаются added coverage, не ложной parity.

Bootstrap phase A доказывает local/CI runner и governance parity на unmerged PR. Phase B после наличия topology в доверенной base branch доказывает publisher/dashboard routing. Поскольку пользователь запретил merge, этот change может завершить реализацию и phase A, но MUST остаться незавершённым на migration cutover task до доступного pre-bootstrapped base или отдельного разрешённого bootstrap merge. Это не исключение из fail-closed правила.

### 6. Trusted boundary минимальна

PR workflow выполняет недоверенный код только с read permissions и публикует content-addressed result artifacts. Отдельный trusted publisher получает минимальные permissions, не checkout'ит и не исполняет PR code, валидирует provenance против event/base topology и публикует итог. Approvals feature выключена.

## Risks / Trade-offs

- [Pre-release upstream изменит schema/CLI] → exact pins, graph digest, dependency review и parity rerun.
- [Первый PR выглядит green без publisher parity] → отдельные migration states и fail-closed запрет cutover до phase B.
- [Receipt превращается во второй narrative record] → хранить только identifiers, hashes, identities, Git commit edges и links; narrative остаётся в существующих artifacts.
- [Author identity неоднозначна для agent reviews] → executable spec задаёт canonical identity и authored-artifact set; missing identity отклоняется.
- [Один `make verify` скрывает granular dashboard] → принять минимальный coarse node сначала; расширять только при доказанной operational ценности без дублирования commands.
- [Representative negative cases дороги] → использовать обратимые fixture commits/branches и synthetic receipts, не менять production/domain data.

## Migration Plan

1. Зафиксировать executable delivery spec и RED для отсутствующего graph/lineage checker; получить независимый Gate 3 review.
2. Добавить receipt schema/checker и repository-owned команду, затем minimal GREEN и regression.
3. Добавить pinned Quality Graph declaration/generated output и untrusted runner/trusted publisher workflows, оставив старый mechanism обязательным.
4. Получить независимый Gate 5 code review exact commit.
5. На representative PR выполнить phase A positive/negative dual-run matrix и сохранить immutable evidence.
6. Выполнить phase B publisher proof только когда base branch уже содержит trusted topology; до этого cutover task остаётся unchecked.
7. Изменение required checks или удаление старого механизма оформить отдельным явно одобренным change после полной parity. Final verification относится к reviewed implementation commit и допустимому evidence envelope, а не требует невозможного exact-HEAD self-review. Rollback нового слоя — перестать считать его кандидатом на cutover; старые commands/workflow продолжают работать.
