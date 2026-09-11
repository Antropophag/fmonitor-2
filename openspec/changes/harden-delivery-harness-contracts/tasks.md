## 1. Контракт и Gate 1

- [x] 1.1 Создать нормативную executable specification `DELIVERY-HARNESS-HARDENING-001` с public seams, полной outcome/exit таблицей, worktree и registry invariants; проверить traceability к issue #90 и отсутствие продуктовых domain-изменений.
- [x] 1.2 Создать verification input для change с полным Gate 3 checklist observable dimensions и проверить его публичной командой `change-verification.py check`.

## 2. RED и независимый Gate 3

- [x] 2.1 Добавить table-driven subprocess test публичного `harness.py run`, включая stdout/stderr, shell exit, retained record, raw/normalized child codes, timeout/signal, domain `UNKNOWN` и filesystem isolation; через harness зафиксировать демонстрируемый RED на отсутствующем/сломном контракте.
- [x] 2.2 Добавить bounded regression test PR #91, который регистрирует E2E suite при устаревшей dependent composition и доказывает локальный RED до full CI.
- [x] 2.3 Добавить RED tests публичных цепочек prepare/check/refresh/run, hook/binding/context/plan, двух worktrees, evidence/package и runner/wrapper/aggregate; каждый test должен использовать реальные returned artifacts и иметь bounded cleanup.
- [x] 2.4 Получить независимый Gate 3 review specification, tests и RED evidence; устранить findings и отметить задачу только при `APPROVED` record в `reviews/tests/`.

## 3. Минимальная реализация Gate 4

- [x] 3.1 Исправить runner outcome/CLI-exit и control-marker semantics минимально до GREEN table-driven public contract; проверить focused test через `harness.py run`.
- [x] 3.2 Исправить plan/package interoperability и worktree-scoped binding/state без global overwrite; проверить обе end-to-end цепочки и rejection произвольного external plan.
- [x] 3.3 Сделать verification roster канонически derived либо добавить deterministic fast consistency boundary, сохранив uniqueness/coverage/literal invariant guarantees; проверить RED/GREEN сценарий PR #91.
- [x] 3.4 Встроить observable-dimensions validation в существующий verification input/package flow без нового Gate и проверить incomplete/complete cases.
- [x] 3.5 Добавить ограниченный fault-injection/mutation sensitivity набор для семи заявленных orchestration faults и доказать RED каждой мутации при GREEN baseline.

## 4. Интеграция и Done

- [x] 4.1 Запустить через delivery harness все focused hardening tests и релевантные fast/governance/verification suites; сохранить exact-source records и отдельно перечислить фактические GREEN, INTENDED_RED и любые deferred checks.
- [ ] 4.2 Получить независимый Gate 5 review spec, approved tests, implementation diff и evidence; устранить findings и сохранить `APPROVED` record в `reviews/code/`.
- [ ] 4.3 Выполнить canonical `make test`/CI для точного publish candidate согласно verification matrix, подтвердить отсутствие ослабления полного CI и закрыть Done только когда 14/14 задач, OpenSpec strict validation и exact-source CI GREEN.
