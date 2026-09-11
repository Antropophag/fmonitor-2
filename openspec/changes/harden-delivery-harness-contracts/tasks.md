## 1. Контракт и Gate 1

- [x] 1.1 Создать нормативную executable specification `DELIVERY-HARNESS-HARDENING-001` с public seams, полной outcome/exit таблицей, worktree и registry invariants; проверить traceability к issue #90 и отсутствие продуктовых domain-изменений.
- [x] 1.2 Создать verification input для change с полным Gate 3 checklist observable dimensions и проверить его публичной командой `change-verification.py check`.

## 2. RED и независимый Gate 3

- [x] 2.1 Добавить table-driven subprocess test публичного `harness.py run`, включая stdout/stderr, shell exit, retained record, raw/normalized child codes, timeout/signal, domain `UNKNOWN` и filesystem isolation; через harness зафиксировать демонстрируемый RED на отсутствующем/сломном контракте.
- [x] 2.2 Добавить bounded regression test PR #91 для product roster и отдельный assertion, что agent-harness tests не входят в product suites/categories; доказать RED до запуска product matrix.
- [x] 2.3 Добавить RED tests публичных цепочек prepare/check/refresh/run, hook/binding/context/plan, двух worktrees, evidence/package и runner/wrapper/aggregate; каждый test должен использовать реальные returned artifacts и иметь bounded cleanup.
- [x] 2.4 Получить независимый Gate 3 rereview обновлённой границы product/agent verification, tests и RED evidence; устранить findings и отметить задачу только при `APPROVED` record в `reviews/tests/`.

## 3. Минимальная реализация Gate 4

- [x] 3.1 Исправить runner outcome/CLI-exit и control-marker semantics минимально до GREEN table-driven public contract; проверить focused test через `harness.py run`.
- [x] 3.2 Исправить plan/package interoperability и worktree-scoped binding/state без global overwrite; проверить обе end-to-end цепочки и rejection произвольного external plan.
- [x] 3.3 Сделать product roster канонически derived либо детерминированно согласованным, удалить agent-harness tests из product suites/categories и добавить отдельный bounded harness entry point; проверить PR #91 RED/GREEN и отсутствие product execution.
- [x] 3.4 Встроить observable-dimensions validation в существующий verification input/package flow без нового Gate и проверить incomplete/complete cases.
- [x] 3.5 Адаптировать fault-injection/mutation sensitivity к отдельному agent contour и доказать RED каждой из семи мутаций при GREEN baseline без product suite execution.

## 4. Интеграция и Done

- [x] 4.1 Запустить через delivery harness только bounded agent-cycle tests и roster consistency check; сохранить exact-source records и доказать отсутствие product DB/PDF/runtime/E2E команд.
- [x] 4.2 Получить независимый Gate 5 rereview обновлённых spec, approved tests, implementation diff и bounded evidence; устранить findings и сохранить `APPROVED` record в `reviews/code/`.
- [ ] 4.3 Выполнить отдельную bounded exact-source CI/fast проверку harness без product full matrix; закрыть Done только когда 14/14 задач, OpenSpec strict validation и этот check GREEN.
