# Issue #172 — delivery record

## Scope и авторство

- Initial base: `0504d2589835f2583dc9afdbc47e4694e2573365`; candidate state-preservingly rebased to current `origin/main` `bced877aec8a8802e97037749ca4251d3098df1a` with merged #140, #228 and #234–#237.
- Root authored OpenSpec artifacts, `specs/FEEDBACK-001.md`, verification input и tests.
- Production executor: separate `gpt-5.6-sol / low` agent `/root/issue172_executor`.
- Independent Gate 3 reviewer: `gpt-5.6-sol / low` agent `/root/issue172_gate3`.
- Final reviewer будет отдельным агентом, не автором implementation.
- Открытый PR #235 касается readiness load; его scope не изменён. Работа согласованности status/list/dashboard не затронута.

## Prepared plan

- Planner lane: `CRITICAL`; required reviews: `gate3`, `final`.
- Canonical contract: `specs/FEEDBACK-001.md`, A1–A9.
- Local obligations: feedback owner/HTTP, connected browser, strict readiness,
  pilot jobs compose, change-verification governance, architecture guard.
- Full `make test` разрешён только exact-source GitHub CI consumer.

## Gate 2 и Gate 3

- Initial owner RED: missing `FeedbackApplication::buildIdentityFile`.
- Initial browser RED: `/pilot/calendar` normalized to `/pilot/objects`.
- Strict readiness remained GREEN.
- Gate 3 had three correction rounds for distrust/dependency-oracle completeness;
  final preimplementation exact source `a1e0c3f837b17bc6036222bf29f9dd18add5206d52a37ae4250f37d706da03b8`
  was `APPROVED` with no findings. Full append-only history is in
  `reviews/tests/FEEDBACK-001.md`.
- Post-implementation test-mechanic delta (worktree-local immutable fixture reset,
  vendor-only dependency allowance under `open_basedir`, diagnostic message and
  actual minimal-fixture actor IDs) was independently `APPROVED`; expectations were
  not weakened. This verdict is not Gate 5.

## Implementation

- Existing `FeedbackApplication` owns fail-soft reading of the configured
  `FMONITOR_RUNTIME_BUILD_ID_FILE`; missing/untrusted identity becomes `unknown`.
- The feedback HTTP path does not invoke `RuntimeBuildIdentity::read()` or its
  `sourceIdentity()` fallback and does not use process/network/Docker/Git access.
- Existing route normalization adds only specified current user-facing GET paths;
  query/fragment remain discarded and snapshot IDs remain distinct from object IDs.
- Existing fingerprint, `MariaDbFeedback`, schema, replay and append-only facts are
  unchanged. Operator view labels persisted source path and build explicitly.
- No migration, external call, stand mutation, merge or deployment.

## Verification / publication

Planner-selected bounded local run GREEN:

- `php tests/Yii2/yii2_feedback_001_test.php`;
- `php tests/Yii2/yii2_feedback_browser_001_test.php`;
- `php tests/Runtime/runtime_readiness_load_001_test.php`;
- `python3 tests/Deployment/pilot_jobs_compose_001_test.py`;
- `python3 tests/Verification/change_verification_001_test.py`;
- `python3 tests/Verification/architecture_guard_001_test.py`.

`git diff --check`, PHP/Node syntax and strict OpenSpec validation are GREEN.
Локальный full `make test`/`make verify` не запускался. Pending: exact-source final
review, commit/PR and one GitHub CI run. Эти состояния остаются `UNKNOWN` до
собственной evidence; UNKNOWN не является GREEN.

После state-preserving rebase на `origin/main` `bced877a` planner сохранил lane
`CRITICAL`, reviews `gate3`/`final` и те же шесть bounded local obligations. Все
шесть повторно GREEN на rebased source; merges #235–#237 не потребовали изменения
feedback behavior и остались вне production diff.

Независимый Gate 5 сначала вернул HIGH из-за pathname TOCTOU/relative path. Root
добавил independently reviewed RED для реального relative file, stable handle,
pathname rebind и post-read handle mutation; executor перевёл reader на один open
handle с before/after `fstat`, final `lstat` binding и guaranteed close. После
correction и rebase final reviewer дал `APPROVED`, findings none, для HEAD
`7b9cda428d8ef098ee1310bd31a22273b88bf75e`, exact source
`3179ff5d7098ebce56b23117f131367f0095cda8b700c669259a771dea16524f`.
Append-only история verdicts: `reviews/tests/FEEDBACK-001.md` и
`reviews/code/FEEDBACK-001.md`. PR/CI пока `UNKNOWN`.
