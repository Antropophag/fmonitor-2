# Autonomous checkpoint — 2026-09-07 04:25 UTC / 07:25 МСК

Persistent goal ACTIVE, без token budget; не complete/blocked:

> Довести портал FMonitor 2.0 от фактического состояния repository и remote до проверяемой готовности к запуску в тестовую эксплуатацию, соблюдая все delivery gates, append-only evidence, exact-SHA verification, CI, clean deployment/restart/golden-path и отсутствие launch blockers

Branch codex/remove-pilot-work-navigation-v2. Closing HEAD указывается в ответе
после commit этого файла. Latest implementation45d7a668170e49e9edbba13977513a4c4fcd7e7f.
На старте продолжения goal уже существовала, HEAD185d95c совпал с предыдущим
checkpoint. Исходный user-expected5d407ff был проверен при прежнем restore;
никакого reset к старому SHA не делать. Старые approvals переиспользованы.

## Completed now — original application reference

Change read-assignment-order-original-application-reference:6/6tasks complete,
не archived. Spec ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001 v0.1,
SHAcf1ae6a3da0683cc7d4f5040dd03e72963b5f4b7958cd2b71101c8d8c7c46841.
Gate1 +Gate3initial/v2 +Gate5 APPROVED. Test/REDf60dcea, initial sourcef68fa7a.

Public in-process factory/reader in AssignmentOrderOriginal:
- readCurrent(objectId,orderId) — own idle-only consistent readonly snapshot;
- immutable exact14field metadata reference, no private path/content identity;
- confirmCurrent(ref) — same issuer/DB/nativeconnection/charset, caller writeTX,
  case FOR UPDATE first, explicit current locking reads all source/backing;
- matched/changed/unavailable, no begin/commit/rollback/close/wait-policy edits;
- owning-module StoredReader/lineage/audit/event+registered selection reuse;
  existing helpers defaultfalse locking preserve earlier snapshot readers;
- private seal protects original referenced revision, immutable root, registry,
  selection header+members; current pointer is separately compared;
- no PDF byte-availability claim, actor grant, application/date/latest-order policy,
  schema version, facts/audit/FS writes or HTTP wiring.

Native14cases prefix0/25: exact fields, clone-by-value metadata, RR snapshot stale
while other original correction commits, current guard changed; real correction
worker waits on case lock until caller rollback; sentinel proves caller ownership;
corruption, foreign issuer, null DB, closed/charset/DB change, missing/prefix cases.
9 original/selection/HTTP regressions PASS, architecture7/full lint/diff PASS.
SelectedOriginalFixture now optional prefix. No default fixture semantic changes.

### Supplemental clone finding and repair — CLOSED

After initial Gate5, root found PHP shallow clone shared WeakMap issuance.
Independent P2 CHANGES_REQUESTED retained in
reviews/code/ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001-clone-finding.md.
External native repro actually returned matched through cloned reader.

Four additional native cases: clone before/after issuance ×prefix0/25; both own
proofs matched, both cross-issuer proofs unavailable, same metadata and caller
sentinel/rollback/allfacts/files preservation. RED external overlay on frozen18916ae
returned4matched. Independent Gate3 APPROVED exact test SHA
df73f74e28687686f118fe214212c5734c756b4a0ff71370fb83b6647bf2721f.
Byte-identical test adopted only AFTER fullverify18916ae terminal.

Source45d7a668 adds public __clone() with new WeakMap; copied readers keep borrowed
connection/source but are distinct issuers. New4cases +original14regression PASS,
including2real blocked workers. Supplemental Gate5 APPROVED:
reviews/code/ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001-clone-repair.md.
No need repeat old Gate1/3 findings; P2 closed. Details:
- docs/operations/original-application-reference-{red,green}-2026-09-07.md
- docs/operations/original-reference-clone-repair-2026-09-07.md
- external /Users/antropophag/.local/state/fmonitor2-verification/original-reference-20260907
All verification handles terminal, owned native fixture resources cleaned.

