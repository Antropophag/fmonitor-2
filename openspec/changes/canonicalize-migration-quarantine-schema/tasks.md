## 1. Planning и Gate 1

- [ ] 1.1 После landing predecessors обновить точный runner order, literal migration version, composed 25-byte success / 26-byte pre-DB-access rejection и family-local 27/28 evidence; проверить `openspec validate canonicalize-migration-quarantine-schema --strict`
- [ ] 1.2 Подготовить executable spec с exact three-table manifest, 8-state matrix, отдельными conflict cases, populated counters/decoys, repeat и cleanup; получить явное owner approval Gate 1

## 2. RED и независимый test review

- [ ] 2.1 Реализовать deterministic verifier, доказать RED именно на отсутствии canonical ownership/runtime-DDL запрета и сохранить RED evidence с точной командой и transcript
- [ ] 2.2 Передать тест свежему независимому test-review агенту, сохранить verdict в `reviews/tests/` и исправлять замечания с новым reviewer до approval

## 3. Minimal GREEN

- [ ] 3.1 Добавить один additive family migration с read-all preflight, semantic fingerprint и create-missing для всех трёх таблиц; прогнать approved verifier
- [ ] 3.2 Зарегистрировать migration в production runner и применить composed 25/26 pre-DB-access prefix boundary; сохранить direct-family 27/28 coverage и проверить clean, repeat, 8 partial states и отдельные zero-mutation conflicts
- [ ] 3.3 Удалить DDL из registry/decision runtime owners и добавить общий fail-closed schema precondition; проверить registration/read/decision paths с запрещённым DDL

## 4. Regression и architecture

- [ ] 4.1 Прогнать focused migration-quarantine/OTIZ verifiers и подтвердить сохранение literal PILOT_ONLY behavior без новой domain/financial semantics
- [ ] 4.2 Прогнать `git diff --check`, `make architecture-check`, strict OpenSpec validation и `make verify`; устранить regressions без расширения slice

## 5. Независимый code review и Done

- [ ] 5.1 Передать GREEN diff свежему независимому code-review агенту, сохранить verdict в `reviews/code/` и исправлять замечания с новым reviewer до approval
- [ ] 5.2 Обновить operations status и отметить Done только когда approved spec, RED evidence, test review, minimal GREEN, regression/architecture/full verification и code review присутствуют в worktree
