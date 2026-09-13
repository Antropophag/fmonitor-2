## 1. Gate 1–3: contract и RED

- [x] 1.1 Подтвердить этот scope владельцем и проверить `openspec validate pinned-focused-check-profiles --strict`; это единственная binding-точка issue #110 перед implementation.
- [x] 1.2 Пересчитать минимальный verification plan после пересмотра owner contract; если guard требует запрещённые поля или расширение scope, STOP.
- [x] 1.3 Обновить Gate 2 test: immutable base inputs и runtime/dependency observations без равенства independent image IDs; сохранить RED пересмотренного контракта.
- [x] 1.4 Передать revised spec, test delta и RED независимому Gate 3 reviewer; correction разрешена только при `APPROVED`.

## 2. PR A — Container execution

- [x] 2.1 Executor корректирует один container recipe для полного revised runtime/dependency contract, сохраняя immutable base-image digests.
- [x] 2.2 Сохранить минимальный launcher без registry publishing, layer normalization, command registry или provenance framework.
- [x] 2.3 Выполнить parity contract test через уже выбранный corpus, не меняя Quality Graph/workflow/category semantics.
- [x] 2.4 Выполнить полный focused plan, `git diff --check`, strict OpenSpec и LOC guard; при >500 infrastructure LOC STOP.
- [ ] 2.5 Передать exact source, spec, approved tests и evidence независимому Gate 5 reviewer; PR A создавать только после `APPROVED` и подтверждения владельца.

## 3. Merge checkpoint

- [ ] 3.1 Дождаться merge и подтверждённого GREEN PR A; до этого не начинать PR B и не удалять `setup-runtime`.

## 4. PR B — Quality Graph adoption

- [ ] 4.1 От нового `main` заменить только существующие `unit`, `integration`, `e2e`, `governance` run commands на соответствующие profiles и обновить generated Quality Graph manifest/digest.
- [ ] 4.2 Доказать существующими selection/aggregation tests, что planner, category inventory, sharding и fail-closed aggregation semantics не изменились.
- [ ] 4.3 Выполнить focused verification и независимый Gate 5 review exact source; сохранить `setup-runtime` до подтверждённого GREEN всех четырёх jobs.
