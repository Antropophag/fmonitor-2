# Context restart handoff — 2026-09-06 23:50 UTC / 7 сентября02:50МСК

Owner уходит спать до08:00МСК и явно хочет новый чистый context. Root сначала
неверно понял вопрос как operational restart; owner уточнил. Feature work
остановлена перед original HTTP GREEN. Этот commit сохраняет planning/RED/tests;
никакой original HTTP production implementation ещё не написан.
Goal ACTIVE, не complete/blocked. Exact closing HEAD будет в start prompt.
Pre-checkpoint approved HEAD adcef69dd413ad1809dba4cf0545b5036e2f26ed.

## Restore

Repo /Users/antropophag/code/fmonitor-2, branch codex/remove-pilot-work-navigation-v2.
Сначала get_goal. Если отсутствует, восстановить EXACT objective БЕЗ token budget:

> Довести портал FMonitor 2.0 от фактического состояния repository и remote до проверяемой готовности к запуску в тестовую эксплуатацию, соблюдая все delivery gates, append-only evidence, exact-SHA verification, CI, clean deployment/restart/golden-path и отсутствие launch blockers

Проверить HEAD/clean tree; полностью прочитать AGENTS.md, PRODUCT.md, CONTEXT.md,
docs/fmonitor-2-pilot-spec.md, docs/fmonitor-2-pilot-data-model.md,
docs/development-process.md и этот handoff. Rapid-pilot AGENTS до его изменений.
Relevant openspec-apply-change skill. Не перечитывать всю старую историю и не
переоткрывать уже одобренные domain/UI scopes.

## Controlling owner scope

- Чистый launch без исторических данных/PDF. Не мигрировать old writers ради
  compatibility. State-changing application seam один, история append-only.
- PDF optional/on-demand с Moscow today; без template files/versions; latest
  generation date + audit. Original documentDate из документа, uploadTime отдельно.
- new_order и replace_pending обязательны. Upload/selection/template не применяют
  состав и не открывают работы. Application и opening — отдельные действия/slices.
- Owner явно сказал2026-09-07: «просто используй shlz-ui». Это заменяет прежнее
  требование искать Windows ServiceDesk source; public shlz exports доступны.
- Справочник монтажников обязателен к launch, включая реальные текущие закрепления.
  Owner отдельно напомнил: монтажников надо тянуть/обновлять через Bitrix webhook.
  В repo есть REST user.get hourly worker, но в preview профиль bitrix выключен.
  Нельзя считать web-hook launch проверенным на fictional fixtures. Secrets вне repo.
- ОТиЗ: все распоряжения/прошлые original revisions по explicit read grant обязательны;
  official premium calculation исключён из первого pilot increment в pilot spec.
  General read/download scope НЕ завершён операторским upload child.
- Technical decisions autonomous; новые product вопросы отдельно. Не повторять
  owner approvals. Каждый пакет ограничивать результатом/checks/time/cost с review
  и commit. Значительная часть текущего времени ушла на context/reviews; держать
  следующие чтения и review packs короткими. Goal counter23:50UTC923050tokens,
  elapsed8523s (не финальный billing). Owner просит restart именно ради context.

## Completed / approvals to reuse

Предыдущие native selection, selected-original binding, canonical registry14/
selection15 и template approvals полностью сохранены. Подробные ссылки в
`autonomous-restart-handoff-2026-09-06-2123Z.md`; не повторять тот audit.
Canonical migration frontier15; новых versions не резервировали.

### Preview503 fixed, standalone Gate5

Cause: old healthcheck терял cookie, создавал anonymous sessions каждые5s.
Native session root10001 entries превысил bound10000 → start(null) gc_failed.
2495 auth_return_to-only anonymous sessions сохранены rename в private incident
archive на том же owned volume под locks; bytes SHA verified, locks оставлены.
Никаких deletions/reset. Authenticated sessions preserved.

Health probe теперь сохраняет private anonymous cookie, bounded same-origin
redirects/timeouts и явную checked cookie write/flush/fsync/rename. Curl silent
cookie write failure был пойман Gate5 и исправлен через native RLIMIT_FSIZE RED.
Final source12efd022ad4b1879cb541a0fa4095705e15e803e, Gate5 APPROVED:
reviews/code/PILOT-HEALTHCHECK-SESSION-001-v2.md.
Ops evidence: docs/operations/local-preview-503-recovery-2026-09-07.md.

