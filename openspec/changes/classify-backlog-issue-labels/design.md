## Context

См. `proposal.md` и delta spec. Изменение сочетает внешний mutable GitHub state с небольшим repository diff; основной риск — устаревший snapshot или массовая замена labels, стирающая параллельные изменения. Актуальный `origin/main` не содержит consumer новых `type:*`, `prep:*` или `status:*`; `quality-graph:*` остаётся отдельной служебной схемой. #95 использует предложенный `agent-ready`, который намеренно не создаётся и не назначается здесь.

## Goals / Non-Goals

**Goals:**

- один воспроизводимый план классификации на полном paginated наборе открытых issues;
- точечные, перечитывающие state GitHub mutations и проверяемый итог;
- минимальный repository contract для будущего ручного сопровождения;
- сохранение авторизации, истории comments и параллельных labels.

**Non-Goals:**

- постоянный sync service, bot, workflow или новый обязательный registry;
- автоматическое решение предметных неоднозначностей;
- изменение harness, Quality Graph, queue selection или GitHub settings;
- product runtime, database persistence и `rapid-pilot` adapter.

## Decisions

### 1. GitHub является persistence owner и public application seam

Все state changes выполняются через штатный GitHub Issues/Labels API (`gh api`/`gh label`) от авторизованного пользователя. В репозитории не создаётся зеркало состояния backlog. Компактные snapshot/plan/report являются evidence, но не вторым owner.

Альтернатива — YAML registry в repository — отклонена: он создаёт второй источник истины и требует синхронизации.

### 2. Анализ отделён от записи, но каждая запись имеет optimistic reread

Сначала снимаются paginated labels/issues/comments/relations и формируется compact plan `{number,type,prep,statuses,reasons,observedUpdatedAt}`. Перед mutation конкретной issue повторно читаются state, updatedAt и текущие labels. При изменении issue её строка переоценивается; mutation выполняется отдельными add/remove только для десяти labels схемы.

Альтернатива — один bulk replace полного массива labels из snapshot — отклонена, потому что может стереть параллельные служебные или пользовательские labels.

### 3. Одноразовая автоматика остаётся вне постоянного product/tooling surface

Для пагинации, cardinality и точечных API calls допустим небольшой disposable script/evidence вне tracked runtime. В repository остаются только OpenSpec, executable acceptance при apply, `docs/issue-labels.md`, ссылка и итоговый delivery report. Новый CLI или сервис синхронизации не добавляется.

Альтернатива — вручную выполнять сотни `gh` calls — повышает риск пропуска и невоспроизводимой проверки.

### 4. Цвет задаётся единообразно по группам

Если GitHub не содержит конфликтующей семантики, используются одинаковые цвета внутри каждой группы: `type:*` — синий `1d76db`, `prep:*` — фиолетовый `5319e7`, `status:*` — оранжевый `d93f0b`. Описания берутся из issue #256 в компактной русской форме. Это presentation metadata, не policy.

### 5. Неопределённость fail-safe классифицируется, а не угадывается

Неясный основной type блокирует mutation конкретной issue и попадает в отчёт, потому что type обязан быть ровно один. Неясная readiness получает `prep:triage`. Status label не ставится без положительного актуального основания. Конкретная неизвестная семантика, меняющая эти правила, была бы `NEEDS_GRILL`; на момент planning таких вопросов нет.

### 6. Проверка соразмерна operations/docs diff

Executable acceptance проверяет exact schema, cardinality/invariants на сохранённом итоговом JSON, сохранность unrelated labels, отсутствие PR/closed-issue mutations, idempotency plan и наличие repository rules/filters. Полный локальный `make test`/`make verify` запрещён. Verification planner выбирает lane/reviews; exact-source CI выполняется один раз в apply lifecycle.

### 7. Architecture ownership

Owning area — operations/governance документация и внешний GitHub issue metadata; application modules и persistence owners не затрагиваются. Разрешённые dependencies — `gh`, `git`, стандартные shell/JSON utilities и существующий delivery harness. `rapid-pilot` не используется. Ожидаемое влияние на architecture check — только проверка документационной ссылки и отсутствие запрещённых runtime/workflow изменений.

## Risks / Trade-offs

- [Часть backlog требует предметного чтения и может быть спорной] → использовать `prep:triage`, сохранять короткое основание в плане и не угадывать status.
- [GitHub меняется во время миграции] → reread каждой issue, точечные operations, итоговый paginated reread.
- [Comment spam при повторе] → нормализованная проверка существующих существенных пояснений и новый comment только при новом/изменившемся основании.
- [Внешние mutations нельзя откатить закрытием PR] → preflight plan, конфликтная остановка, точный audit report; rollback при ошибке выполняется отдельными обратными mutations по сохранённому before snapshot.
- [Цвета уже заняты иной семантикой целевого имени] → не менять молча; остановиться и показать конфликт владельцу.

## Migration Plan

1. В отдельном worktree от свежего `origin/main` материализовать task context, executable contract и verification plan.
2. Снять before snapshot, проверить consumers и построить полный classification plan без записей.
3. Провести требуемый Gate review плана/acceptance до внешних mutations.
4. Создать/обновить десять labels после conflict preflight.
5. Для каждой всё ещё открытой issue выполнить reread, переоценку и точечные label/comment mutations.
6. Выполнить after snapshot, invariant/idempotency checks, repository docs и отчёт.
7. Подготовить exact-source package, независимый final review и один CI run; остановиться до merge/deployment.

Rollback: использовать before snapshot для точечного восстановления только десяти labels схемы; comments остаются append-only и не удаляются, а ошибка поясняется новым correction comment. Rollback требует отдельного решения владельца, если затрагивает уже вручную изменённую после миграции issue.