## Completed now — local Users503 recovery

Change configure-local-pilot-trusted-scheme:5/5tasks complete, not archived.
Spec PILOT-LOCAL-TRUSTED-SCHEME-001 v0.2; Gate1initial/v2/v3, Gate3, Gate5 and
operational supplement APPROVED. Source87c618d4eba3b69b06b36a4d3556eb7d5f55a920.

Actual preview fresh login gave objects/roles200/users503. Native read-only
catalogue+public render were healthy; trustedScheme getenv=false. compose.yaml
now explicitly sets FMONITOR_TRUSTED_REQUEST_SCHEME:http for loopback HTTP.
No backend default/fallback, forwarded-header trust or grant added.
Native test consumes effective Docker Compose JSON, ignores ambient https, runs
real rapid-pilot/router.php with native administrator99+access.administer and
owner-backed session. Users200/actiontokens committed; missing scheme+forwarded
header stays exact503, DB/privatefiles unchanged. Both native controls GREEN;
originalHTTPflow/sessiontoken/sessionflash regressions PASS, architecture7/lint/diff.

Fixture correction: PHP proc_open DROPS empty env values. SelectionHttpFixture
uses native env KEY= argv before unchanged PHP/router to preserve explicit empty
prefixes. Earlier setup failures (missing OriginalRuntime, roles404) excluded from
RED; intended RED was Users503 with healthy login/roles. No native interception.

### Preview current operational configuration — IMPORTANT

8092 still exact OLD image fmonitor2-local-preview:1eba93cf966d,
imageID sha256:8fa07372e5076ca5d488a8c8cde42257d832c9fb7a199e0813e95d78a829ec8b.
New reference/upload/selection source was NOT deployed by this recovery.

Existing old bootstrap is NOT idempotent for auth/role timestamps/counters.
To preserve state, external compose.override.yaml now mounts readonly startup:
/Users/antropophag/.local/state/fmonitor2-verification/users-preview-20260907/runtime/start.sh
→ /opt/fmonitor-preview-runtime/start.sh, entrypoint sh that file.
It runs only the same two socat listeners +existing rapid-pilot/start.php,
WITHOUT bootstrap/migrations/import. Same state volume and old approved readonly
healthcheck12efd02 mount retained. No build/pull/newimage/schema/grants/productionimport.
External ownership.json updated with recipe hash/source/evidence. Do not silently
replace this override with ordinary docker-entrypoint.sh during later restart.

Before recovery: private state tar, all-row/DDL snapshot, manifest/session hashes,
container/override backups. AFTER recreation BEFORE new smoke-login:
51tables rows/DDL exact, manifest exact(no nonce refresh),3308old sessions exact,
0new sessions, same immutableimage/namedvolume, healthy. Independent reviewer
recomputed entire raw before==after structure. Fresh smoke login→objects200,
roles200, users200(actual12456bytes), objects200. Allold3308sessions still exact;
normal login only advanced auth_attempts AUTO_INCREMENT, all DB rows unchanged.
Users503 is therefore NO LONGER a launch blocker. Do not re-open old diagnosis.

Docs: docs/operations/local-users-trusted-scheme-2026-09-07.md.
Primary /Users/antropophag/.local/state/fmonitor2-verification/users-preview-20260907.
Credentials remain only external0600 preview.env; NEVER print it/cookies/snapshots.
Preview project volumes fmonitor2-local-preview_mariadb-data and ..._pilot-state
preserved. MariaDB service not recreated. Preview intended to keep running.

## Latest full verification — FAILED, no VERIFY_OK

