## 1. Gate 1–3: contract и RED

- [x] 1.1 Подтвердить этот scope владельцем и проверить `openspec validate pinned-focused-check-profiles --strict`; это единственная binding-точка issue #110 перед implementation.
- [x] 1.2 Создать минимальный `verification-input.json` только с issue/spec/base и focused commands, сгенерировать обязательный plan существующим `change-verification.py`; если guard требует запрещённые поля или расширение scope, STOP и вынести владельцу минимальное изменение guard.
- [x] 1.3 Добавить Gate 2 tests для трёх profiles, argv/exit propagation, pinned digest и local/CI parity; сохранить целевой RED.
- [x] 1.4 Передать spec, tests и RED независимому Gate 3 reviewer; implementation разрешена только при `APPROVED`.

## 2. PR A — Container execution

- [x] 2.1 Executor добавляет один container recipe с profiles `governance`, `integration`, `browser` и immutable image pins; проверить сборку каждого target и runtime/dependency probes.
- [x] 2.2 Executor добавляет минимальный `run-in-profile <profile> <command>` без command registry/selection/provenance policy; проверить точную передачу argv и exit code.
- [x] 2.3 Расширить уже выбранный Quality Graph container setup contract test для parity smoke, не меняя workflow, graph manifests, category commands, planner или aggregation; проверить одинаковые profile/image digest/runtime versions локально и в CI.
- [x] 2.4 Выполнить focused regression, `git diff --check`, OpenSpec strict validation и LOC guard; при >500 production/infrastructure LOC STOP.
- [ ] 2.5 Передать exact source, spec, approved tests и evidence независимому Gate 5 reviewer; PR A создавать только после `APPROVED` и подтверждения владельца.

## 3. Merge checkpoint

- [ ] 3.1 Дождаться merge и подтверждённого GREEN PR A; до этого не начинать PR B и не удалять `setup-runtime`.

## 4. PR B — Quality Graph adoption

- [ ] 4.1 От нового `main` заменить только существующие `unit`, `integration`, `e2e`, `governance` run commands на соответствующие profiles и обновить generated Quality Graph manifest/digest.
- [ ] 4.2 Доказать существующими selection/aggregation tests, что planner, category inventory, sharding и fail-closed aggregation semantics не изменились.
- [ ] 4.3 Выполнить focused verification и независимый Gate 5 review exact source; сохранить `setup-runtime` до подтверждённого GREEN всех четырёх jobs.