8092 сейчас healthy на ТОМ ЖЕ image fmonitor2-local-preview:1eba93cf966d. Только
approved probe12efd02 readonly-mounted в /opt/fmonitor-healthcheck. Старый image,
DB и state backups сохранены. External compose.override.yaml фиксирует mount.
Linux20 repeated probes сохраняют exact9107 session/lock entries и bytes.
6 authenticated session files preserved byte-for-byte через recreation.
Login/objects/calendar/roles200; admin/users503 остаётся отдельным blocker;
installers403 у bootstrap-admin не является автоматическим process grant.

### Native selection/PDF HTTP/UI — full scoped Gate5

Change expose-assignment-order-composition-http complete, not archived.
Source333e6633f825b469d932a69b4fb1d9c51a043081 + UUID correction
bfcf6c03b2cdd4c50f4eb4dba36ce985969d3070. Gate5 APPROVED:
reviews/code/ASSIGNMENT-ORDER-COMPOSITION-HTTP-001-v2.md.
Spec ASSIGNMENT-ORDER-COMPOSITION-HTTP-001, read/transport/DOM tests approved.
Ops: docs/operations/composition-http-green-2026-09-07.md.

FMONITOR_FRESH_ORDER_FLOW=1 activates selection GET/HEAD/POST and template POST.
New public read model in AssignmentOrderComposition; FreshOrder* HTTP classes.
Old prepare GET303→selection; fresh old prepare/registration/direct engineer/open
POST writers and old artifacts410. Flag does not grant rights or migrate data.
Real HTTP new_order/replay/replace_pending/PDF/new-after-accepted-root, security,
read dependency failure and full retry form GREEN. UUIDv4 transport only; underlying
selection domain UUID grammar remains unchanged.

Chrome CUA real login → checkbox/radio/confirmation → keyboard save → replace7002
→ PDF download → date2026-09-07 passed; modern shell/public shlz, visible neutral
focus. Final layout inspected. Browser PDF100595bytes SHA
b3035e0605b524288438a282532d85e1fccb807432ec1a6f376e35c10a62cef4;
application private files0. CUA temporary tabs and task server64906 closed/cleaned.
Safari AX works but screenshot blank/elementHasNoFrame; use native Chrome via
/Applications/Google Chrome.app (bundleID ambiguous due mounted installer).

Impeccable was absent locally. Official pbakaus/impeccable source
bdfc59ee30b978f6fd01e74dba035d44a4a59c22 cloned ONLY to external verification dir;
CLI downloaded checksum-verified engine to external IMPECCABLE_HOME. Final scan[];
no project dependencies or Codex hooks changed. Command below may be reused:
IMPECCABLE_HOME=/Users/antropophag/.local/state/fmonitor2-verification/impeccable-runtime
node /Users/antropophag/.local/state/fmonitor2-verification/impeccable-detector-20260907/cli/bin/cli.js detect --json <changed-view-files>

## Exact current work: original upload UI, BEFORE GREEN

Active change expose-assignment-order-original-upload-ui. Parent
expose-assignment-order-original-http retains general history/download/all-role
scope and full VERIFY Done; child does not silently close those requirements.
Reason: assigned-engineer read must use actual composition application owner,
which is still missing; do not use legacy responsstroicontrol as authority.

Spec ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001 currentv0.3. Gate1v0.2 APPROVED:
reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001-gate1-v2.md.
V0.3 adds narrow form CSP script-src/self + connect-src/self (no worker/blob) needed
for same-origin binary fetch; THIS SMALL AMENDMENT NEEDS Gate1 re-review.
NO Gate3 yet, NO original HTTP production code. Next: finish spec amendment review,
independent Gate3 of saved RED tests, then minimal GREEN. Do not skip those gates.

Chosen technical transport: PDF raw application/pdf body ≤20971520 with required
Content-Length, canonical base64 UTF8 JSON X-FMonitor-Original header≤16384.
JSON keys/order/types explicit in spec; avoids PHP multipart duplicate erasure.
HTTP metadata mode initial/correction lower-case; UUIDv1–5 inherits original core
(unlike selection HTTPv4). Child endpoints POST .../originals and GET/HEAD
.../originals/submit. Forms require explicit local original.read + current action;
POST local upload/correct then native process capability independently. Use
ProductionAssignmentOrderOriginalFactory::createForSelections with real
AssignmentOrderOriginalFreshTerminalReaderFactory (NOT Closure); existing
AssignmentOrderOriginalWorkerResultEncoder gives exact11 fields + LF.

