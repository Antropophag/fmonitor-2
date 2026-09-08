# Autonomous restart handoff — 2026-09-06 17:33 UTC checkpoint

Owner явно запросил restart и готовый стартовый prompt. Все reviews завершены,
verification processes terminal. После этого checkpoint новая работа в старой
сессии не начинается. Goal ACTIVE, не complete/blocked.

## Restore first

Repo `/Users/antropophag/code/fmonitor-2`, branch `codex/remove-pilot-work-navigation-v2`.
Сначала get_goal; только если отсутствует, восстановить EXACT objective БЕЗ budget:

> Довести портал FMonitor 2.0 от фактического состояния repository и remote до проверяемой готовности к запуску в тестовую эксплуатацию, соблюдая все delivery gates, append-only evidence, exact-SHA verification, CI, clean deployment/restart/golden-path и отсутствие launch blockers

Review-record checkpoint `b458087`; этот handoff — следующий docs-only commit.
Exact expected HEAD дан в closing message/start prompt. Проверить HEAD/clean status.
Полностью прочитать AGENTS.md, PRODUCT.md, CONTEXT.md, pilot spec/data model,
docs/development-process.md и этот handoff. Старую историю целиком не перечитывать.
Relevant skills openspec-apply-change; перед rapid-pilot читать local AGENTS.

## CURRENT OWNER SCOPE — overrides old handoffs

1. **Исторических данных и PDF нет. Чистый запуск.** Не делать backward-compatible
   legacy writer migration, mixed N−1/N rollout или historical preservation matrix
   ради запуска. Authority `docs/operations/fresh-launch-owner-scope-2026-09-06.md`.
   Старые1433Z/1240Z/0830Z migration prerequisites superseded в этой части.
2. **PDF-шаблоны не хранить.** По запросу выдаётся PDF с сегодняшней датой Moscow;
   повторное формирование создаёт PDF заново. Сохраняются дата последнего успешного
   формирования для original-date prefill и append-only audit, без files/versions/
   artifact storage. Authority `selection-template-no-storage-owner-approval-2026-09-06.md`.
   Earlier rerender/versioned-PDF approval superseded; не спрашивать снова.
3. Signed originals/corrections и новые domain facts после запуска immutable/
   append-only. Выбор состава не применяет его и не открывает работы.
4. Both **new_order и replace_pending** обязательны. Technical choices autonomous;
   новые product questions отдельно. Не возвращать manual registration gate.
5. Owner недоволен временем/стоимостью и невидимым прогрессом. Сначала кратко
   критический путь; каждому пакету result/evidence/time/token-delta cap, учитывать
   reviews/фиксацию, при overrun пересмотреть. Без completeness-only матриц,
   дополнительных абстракций/контрактов и speculative harness tuning.
   Перезапуск не заменяет контроль scope. Reuse exact existing approvals/evidence.

## What is completed

Original-command combined Gate5v2 APPROVED на360db9a2f863de117fb0ef2e2fd93c2b79832fa8
из inherited1433Z checkpoint. Не пересматривать. Полного portal VERIFY_OK ещё нет.

### Disabled selection schema — Gate5 APPROVED

Source `ced7b776a85409b0766a6a14aa9e4c37487d60db`.
Spec `ASSIGNMENT-ORDER-SELECTION-SCHEMA-001` v0.2 и normative JSON fixtures.
Review `reviews/code/ASSIGNMENT-ORDER-SELECTION-SCHEMA-001-engine-v1.md`.
Registry public completion false означает unproven prerequisite/conflict, включая
скрытые bool API errors; собственные native selection SQL errors unavailable.
Exact five tables, prefix0..25, fingerprints/AST, populated coherence, counters,
partial recovery, observers/locks/native interruption проверены. Engine disabled,
registry тоже disabled, canonical frontier **13**, новую version не резервировать.
Archive `selection-schema-green-__f6seo4`, manifest
c7646f83162667ad8af1bda60d1f13921e30783fffa8376503721784f19ed2f9,30PASS.

### Registered original composition reader — Gate5 APPROVED, unwired

