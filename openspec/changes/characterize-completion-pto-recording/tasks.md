## 1. Gate 1 — executable specification

- [x] 1.1 Создать `specs/CHARACTERIZE-COMPLETION-PTO-RECORDING-001.md` с actor, HTTP seam, точными fixture values, ответами, facts, rejection ordering, authorization observations, replay/concurrency и live-clock normalization; проверить traceability к delta spec и evidence note
- [ ] 1.2 Получить явное одобрение владельца для `PILOT_ONLY` executable spec и зафиксировать approval без утверждения target 85/15 или authorization semantics

## 2. Minimal RED / GREEN

- [ ] 2.1 После Gate 1 написать минимальный HTTP test первого успешного `record_pto`, запустить его до harness implementation и сохранить RED assertion отдельно от environment preflight failure
- [ ] 2.2 Назначить свежему независимому test reviewer проверку spec, public seam, independently derived fixtures, clock bounds и RED evidence; сохранить `APPROVED` review в `reviews/tests/CHARACTERIZE-COMPLETION-PTO-RECORDING-001.md`
- [ ] 2.3 Только после test approval реализовать минимальный изолированный HTTP characterization harness и подтвердить GREEN успешной записи без изменений production pilot behavior

## 3. Расширенная characterization и integration

- [ ] 3.1 Добавить RED-сценарии progress 84, invalid/future date, CSRF, deactivated-session login redirect, out-of-scope actor, non-working case, missing case, rejection ordering, exact/changed replay, multi-worker same-case concurrency и request DDL-before-missing-case; сохранить intended failures
- [ ] 3.2 Назначить новому независимому test reviewer расширенную матрицу и RED evidence; получить отдельный `APPROVED` verdict до GREEN
- [ ] 3.3 Минимально расширить только verifier/harness до GREEN всей принятой матрицы и подтвердить unique-prefix cleanup и bounded timeouts
- [ ] 3.4 Зарегистрировать verifier в canonical characterization command и проверить, что clean invocation отличает setup, assertion и regression outcomes
- [ ] 3.5 Выполнить focused verifier, characterization regression, `make architecture-check` и `git diff --check`; сохранить команды и результаты для code review

## 4. Target contrast и Done

- [ ] 4.1 Обновить inventory/backlog/status: пометить current PTO oracle как охваченный, сохранить `COMPLETION-PTO-001`, declaration и canonical schema в `NEEDS_GRILL`, проверить отсутствие принятой target semantics
- [ ] 4.2 Назначить отдельному свежему независимому code reviewer spec, approved tests, diff и verification evidence; сохранить `APPROVED` verdict в `reviews/code/CHARACTERIZE-COMPLETION-PTO-RECORDING-001.md`
- [ ] 4.3 Выполнить `make verify` либо задокументировать только уже известные unrelated baseline failures, подтвердить все 13 tasks, architecture guardrails и clear Done definition, затем синхронизировать/архивировать OpenSpec change по lifecycle