Env planned: FMONITOR_ARTIFACT_STORAGE_ROOT, FMONITOR_ORIGINAL_SAFE_LOG_FILE,
FMONITOR_ORIGINAL_DB_PASSWORD_FILE + existing DB/prefix. No runtime mkdir/DDL.
Original first-denial terminal + repeated denial audit policy is inherited;
never replace it with selection denial semantics.

Saved tests (all original route/client behavior still intended RED):
- tests/Support/OriginalHttpFixture.php;
- tests/AssignmentOrderComposition/original_upload_http_flow_001_test.php;
- .../original_upload_http_admission_001_test.php;
- .../original_upload_http_bounds_001_test.php;
- .../original_upload_http_prefill_001_test.php;
- tests/Verification/original_upload_client_001_test.mjs.
SelectionHttpFixture extended with optional environment closure, post_max_size22M,
null header removal, half-close and explicit closed-not-timeout observation for
raw framing; previous selection HTTP flow regression PASS. These fixture/test
changes still require new independent test review with the original HTTP batch.

RED logs + characterization external:
/Users/antropophag/.local/state/fmonitor2-verification/original-http-20260907.
Ordinary327/exact20MiB/over-limit+1 reach PHP with exact native input length and
16384 metadata header. Raw short/extra frames close EOF before PHP (not timeout),
so no adapter JSON promise; framing-results-v2.json records distinction. Tests
assert healthy server afterwards and unchanged domain facts. Missing Length reaches
PHP null/0. Current real route404 instead200/201/403/etc, client asset missing.
Max-size PDF fixture is checked as real PASSIVE_PDF before target assertion.
Currentv0.3/client tests had minor additions after initial captures; rerun current
files once to pin final RED/hashes before Gate3. Public frozen-clock template fixture
proves yesterday prefill distinct from today; review its setup normally.

## Remaining launch critical path

1. Original operator upload/correction/form/date (current), then full original
   history/download/all-role reader binding as application source becomes available.
2. Actual prospective composition application and effective projections, directory
   assignments/counters and checklist attribution preservation. Not implemented.
3. Separate opening from accepted applicable original, actual date and immutable
   checklist snapshot; no legacy registered-number gate. Not implemented.
4. Fresh fictional TESTUSER/bootstrap/native routes, users503, actual Bitrix REST
   webhook initial+periodic sync and fresh catalog availability/error handling.
   Current hourly-bitrix-workforce.php is rapid adapter, not proof of native gates.
5. Integrate standalone native test families into canonical verify audit: current
   tools/verification/run.sh discovers InstallationProcess and omits new standalone
   native folders. New HTTP suites have focused evidence, not full runner coverage.
6. Exact clean-SHA make verify literal VERIFY_OK, requirements audit, permitted CI/
   publication + Actions exactSHA, controlled clean deploy/restart/native golden path.

## Restrictions and operational handles

No full VERIFY_OK exists. Never modify/merge draftPR10. No QualityGraph/bootstrap
CI PR/publication before first literal full VERIFY_OK. Protected E2E unchanged
except earlier approved admission patch. No failures→skip/allowed/earlyexit;
no rejected native interception/permission probes. ../fmonitor read-only;
../shlz-ui public exports only. No new>=150line hotspots/baseline ratchet.
No remote mutation this session; remote state still needs revalidation before
integration. Inherited integration75a642476224abe9ec99905777164b4279e743a7,
QG lineagef07548135fe930e7a8fb9bb97271c9f05a8ebfc1.

Only intended Docker services remain. Owned preview volumes:
fmonitor2-local-preview_mariadb-data / fmonitor2-local-preview_pilot-state.
External preview config/ownership/credential:
/Users/antropophag/.local/state/fmonitor2-local-preview (preview.env0600; NEVER print).
Synthetic test DB127.0.0.1:23306; FMONITOR_TEST_DB_*; root/
fmonitor2_test_root_local. PATH prepend /opt/homebrew/bin and
/Applications/Docker.app/Contents/Resources/bin. No production imports/Bitrix run.
Reviewers completed, none outstanding; all verification handles terminal before
closing checkpoint. Stop feature work here; resume from this exact boundary in
new context. Persistent goal stays ACTIVE without budget.
