## 1. Preconditions и Gate 1

- [ ] 1.1 Зафиксировать принятое owner decision: только Compose `make up` является TEST-USER release contour, standalone — synthetic harness, будущий рабочий deployment следует Compose-подходу; затем подтвердить landed DDL-free predecessors, approved reset scope и strict validation
- [ ] 1.2 Подготовить exact executable spec для ensure/prepare/readiness/finalize/validate/restart/recovery/reset с sentinel+owner/ready/active manifests, logical DB identity, real-process concurrency, crash boundaries, permissions, decoys и normalized transcript; получить explicit owner approval Gate 1

## 2. RED и независимый test review

- [ ] 2.1 Реализовать isolated verifier с уникальными DB/state namespaces и доказать RED на nonce rotation/destructive restart, mixed identity и unguarded worker до изменения runtime code
- [ ] 2.2 Передать verifier свежему независимому test-review агенту, сохранить verdict в `reviews/tests/` и исправлять замечания с новым reviewer до approval

## 3. Minimal GREEN

- [ ] 3.1 Добавить owning module `app/PilotEnvironment` с exact schema/manifest validation и проверить focused unit/DB cases
- [ ] 3.2 Реализовать serialized ensure state machine: owner candidate, gated prerequisites, readiness proof, fsync/active publication и explicit recovery со scoped cleanup; проверить real-process concurrency плюс каждый crash boundary
- [ ] 3.3 Сделать restart validation-only, перевести HTTP startup и import/apply tools на validated identity и доказать byte-for-byte state preservation
- [ ] 3.4 Перевести hourly workforce sync на validation до started row и transactional identity recheck; доказать zero writes при mismatch
- [ ] 3.5 Оставить reset отдельной ownership-checked operator seam и проверить, что production runner/HTTP никогда не создают и не удаляют generation metadata
- [ ] 3.6 Зафиксировать host/image/co-location topology matrix, добавить standalone contour discriminator там, где co-location поддерживается, исправить root/test-user runbooks и real-process тестом доказать cross-contour zero interaction

## 4. Regression и architecture

- [ ] 4.1 Прогнать focused startup/native-generation/workforce/import verifiers, Compose stop/start и approved explicit-reset scenario с cleanup только verifier-owned resources
- [ ] 4.2 Прогнать `git diff --check`, `make architecture-check`, strict OpenSpec validation и `make verify`; устранить regressions без fixture/domain expansion

## 5. Независимый code review и Done

- [ ] 5.1 Передать GREEN diff свежему независимому code-review агенту, сохранить verdict в `reviews/code/` и исправлять замечания с новым reviewer до approval
- [ ] 5.2 Обновить operations/runbook и отметить Done только при наличии approved spec, RED evidence, test review, minimal GREEN, full regression/architecture checks и code review
