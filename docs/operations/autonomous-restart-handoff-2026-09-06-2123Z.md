# Autonomous restart checkpoint — 2026-09-06 21:23 UTC / 7 сентября МСК

Owner попросил restart после Gate5. Final template Gate5 APPROVED, feature work
остановлена. Только review-record/handoff commits после этого. Goal ACTIVE,
не complete/blocked. Review checkpoint7748cd62c0d8bb37c6ac68ed520a4845fdeee079;
exact final HEAD будет в closing/start prompt. Никакой remote mutation не было.

## Restore first

Repo /Users/antropophag/code/fmonitor-2, branch codex/remove-pilot-work-navigation-v2.
Сначала get_goal. Только если отсутствует, восстановить EXACT objective БЕЗ budget:

> Довести портал FMonitor 2.0 от фактического состояния repository и remote до проверяемой готовности к запуску в тестовую эксплуатацию, соблюдая все delivery gates, append-only evidence, exact-SHA verification, CI, clean deployment/restart/golden-path и отсутствие launch blockers

Проверить HEAD/clean tree. Полностью прочитать AGENTS.md, PRODUCT.md, CONTEXT.md,
pilot spec/data model, docs/development-process.md и этот handoff. Не перечитывать
всю старую историю. Перед rapid-pilot прочитать его AGENTS. Relevant apply skill.

## Owner scope — unchanged and controlling

- Чистый запуск: исторических данных/PDF нет. Не делать old-writer migration,
  historical compatibility/mixed rollout ради запуска.
- PDF-template по запросу с сегодняшней Moscow date, без хранения files/versions.
  Сохраняются дата последнего успешного формирования и append-only audit.
- Original/correction immutable; documentDate из original, не template/upload time.
- new_order и replace_pending обязательны. Selection/upload/template не применяют
  состав и не открывают работы. Application и opening остаются отдельными slices.
- Technical decisions autonomous; новые product вопросы отдельно. Не возвращать
  manual registration gate. Owner approvals не спрашивать повторно.
- Ограничивать пакет result/evidence/time/cost, включая reviews/commit. После
  превышения менять подход. Много времени ушло на backend и повторные контексты;
  нужен переход к рабочему пользовательскому сценарию, не новые completeness matrices.

## Completed since previous1733Z handoff — reuse approvals

### Native selection binding — full standalone Gate5 APPROVED

Core SELECT-001 v0.11 сохраняет прежнее approval. Восемь native ports теперь есть:
app/AssignmentOrderComposition/MariaDbSelection*.php,
ProductionAssignmentOrderCompositionFactory::create(mysqli, Closure freshConnect, prefix).
Full native review: reviews/code/ASSIGNMENT-ORDER-SELECTION-NATIVE-001-native-v1.md.
Native sourcec93c27a, final native testedbe0fe02; 56cases, architecture/OpenSpec/diff
PASS archive selection-native-combined-rny_xymw. Subsequent original query extraction
has its own related reader/selection regression (below). No full portal VERIFY_OK.
Includes real same/different-case/request-key races, response-loss restart, native
exception/boolean interruption, capacity, payload validation, audit independence,
prefix25 and accepted-root state. effectiveOrder null is standalone non-applying
scope only; actual effective owner must be bound by application/opening integration.

### Selected composition → original command — full binding Gate5 APPROVED

Change bind-assignment-order-original-to-selected-composition complete (not archived).
Spec ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001 v0.1.
Review reviews/code/ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001-binding-v1.md.
Source9eab3b1, lifecycle evidencefdbd634. Archives selected-original-green-w7cwj56u
(26commands including original/reader/selection/architecture/lints) and
selected-original-lifecycle-final-4ru_g0az (6lifecycle+2direct+4worker regression).

Use ProductionAssignmentOrderOriginalFactory::createForSelections(db,config,fresh)
for fresh flow. It binds registered preflight and locked target validation together.
Old create/createRecoveryReady intentionally remain physical-only. Shared case lock
serializes replacement/upload. NOT_CURRENT/COMPOSITION_NOT_CURRENT map to existing
conflict/target_not_current. Historical accepted-root correction/replay works after
new pending selection. No fallback, no template dependency. HTTP is NOT wired yet.

Original denial differs from selection denial: inherited ORIGINAL-ATTEMPT-AUDIT-001
section3 permits first denial terminal+audit; repeats preserve terminal and append
one audit each. Do not incorrectly replace this approved policy with selection's
no-terminal-on-denial rule. The mistaken test expectation was corrected, not code.

### Canonical registration — Gate5 APPROVED; actual frontier now15

bin/fmonitor2-migrate.php registers registry14 and selection15 after existing13.
Spec SELECTION-CANONICAL-REGISTRATION-001; review
reviews/code/SELECTION-CANONICAL-REGISTRATION-001-v1.md. Sourceea2fca9.
Archive selection-canonical-green-bsswpvq5: focused6/PMR/originalAudit21/architecture/
lint/diff PASS. ConsumerGate3 preserves oldv13 metadata and validates exactnew7tables
through approved readiness seams. Engines unchanged. Do NOT reserve another version.
This registration did not migrate/rebuild the running preview or enable routes.

### On-demand PDF generation — FINAL Gate5 APPROVED

