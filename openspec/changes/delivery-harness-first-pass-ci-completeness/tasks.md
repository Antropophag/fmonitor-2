## 1. Scope, executable contract и Gate 1

- [x] 1.1 Создать нормативную specification `DELIVERY-HARNESS-CI-COMPLETENESS-001` с actor, public seams, rejected/UNKNOWN cases, source/evidence audit и examples PR #98/#100; проверить traceability к issue #99 и отсутствие product/Yii2 changes.
- [x] 1.2 Создать `verification-input.json`, сопоставить каждую acceptance с bounded command и observable dimensions, затем выполнить `harness.py prepare --role root` от exact `origin/main`; Gate 2 не начинать, пока generated plan не замкнут и binding не подтверждён `harness.py state`.

## 2. Intended RED и независимый Gate 3

- [x] 2.1 Добавить deterministic fixture/test для generated Dockerfile drift и stale E2E inventory composition PR #98; через публичный harness runner сохранить intended RED, доказывающий, что текущий planner пропускает obligations.
- [x] 2.2 Добавить dependency/environment fixtures для undeclared Python import PR #98 и MariaDB-dependent test в unit category PR #100; сохранить intended RED в CI-подобных bounded profiles без использования developer services.
- [x] 2.3 Добавить RED contracts для typed reviewer evidence, post-implementation Gate 3 delta lineage, executable-vs-lifecycle digest и declared dependency workspace; проверить каждую acceptance публичным package/preflight seam.
- [x] 2.4 Подготовить Gate 3 reviewer package через harness и получить независимый `APPROVED` review в `reviews/tests/`; findings возвращают работу к spec/tests и требуют нового package.

## 3. Verification graph и environment contract

- [x] 3.1 Реализовать machine-readable generated-source/consumer obligations в `tools/verification/` и проверить, что Dockerfile-only и inventory fixtures планируют точные generator/consumer commands, а unknown surfaces дают `UNKNOWN`.
- [x] 3.2 Реализовать runtime-prerequisite/category profiles и dependency-policy validation для Python/PHP/Node tests; проверить undeclared import и DB-in-unit RED→GREEN без product full suite.
- [x] 3.3 Расширить runner evidence фактическими services/dependencies и доказать, что GREEN из более богатой среды не подтверждает более узкий CI profile.

## 4. Package/source contracts и preflight

- [x] 4.1 Расширить plan/evidence schema типами `acceptance`/`boundary`/`category`; проверить принятие всех plan-owned records и fail-closed rejection unrelated evidence.
- [x] 4.2 Реализовать Gate 3 test-delta lineage с current GREEN, historical intended RED и exact test delta; проверить compatible и mismatched historical records.
- [x] 4.3 Разделить candidate source и executable digest с узким lifecycle-metadata allowlist; mutation tests должны сохранять digest только для verdict/task metadata и менять его для tests/config/catalogs.
- [x] 4.4 Реализовать manifest/realpath contract dependency workspace; проверить разрешённый ignored `vendor`, missing identity и symlink escape без добавления dependencies в deliverable.
- [x] 4.5 Добавить bounded exact-commit CI-parity preflight/publication admission и проверить, что совокупный bad candidate PR #98 и mismatch PR #100 блокируются до push, а исправленный candidate выдаёт publication-ready при PR/CI=`UNKNOWN`.

## 5. Focused verification и Done

- [x] 5.1 Выполнить generated bounded harness/verification plan и fast checks, подтвердить отсутствие локального `make test`/`make verify` и отсутствие product DB/PDF/E2E исполнения вне явно моделируемых synthetic environments.
- [x] 5.2 Выполнить `openspec validate delivery-harness-first-pass-ci-completeness --strict` и проверить, что все tasks/acceptances и exact executable source отражены в package.
- [x] 5.3 Получить независимый Gate 5 `APPROVED` на complete candidate и bounded evidence; изменения tests после review возвращают Gate 2/3.
- [ ] 5.4 Только после Gate 5 подготовить publication package, открыть PR и выполнить один exact-head GitHub Quality Graph; Done требует GREEN/VERIFY_OK, сохранённых исторических failures и отсутствия merge/deployment без отдельной авторизации.