Literal make verify clean-start source18916aef904e37ddfbcf8afd9adb6abcdf642654:
- reset, migrate15, architecture7, lint, unit, characterization, diff PASS;
- DB FAIL only pilot_demo_bootstrap_001_test.php and pilot_e2e_flow_001_test.php;
- E2E FAIL same protected test;
- terminal exit2, FULL_VERIFICATION_FAILURE count=2 stages=db-test,e2e-test.
New reference14cases and local trusted-scheme test each ran once and passed in
canonical runner. Clone repair45d7a668 is AFTER this run; its4+14focused tests PASS,
not a claim of a new full makeverify on repaired source.

Raw users-preview-20260907/make-verify.log, derived full-verify-summary.json.
Protected tests/InstallationProcess/pilot_e2e_flow_001_test.php SHA256 unchanged:
8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b.
First assertion remains line157 «Сформировать распоряжение»; whole protected flow
stillprepare→manual1Cregistration→open. Do not merely restore label/oldwriters or
weaken assertions/skip/fail-allow to green. Native application/opening/golden path
and resolution of protected integration contract are still pending.

## Required owner answer — still pending, do not infer it from elapsed time

Parent openspec/changes/apply-assignment-order-original-to-composition has ONLY
proposal +NEEDS_GRILL, no application spec/code/schema or version16 reservation.
Earlier async question (not answered): if composition applied but work NOT opened,
and original date corrected e.g.Sept2→Sept1, allow separate reapplication of corrected
revision preserving prior application fact, or freeze applied date permanently?
After opening historical assignments/checklist attribution stay immutable.

Do not choose UNIQUE(order), correction/applicability schema/semantics before this
answer. Existing owner decision already says sequential new order acts prospectively
from DOCUMENT DATE, not upload/apply timestamp. Do NOT ask that again. Upload or
correction never applies composition or opens work. Readonly reference prerequisite
is now complete and does not settle parent policy. See original owner2026-09-02,
v4approval2026-09-04, fresh-launch owner scope2026-09-06 and HTTPread owner2026-09-05.

## Remaining launch critical path / restrictions

1. Native prospective application +effective projections, installer assignments/
   counters and actual assigned-engineer authority, preserving old checklist facts.
2. Separate native opening from applicable original/applied composition, actual
   date and immutable opening/checklist snapshot.
3. Full original history/download/all-role HTTP read; engineer scope must use
   application owner, never legacy responsstroicontrol. Upload UI child already done.
4. Fresh fictional TESTUSER/native bootstrap/golden path; actual Bitrix initial+
   periodic sync/current assignments still not verified. No production data imports
   or Bitrix run authorized in this session.
5. Protected E2E reconciliation, clean exactSHA literal VERIFY_OK, permitted CI,
   launch-blocker audit and controlled clean deployment/restart. None waived.

Original HTTP upload child remains complete with prior Gate5/browser evidence;
canonical native runner and frontier15 consumer repairs from previous checkpoint
remain approved. Frontier consumer bootstrap task still blocked by protected E2E.
Read previous handoff autonomous-original-http-completion-handoff-2026-09-07-0154Z for details.

Remote read-only revalidation2026-09-07~04:06UTC:
- main2bff0a0e6baaab61679321001c57cbc916609295;
- branch remote75a642476224abe9ec99905777164b4279e743a7;
- PR10 OPEN/DRAFT, head3ae214f75b898d171c68bb127dec10f17e03117a,
  base main, no checks reported. No remote mutations performed.
Never modify/merge PR10; no QG/bootstrapCI publication before literalVERIFY_OK;
no remote mutation in this session. All original handoff restrictions retained.

Read AGENTS/product/pilot/process contracts. Use public shlz-ui; owner replaced
WindowsServiceDesk search with «просто используй shlz-ui». ../fmonitor read-only;
primaryevidence/secrets outside repo. No baseline ratchet/new>=150line production
hotspots. Review agents original_gate1_v3/original_gate3/original_gate5 reusable,
all current tasks completed. PATH Homebrew/Docker; explicit synthetic DB127.0.0.1:
23306 root/fmonitor2_test_root_local. All handles terminal, only main worktree.