Source `8ca5103f99a54758fe2c015a4497af3dd9466c54`.
Spec `ASSIGNMENT-ORDER-REGISTERED-COMPOSITION-READER-001` v0.1.
Review `reviews/code/ASSIGNMENT-ORDER-REGISTERED-COMPOSITION-READER-001-v1.md`.
Stable find(caseId,orderId), registry dispatch, other-case nondisclosure, one RR
snapshot, targeted selection hash/member validation. Existing physical reader
unchanged. Do not implement extra legacy behavior: that branch already exists.
Production original factory AND locked composition check still physical-only;
new reader must be bound with the locked check before original-first integration.
Archive `registered-composition-reader-green-dlodza6_`, manifest
d38e250f83b45c64d4aa5b26f1fbdf922a57906d0b829c8ab9bbfda072dd969e,15PASS.

### Fresh selection application core — Gate5 APPROVED, no native adapters yet

Spec `ASSIGNMENT-ORDER-COMPOSITION-SELECT-001` **v0.11**, SHA
d9fe7f3c47e66d059138117f99609493b8a00b1d4e4166582b3185a8037d5f1e.
Gate1 chain: fresh-selection-gate1-v09-2026-09-06.md and
fresh-selection-construction-review-2026-09-06.md under operations.
v0.10 added public factory/typed dependencies. v0.11 added the eighth port
SelectionTerminalAttemptUnitOfWork: object_not_found atomically stores terminal
request+audit without fake caseId, case lock or identity allocation.

Core source `74ba2d0113d445d08ab8158cb947cd8f69f61801`:
`app/AssignmentOrderComposition/`,77files/1035lines (mostly public types), max53lines.
One `AssignmentOrderCompositionFactory::create(SelectionDependencies)`;
new_order/replace_pending, eligibility/state, lazy clock, cached outcomes,
unknown/race reauthorization/fresh-reader closure and no-case route implemented.
No renderer/storage/native SQL/HTTP caller. Fresh state rejects legacy ownership.

Tests: tests/AssignmentOrderComposition/selection_command_{tracer,outcomes,recovery}_001_test.php
(4+54+15=73), Support/SelectionCommandFixture.php. Tracer/core Gate3 reviews exist;
final `reviews/tests/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001-core-v2.md` APPROVED.
Reviewer withdrew malformed UoW RETURN echo checks: wrong echoes must be rejected
by native UoW before mutation; no speculative app outcome for a lying port.
Final core Gate5: `reviews/code/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001-core-v1.md`.
Native DB/UoW/race/integration behavior is NOT approved by these pure-port tests.
Parent main tasks remain open where they also require native persistence/wiring.

### Architecture lexical correction — separate Gate5 APPROVED

Raw scanner mistook enum SELECT/class constant and dotted capability atom forSQL.
Measured blocker fixed in `tools/architecture/check.py`, no file allowlist,
baseline growth, SQL permission widening or source name obfuscation. Quoted SQL,
same-line real SQL and original fingerprints preserved.32tool tests PASS.
Reviews `ARCHITECTURE-PHP-SELECT-TOKENS-001` tests v1/v2 and codev1 saved.

**Final combined exact clean HEAD** `f285ed66d592366114141f85ee218036170175b5`:
archive `selection-core-final-green-izgviv32`, manifest SHA
1123980f62a97801a3db31c17bba7cbf62c637bd3040623e362f5abdf58cec02.
Complete=true, cleanBefore/After, coreUnchanged=true, baselineUnchanged=true;
84commands PASS (73core cases,32tool tests,architecture7,OpenSpec,diff,77lints).
Initial failed capture `selection-core-green-mvirkfwa` retained, not skipped.
All archives under `/Users/antropophag/.local/state/fmonitor2-verification/`.
Independent reviewers root/selection_core_gate3 and root/sql_token_review finished,
no source edits by reviewers. No review outstanding.

## Next bounded work / actual critical path

1. Specify/confirm native construction for the **same approved core** and implement
   real eight ports through Gates, especially MariaDB case UoW + allocator +
   accepted/terminal writes, separate no-case terminal UoW, read/authorization/
   clock/fresh recovery/audit. Use approved five-table/registry metadata; no new
   template table and no legacy writer conversion. Start bounded, e.g. native
   contract+public RED/Gate3 first; do not rerun/rewrite core73 without a reason.
   Current architecture sql_owner() has no AssignmentOrderComposition MariaDb
   owner branch yet: adding real native adapters needs narrow ownership policy
   coverage/review, not baseline exceptions or moving SQL into application classes.