Spec ASSIGNMENT-ORDER-TEMPLATE-GENERATE-001 v0.2 SHA
 ec209b39d71e75f27b34b5c2c3027effc0ac472e694d2fccd336ae0ddf8b250e.
Review reviews/code/ASSIGNMENT-ORDER-TEMPLATE-GENERATE-001-v1.md.
Source7d9888b; final boundary evidence2ed8e11. Archives:
- template-generation-green-berixc_1: template/render/original/selection,
  architecture/lints/diff and real PDF QA;
- template-boundaries-final-wzb8fbb_: 21boundary +2tracer PASS, exact clock counts.

ProductionAssignmentOrderTemplateFactory::create(db,prefix) returns owner with
 generateAssignmentOrderTemplate(caseId,orderId,actorId): array.
Success returns PDF bytes, date, filename/media metadata. Exact capability reuses
assignment_order.composition.select. Case lock held through render/audit/commit.
Only fm2_process_events event assignment_order_template_generated is persisted;
no template files/versions/new schema. Existing ProductionPdfAssignmentOrderRenderer
is reused without Storing wrapper. dateReader(db,prefix)->find(case,order) projects
last successful date by greatest event ID; caller must authorize its read.
Task3.2 in parent selection change is complete; parent overall still open.

QA PDF is verification-only, NOT application storage:
/Users/antropophag/.local/state/fmonitor2-verification/template-pdf-qa-s2auz_s4
contains template.pdf, text.txt and page1–3 PNGs. All3 pages visually inspected:
07 сентября2026, crew/object/appendix legible, no clipping/overlap. MuPDF installed
only in external /Users/antropophag/.local/state/fmonitor2-verification/pdf-qa-env.
Poppler absent. No project dependency added. PDF layout renderer source unchanged.

## Preview operational blocker at checkpoint — do not hide this

Preview processes are still running, but at21:17UTC login returned503 and pilot
container is unhealthy. This is NEWLY OBSERVED, cause not localized. It is not a
new feature deployment: image still fmonitor2-local-preview:1eba93cf966d; only
pilot-state volume mounted, no repository bind mount. MariaDB healthy. No preview
files/data/volumes were reset or changed. Read-only health/log inspection only.

URL http://127.0.0.1:8092/pilot/login. Earlier login/objects/calendar/roles200 and
users503 are in docs/operations/local-preview-2026-09-06.md and old1733Z handoff.
Current login503 supersedes earlier healthy claims. Logs showed request accept/close,
no useful exception. Healthcheck fetches objects/installers. Do not invent a cause.
First next-session operational step: diagnose/restore this owned preview without
blind resets or data deletion. After final Gate5 owner requested session restart,
so no new fix slice was started here.

Compose fmonitor2-local-preview; owned volumes only
fmonitor2-local-preview_mariadb-data / fmonitor2-local-preview_pilot-state.
Login fmonitor-preview-testuser@shlz.ru; password remains only external0600
/Users/antropophag/.local/state/fmonitor2-local-preview/preview.env. Do not print it.
Synthetic test DB remains at127.0.0.1:23306; use FMONITOR_TEST_DB_* names, root /
fmonitor2_test_root_local. PATH prepend /opt/homebrew/bin and
/Applications/Docker.app/Contents/Resources/bin. No production imports/Bitrix used.

## Actual remaining launch work

1. Diagnose current preview503 as a bounded operational/critical-fix task with gates
   if code changes are needed. Keep owner preview available; do not claim readiness.
2. Wire fresh HTTP/UI selection/PDF/original and date prefill to approved factories.
   Existing expose-assignment-order-original-http planning must be reconciled with
   current contracts. No calling old prepare to manufacture identity or hidden PDF.
3. Implement prospective application of accepted original and separate opening,
   including real effective projection/assignment/checklist preservation. These
   are NOT implemented by selected-original upload or template generation.
4. Fresh fictional TESTUSER/bootstrap/new routes; old prepare/registration/direct
   physical writer entries must not remain alternate fresh-portal mutation paths.
   separate-pilot-generation-metadata is deployment identity/restart work, unrelated
   to PDF generation; do not confuse these changes by keyword.
5. Full exact clean-SHA make verify with literal VERIFY_OK, requirement audit,
   permitted CI/publication and Actions exactSHA, controlled clean deployment,
   restart/persistence/login/native golden path. No full VERIFY_OK exists yet.

## Durable restrictions / shutdown

Never modify/merge draft PR10. No QualityGraph/bootstrap CI PR/publication before
first literal full VERIFY_OK. Protected E2E unchanged except earlier approved
admission patch; no failures→skip/allowed/earlyexit. No rejected native interception
or permission probes. ../fmonitor read-only; ../shlz-ui public exports only.
Primary evidence/secrets outside repo. No new>=150line hotspots or baseline ratchet.
No remote mutation in this session; before integration revalidate remote. Inherited
integration75a642476224abe9ec99905777164b4279e743a7, QG lineagef07548135fe930e7a8fb9bb97271c9f05a8ebfc1.

All verification handles terminal, latest15639/10591 included. Reviewer finished;
no review outstanding. Only intended service containers remain running. Goal stays
ACTIVE without budget. Last observed cumulative goal usage1912870 tokens (not final
billing/usage). Significant overruns occurred: use short context and tightly scoped
reviewers (fresh fork-none gpt-5.6-sol low when needed, reuse only within small family).
After this handoff commit do not start new feature work in this old session.
