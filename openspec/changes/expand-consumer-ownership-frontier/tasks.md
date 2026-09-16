## 1. Contract, gap-check and RED

- [x] 1.1 Обновить current delivery pointer для bounded #153 Slice B и создать stable executable spec `CONSUMER-OWNERSHIP-FRONTIER-153-B`, проверив traceability к issue и явные non-goals #153C/D, T07a+/#107, fixture reachability и product changes
- [x] 1.2 Добавить root-authored canonical planner regression через public build/prepare seam: synthetic #20 schema/migration → direct migration → recovery/current-schema → indirect runtime/inventory, synthetic #148 current-assignment, missing/stale/ambiguous ownership, unregistered/removed verifier, indirect-with-direct, declaration order/cycle, presentation-only и Slice A preservation
- [x] 1.3 Зафиксировать измеримый BEFORE на unmodified Slice A planner и intended RED на новом contract, создав `verification-input.json`, подготовив planner-selected role package и сохранив точные команды/evidence вне checkout
- [x] 1.4 Получить независимый Gate 3 review полного spec/RED candidate; при `CHANGES_REQUESTED` исправить root-owned contract/tests и повторно подготовить exact review source

## 2. Minimal consumer frontier implementation

- [x] 2.1 Отдельному executor добавить минимальную capability ownership metadata в существующую verification policy и доказать schema validation для unique owners, declared targets и canonical registered verifier identities
- [x] 2.2 В существующем planner реализовать deterministic transitive traversal и `consumer_expansions` causal evidence, дедуплицируя execution без потери indirect selection
- [x] 2.3 Сохранить Slice A `semantic_escalations`/integration closure и прежний local presentation-only plan; выполнить только planner-selected focused checks и canonical regression, не запуская local full suite
- [x] 2.4 Зафиксировать AFTER для тех же synthetic #20/#148 inputs с точным списком механически добавленных direct/transitive consumers и сравнением с BEFORE

## 3. Independent review and PR-ready delivery

- [x] 3.1 Root проверить полноту candidate и подготовить exact source/reviewer package с actual authorship, focused GREEN и BEFORE/AFTER evidence
- [ ] 3.2 Получить независимый Gate 5 review полного exact source; все findings исправлять через separate executor и повторно review changed delta до `APPROVED`
- [ ] 3.3 Создать отдельный PR-ready commit/branch, запустить ровно один existing exact-source GitHub CI consumer, а при failure сначала собрать полный failed-job и `REGRESSION_FAILURE` inventory и затем довести тот же bounded Slice B до GREEN
- [ ] 3.4 Записать delivery result: exact source, reviews, CI, elapsed/rework, `#153 Slice B delivered`, `#153 remains open`, C/D untouched; merge не выполнять и после Slice B остановиться