2. Wire original reader and locked validation to selection, on-demand PDF/date/
   audit, HTTP, application of accepted original, separate opening. Reuse original
   command approval. These remaining slices are not implemented by core Green.
3. Fresh fictional TESTUSER/bootstrap/new routes only, full exact-SHA make verify
   literal VERIFY_OK, permitted CI/publication, Actions exact SHA, clean deployment,
   restart/persistence/login/golden path and requirements audit with0launch blockers.

No need to gate isolated selection code on completion of all later PDF/HTTP/
opening implementations. Full launch still requires them. Deadline remains
2026-09-09 09:00 Europe/Moscow; schedule risk high.

## Local preview is RUNNING — keep it for owner

URL `http://127.0.0.1:8092/pilot/login`.
Login `fmonitor-preview-testuser@shlz.ru`; password only external0600
`/Users/antropophag/.local/state/fmonitor2-local-preview/preview.env` (already given
to owner). Do not print real environment credentials or import production data.
Compose project `fmonitor2-local-preview`; both pilot and MariaDB healthy.
Image remains `fmonitor2-local-preview:1eba93cf966d` — reviewed bootstrap-loading
fix, not latest unwired selection core. Existing test-db23306 also healthy.
Only owned preview volumes: fmonitor2-local-preview_mariadb-data / pilot-state.
External directory has override/config/ownership, fixture setup script and HTTP
captures. No Bitrix profile/import-production used. Do not delete unrelated volumes.

Verified login/objects/calendar/roles200; objects currently0visible. Users503 is
still a separate request/session blocker; installers403 for bootstrap superadmin
without process permission is expected. No visual browser QA: CUA unavailable.
See `docs/operations/local-preview-2026-09-06.md` for setup and limitations.
Actual HTTP500 redeclaration fixed by2canonical require_once changes after
PILOT-ENTRYPOINT-LOAD-001 Gates1–5. Source1eba93cf966d49b85d56e1a3b38e2096a6618f0d,
review `reviews/code/PILOT-ENTRYPOINT-LOAD-001-v1.md`; auth shadow protection retained.
Preview is not full portal readiness; do not claim all buttons/scenarios work.

## Durable prohibitions / cost / shutdown state

- Never change/merge draft PR10. No Quality Graph/bootstrap CI PR/publication
  before first literal full VERIFY_OK on exactSHA. It does not exist yet.
- Protected E2E unchanged, only earlier recorded owner-approved admission patch.
  No failures→skip/allowed/earlyexit; no rejected native interception/permission probes.
- Original denied-invocation audit approval remains: each invocation gets audit.
  Owner approvals need no re-asking. Appendix/history contracts superseded only
  by explicit fresh/no-template-storage decisions, not by convenience.
- ../fmonitor read-only evidence; ../shlz-ui public exports; secrets/primary
  evidence outside repo. DDL InstallationProcess *SchemaMigration, SQL MariaDb
  adapters, one public mutation owner; no new>=150line hotspot/baseline ratchet.
- No remote mutation this session. Before integration revalidate remote;
  inherited integration75a642476224abe9ec99905777164b4279e743a7,
  QG lineagef07548135fe930e7a8fb9bb97271c9f05a8ebfc1.
- Synthetic DB env: PATH prepend /opt/homebrew/bin and Docker.app/.../bin;
  FMONITOR_TEST_DB_HOST=127.0.0.1,PORT=23306,ADMIN_USER=root,
  ADMIN_PASSWORD=fmonitor2_test_root_local (all names FMONITOR_TEST_DB_*).

Core package limit30min/150k exceeded. Baseline1234352; after final reviews and
record commit observation1467551 =>233199 tokens, including tooling correction,
checkpoint work and owner session-context question. No further source work after
restart request. This is an observation, not falsely reported as final thread cost.
Use smaller bounded packages and compact reviewers (fresh fork none gpt-5.6-sol
low; reuse within one small family, no old huge maintenance context). Do not use
context fullness alone as justification to repeat completed work.

All verification handles terminal, including54931/26263/49929. All reviewer agents
completed. Only intentionally running services are preview and synthetic test DB.
Goal ACTIVE; no new goal budget. Final handoff commit/clean status in closing prompt.
