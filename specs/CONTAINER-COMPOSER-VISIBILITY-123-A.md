# CONTAINER-COMPOSER-VISIBILITY-123-A — exact candidate и Composer dependencies в verification container

## Простыми словами

Canonical `run-in-profile` замораживает candidate существующим harness snapshot mechanism, materialize'ит его внутрь read-only verification container и компонует с matching locked dependencies. Host checkout не предоставляет runtime source mount и не получает dependency directories. Slice не меняет classification, общий worktree guard, production runtime или application behavior.

## Нормативный контракт

- Идентификатор: `CONTAINER-COMPOSER-VISIBILITY-123-A`.
- Actor: разработчик или existing CI consumer.
- Source oracle: owner decisions по #123-A от 2026-09-16 и T08 gap-check на `main` `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf`.
- Public seam: `tools/delivery/run-in-profile <profile> <command> [args...]` с existing harness frozen candidate snapshot как source input.
- Preconditions: Docker daemon доступен; candidate содержит canonical dependency inputs; host dependency state не является prerequisite.

### CCV123A-01 — existing frozen candidate является единственным project source

Launcher MUST использовать existing `review-source.py capture/restore` representation и `source_details().executable_digest`, не новый snapshot/identity format. Materialized application source MUST byte-for-byte сохранять применимые tracked/untracked additions, modifications, deletions и executable modes frozen candidate. Image identity и compact result MUST однозначно ссылаться на executed source identity.

Host изменения после freeze MUST NOT влиять на execution. Main, stale image source, соседний checkout или более поздний dirty host state MUST NOT подменять frozen source.

### CCV123A-02 — immutable source/dependency composition

Project files MUST загружаться только из container-owned materialized source. Third-party Composer files MUST загружаться только из immutable container-managed dependency layer, соответствующего candidate `composer.json`, `composer.lock`, runtime pins и recipe. Existing Yii repository-relative paths MUST работать без переписывания entrypoints.

Candidate source и dependency tree MUST быть read-only во время command. Writable data допускается только в явно предоставленном disposable temp/artifact/runtime location; test MUST NOT изменять canonical candidate source.

### CCV123A-03 — host cleanliness и отсутствие fallback

Cold-ish и warm execution MUST NOT создавать или использовать на host `vendor/`, `node_modules`, `.venv`, dependency mountpoints, symlinks или dependency copies. Stale/foreign host dependencies MUST игнорироваться. Missing/corrupt container dependency MUST fail closed до behavior без fallback на host Composer/vendor, соседний checkout или network install во время test execution.

### CCV123A-04 — lock/source invalidation и isolation

Image/dependency identity MUST включать frozen executable source, recipe/runtime pins и `composer.json`/`composer.lock`. Изменение lock/runtime input MUST rebuild/reject stale dependency composition. Warm invocation MAY reuse immutable Docker cache, но MUST materialize текущий frozen source. Два worktrees/candidates MUST исполняться последовательно или параллельно без source/dependency/runtime contamination.

### CCV123A-05 — existing profiles и evidence compatibility

Governance, integration, browser и representative Yii focused execution MUST сохранять применимые argv, exit, network/service ownership и compact evidence semantics. Если checkout-local Git metadata больше не входит в container source, непосредственно связанный verification test MAY читать explicit executed Git/source identity из environment/evidence. Production Docker runtime, deployment Compose и CI topology MUST оставаться неизменными.

### CCV123A-06 — неизменность host candidate и scope

Preparation/execution MUST оставлять host tracked source/locks и dependency-directory inventory неизменными. Slice MUST NOT менять `INTENDED_RED` classification, добавлять общий worktree guard, новый environment manager/runner/snapshot format, обновлять dependencies/PHP/Yii, оптимизировать performance либо менять product/domain authorization, audit/history или concurrency semantics.

## Обязательная executable matrix A–O

- **A:** clean worktree без host vendor → Yii bootstrap `YII_BOOTSTRAP_OK`.
- **B:** после cold/warm execution host vendor отсутствует даже как пустой directory; также отсутствуют `node_modules`, `.venv` и иные dependency paths.
- **C:** candidate-only project class/fixture → container наблюдает candidate bytes и source origin.
- **D:** host source изменён после existing snapshot freeze → execution сохраняет frozen bytes/identity.
- **E:** candidate deletion и применимый executable mode → deletion/mode представлены materialized source.
- **F:** stale foreign host vendor fixture → игнорируется и не изменяется.
- **G:** missing/corrupt container dependency → raw nonzero fail closed, без host/network fallback.
- **H:** changed `composer.lock`/runtime dependency input → stale composition rejected/rebuilt.
- **I:** два distinct worktrees/candidates → каждый видит только свой marker; допускается concurrent proof.
- **J:** writable artifact пишется только в allowed disposable location; запись project source отклоняется.
- **K:** representative governance profile GREEN.
- **L:** representative integration profile GREEN.
- **M:** representative browser profile GREEN.
- **N:** existing exact-source identity regression GREEN; image/evidence identity совпадает с frozen executable candidate.
- **O:** итоговый host dependency inventory после всех cases равен исходному.

Для A/K–M фиксируются cold-ish/warm duration из `RUN_IN_PROFILE_RESULT`, host dependency existence, dependency origin и source origin. Setup failures before Yii behavior должны измениться с исторических `3/3` до `0/N`. CI/token improvement без telemetry не заявляется.

## Done

- OpenSpec strict validation и planner obligations разрешены.
- Изменённый test имеет Gate 3 delta `APPROVED` и демонстрирует intended RED source-layout/host-cleanliness gap.
- Separate executor реализовал только bounded verification source/dependency composition; A–O и planner focused commands GREEN без local full suite.
- Independent Gate 5 `APPROVED` относится к exact final source; один existing-consumer exact-source CI GREEN.
- Branch/PR PR-ready; merge/deploy/settings не выполнялись.
