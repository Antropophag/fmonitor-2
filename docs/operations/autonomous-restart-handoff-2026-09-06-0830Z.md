# Autonomous restart handoff — 2026-09-06 08:30 UTC

User requested a suitable point for session restart. This is that checkpoint.
Do not start more work in the old session. Goal remains ACTIVE, not achieved and
not blocked. All known test runner handles are terminal; three separately tasked
reviewers are completed. Preserve their actual verdicts, never infer approval.

## Restore goal and state first

Call persistent get_goal first. If no unfinished goal exists, restore exactly,
WITHOUT token budget:

> Довести портал FMonitor 2.0 от фактического состояния repository и remote до проверяемой готовности к запуску в тестовую эксплуатацию, соблюдая все delivery gates, append-only evidence, exact-SHA verification, CI, clean deployment/restart/golden-path и отсутствие launch blockers

Repository `/Users/antropophag/code/fmonitor-2`, branch
`codex/remove-pilot-work-navigation-v2`. Implementation/review checkpoint is
`56e494a46f201d81f6cd85808f3a3d1f6bc22a33`. This handoff is a following docs-only
commit; its exact HEAD is reported in the closing message. Locate that commit
by this path and verify actual HEAD/status before acting. Worktree was clean
before creating this document. No push, deployment or PR mutation occurred.

Read fully AGENTS.md, PRODUCT.md, CONTEXT.md, docs/fmonitor-2-pilot-spec.md,
docs/fmonitor-2-pilot-data-model.md, docs/development-process.md, the1309Z/1751Z/
1842Z handoffs from09-05 and this one. Read local rapid-pilot/AGENTS before work
there. Historical handoff descriptions of unfinished safe-log/admission/PDF work
are superseded by actual later commits and reviews described below.

User deadline: Wednesday2026-09-09 at09:00 Europe/Moscow. User authorized autonomous
technical decisions, asked product choices be deferred while asleep, and has now
returned. Do not stop at intermediate gates after restart. Keep the full launch
goal, exact-SHA/CI/deploy/restart/golden-path requirements and all prohibitions.

## Approvals and hard prohibitions

Both owner approvals in `owner-e2e-admission-and-pending-selection-approval-2026-09-05-1842Z.md`
are durable. Never ask them again. REPLACE_PENDING and new_order are both retained.
Earlier approvals from1751Z remain valid. No permission for primary evidence,
real documents/personal data/secrets is inferred from VPN/network connectivity.

- Never modify or merge draft PR10.
- No Quality Graph/bootstrap CI PR/publication before first literal full
  VERIFY_OK on exact SHA. No such success exists yet.
- Protected E2E may change only under its exact recorded owner-approved admission
  amendment. Downstream assertions are not covered by that approval.
- Never failure-to-skip, early-exit or allowed-failure conversions.
- Never repeat rejected native-interception or chmod/permission-failure approaches
  to safe-log validation. No FIFO/missing-path trick to recreate rejected probes.
- `../fmonitor` read-only evidence, `../shlz-ui` public exports only. Secrets and
  primary evidence stay outside repo. Do not delete five anonymous Docker volume
  candidates without ownership proof; age/dangling status is insufficient.
- Every behavior keeps spec→RED→independent Gate3→minimal GREEN→independent Gate5.
  Root authors code/tests; separately tasked agents review, never their own work.
- SQL adapters in AssignmentOrderOriginal have MariaDb-prefixed filenames.
  DDL remains InstallationProcess *SchemaMigration ownership. No new>=150-line
  production hotspot or baseline growth. One public original application mutator.

Local remote-tracking refs (not freshly fetched): integration
`75a642476224abe9ec99905777164b4279e743a7`; Quality Graph lineage
`f07548135fe930e7a8fb9bb97271c9f05a8ebfc1`. Revalidate remote before integration.

Docker test DB `fmonitor2-test-test-db-1` checked healthy at restart,
127.0.0.1:23306→3306, running41hours. All synthetic test databases/users and worker
children from completed tests were fixture-cleaned. PATH needs `/opt/homebrew/bin`
and `/Applications/Docker.app/Contents/Resources/bin`. Existing scripts own their
synthetic credentials; do not print real environment secrets.

## Completed since the preceding restart

Protected admission patch applied in060e880cdff41b8564a005fba95d6ed796c7772f.
Current protected E2E SHA256 is
8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b.
Admission/RBAC matrix passed. Latest FULL make verify remains clean060e880:
369.515seconds exit2, DB/E2E fail at unchanged downstream line157 action
«Сформировать распоряжение» plus its wrapper. No VERIFY_OK. See
protected-e2e-admission-integration-verification-2026-09-05.md.

Original-command corrections independently approved at bounded Gate5:
- shared safe-log owner73c3c22999e379d7b250d170ce9a2a262c9ca815;
- diagnostic isolation3583ef866be64765017b995f80d4d1c64b8db695;
- clock/ID dynamic ports738b9adf00e6e808e0a89f5899ce5db412daa7e6;
- metadata shape bca89b4853a7106fac3194d3724f27cba38b3b2f;
- resource lifecycle d7ed54d03041605200887c607ce6b3ce81f579be;
- full physical PDF history e9aca37cbb33f11a7cb63d13c1f06ca41bec35c3.

Read original-command-lifecycle-green-v1-2026-09-06.md and
original-pdf-history-green-v1-2026-09-06.md with their independent reviews.
PDF selected physical objects/trailers/ObjStm history are scanned even if freed,
overwritten or unreachable; opaque payloads remain opaque; latest graph and
budgets are separate. PDF run33/34 passed; broad historical whitespace diff
failed only immutable old review/patch formatting and was retained as failure.
Scoped implementation diff passed. This is not combined command approval.

Lifecycle pure storage ports may legitimately use opaque synthetic
`private-content-0001`; only the real MariaDB adapter requires digest-bound
`content-sha256-<hash>`. Never rewrite pure oracles to resemble filesystem names.
Root/revision ID grammar is ASCII0x21..0x7E excluding slash/backslash, NO SPACE.
No generic graph-backlink rejection in PDF: page Parent cycles can be legitimate.

## Current DATA-INTEGRITY implementation and exact evidence

Core implementation commit94a17bfef8175669a2265ebd03a33e77c143bfee adds:
- immutable validation of stored results/composition/complete lineage;
- normal step11 before IDs/finalize and distinct post-CAS classification;
- lazy authorized attempt clock, real epoch support and fresh attempt recovery;
- complete read-only MariaDB snapshots/backing validation and pre-SQL DTO checks;
- authoritative locked composition, append-only atomic writes, explicit commit
  acknowledgement uncertainty and checked native false returns;
- lazy fresh factory/new native one-shot reader, read-only RC request-key locking
  barrier before reliable miss, then RR full backing snapshot for FOUND;
- safe-log-first worker wiring, separate app/storage clocks, explicit recovery
  provider and production createRecoveryReady.

All751 new tests had prior independently approved RED/Gate3. Exact original old
fixture compatibility patch2ab9c675... was approved and applied8350038 before code.
See original-data-integrity-compatibility-patch-v1-2026-09-06.md and its review.

Full affected runner completed at clean94a17bf before/after,45commands42PASS3FAIL.
All751 new cases, support, architecture7, unit, lint, strict OpenSpec and scoped
diff passed. The three old worker failures remain real failures. Archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-data-integrity-green-vrynb89_`
Final evidence.json SHA256:
7616880e290a58c0fe5671c80998a498f087b9e89249eb39e2afd6c755c5283c.
complete=true, terminal session7272 exit1. Never approve a running evidence prefix.

751 cases breakdown: public values133, composition92, lineage106, DTOscalars134,
recovery50, attempt-clock50; real composition40, reads79, writes32, fresh25,
worker10. New membership extension below adds12, not included in751.

Gate5 v1 is CHANGES_REQUESTED, not approved:
- reviews/code/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001-application-v1.md
  db5ee49444b60cd70e581a413fffcf36fc85a28aac54a8f3c8e8a321cff8b4e4;
- reviews/code/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001-mariadb-core-v1.md
  5efd32e3e50e823290f48171afc1041ded8f7ca22d209d4f5c5c43784a9c1a36.
Fresh-wiring reviewer draft is preserved verbatim in operations
original-data-integrity-fresh-wiring-review-draft-2026-09-06.md,
SHA3e57d8a2e04a43034d406721f0bf941483c1477d7c8b555d934b42a1b33f4eec.
It is a draft with CHANGES_REQUESTED source finding; not a final approval.

## Corrections already reviewed/applied at56e494a

1. Negative lineage metadata could be empty while containsRevision(known target)
   returnedtrue/Throwable. Added separate12case RED:4controlsPASS8intendedFAIL,
   production still94a17bf. Archive original-data-negative-membership-red-dzzor054
   under the same external verification root, evidence47e68fcf..., log73cab0fc...
   TestSHA d662af9a70982782284285bcb392b98018bb5b757ee223d271b0fbdc2ebbb0c7.
   Independent Gate3 APPROVED in DATA-NEGATIVE-MEMBERSHIP-001-v1.md,
   SHA4e3e068935bead31500d366cff7df8f387008b107425ac4c346e730c604e23b8.
   Minimal source fix now applied: validate contains once for known current/target/
   queryRevision in both positive and negative states; no arbitrary probe.
   Focused12membership+106lineage+50attempt-clock pass after fix. Archive
   original-data-restart-check-uobo19dw pins changed source/logs; no new full-suite
   or Gate5 approval claimed.

2. Old worker tests used sys_get_temp_dir `/var/...` while canonical realpath is
   `/private/var/...` on this Mac. Strict password validator stays unchanged.
   Exact canonical-temp-base patch948aaeee779fda9d7575dd8510f2c26df41b52a1f1bb48fb2e32131c12b51f86
   is reviewed and APPLIED to lease_race, worker_post_finalize_negative and
   worker_transport. Patch stored as original-data-integrity-worker-canonical-path-v1-2026-09-06.patch.
   Gate3 DATA-WORKER-CANONICAL-PATH-001-v1.md APPROVED,
   SHA802e5efe46aa11ed58e0994e3009151c4dcdcc2c5319e497a1317702d377ae56.
   Candidate archive original-data-worker-canonical-patch-v1-d1uldlev:
   manifest e25cee6c31cf7b7c1483eed398a2e0da7e69ead0ab01e14aa362a5f8d3f2e130;
   evidence0fa79a94f8dd8c4ab5db7b640ed6c4214c38ea062efa082f89e531f48ad5e436.
   Exact current test after hashes are listed in the review. Every result oracle
   is unchanged. Lease and post-finalize tests pass in candidate. Transport now
   exposes the next genuine functional RED below. A failed earlier candidate
   preparation directory original-data-worker-canonical-patch-1vzov7yr is NOT
   authoritative and must not be mistaken for the reviewed candidate.

## Immediate next work after restart

A. Fix existing identical-correction race precedence, already authorized by the
   canonical patch Gate3 and parent normative concurrent replay contract.
   At AFTER_FINGERPRINT_MISS_BEFORE_CAS both A/B saw miss; A then commits. B's
   fresh step11 current read sees drift. Current Candidate::number immediately
   selects STALE instead of re-reading the identical fingerprint winner.
   Required B result: REPLAYED revision-0070, number7, date2026-09-01, exact old
   payload/request echo, no loser terminal/audit/event. Existing worker_transport
   line41 is unchanged independently reviewed RED. Before selecting stale,
   reread exact same fingerprint once: valid ACCEPTED winner→frozen replay with
   cleanup and noIDs/finalize; validmiss→stale; malformed/unavailable→persistence.
   Do not add extra reads on unchanged-current normal path. Candidate may return
   typed prepared number versus selected immutable replay to Service; preserve
   public seam and all port counts/cleanup. Do not weaken old race assertion.
   This production correction has NOT been implemented yet.

B. Technical TCP-only host clarification needs Gate1 before new transport RED.
   Current FreshConnection::host accepts exact nine-byte case-insensitive
   localhost, which mysqlnd treats as unix:// and ignores TCPport. Local php8.5.10
   exposes no MYSQLI_OPT_PROTOCOL. Official source:
   https://github.com/php/php-src/blob/PHP-8.5/ext/mysqlnd/mysqlnd_connection.c#L481-L535
   get_scheme uses length9 + strncasecmp; reject localhost, LOCALHOST, LocalHost.
   No host rewriting or new public config classifier. Shared worker/fresh host
   validation must reject before password contents or mysqli_init/real_connect.

   Root drafted, but NO Gate1 review requested/approved yet:
   DATA-INTEGRITY v0.7 SHA30f402ceb136cd72fcc97801a28c51c9186ce78e8ea5454bf0e98ee272814bd9;
   parent ORIGINAL-UPLOAD v72 SHA461577954b8f89f07900e1b3ebb217ceec3f981421d6420f51e79d9c4f5f18ee.
   All four OpenSpec artifacts updated, strict validate passed. v0.7 also makes
   A's existing concurrent-race winner reread explicit and defines transport
   sensor. Earlier v0.6 spec approval/Gate3 records remain immutable historical
   authority; do not infer v0.7 approval from them.

   Feasible independent reviewer-assessed sensor, NOT authored yet: task-owned
   Unix listener at a short canonical temp path; bounded PHPchild started with
   -d mysqli.default_socket=<owned path>, asserts ini_get exact value. Invoke
   public fresh factory with synthetic canonical0600 password and each localhost
   case variant; listener accepts and immediately closes without MySQL greeting,
   so no authentication bytes are requested. Current factory yields UNAVAILABLE
   plus acceptCount1; fixed host validation yields same typed UNAVAILABLE plus0.
   Separate direct-mysqli sensitivity child must yieldaccept1. Use stream_select,
   monotonic deadlines, termination/reaping and exact-identity finally cleanup.
   No sleeps, system /tmp/mysql.sock mutation, OSpermission failure, monkeypatch,
   private invocation, new production observer or real credentials. Source Gate5
   separately proves credential-ordering; network counter alone does not.
   This is external endpoint observation, not native-function interception.

C. Then run all affected tests on a clean exact corrected SHA, preserve final
   immutable logs and request fresh independent Gate5 v2 for all scoped areas.
   Reuse prior runner as template; after added test total commands changes.
   No full make verify/launch claim until all integration prerequisites really
   hold. Do not keep re-running the96second standalone DBsetup without new reason.

## Remaining full-goal work after this package

No combined Original-command Gate5 exists. Audit-only stream/storage failure
writer and generic diagnostics, maintenance/resource ownership and public
API declaration parity remain separate. Read original-public-declaration-parity-audit-2026-09-06.md,
original-command-pre-clock-terminal-audit-contract-2026-09-06.md and related audits.
Do not pretend enum-only or maintenance placeholders are implemented.

Owner product question sent when user returned: repeated denied attempts should
produce one record per attempt or one per request/reason; spec and unique key
currently diverge. No answer received in this session. Do not infer policy from
elapsed time or DBconstraint. This does not block independent technical work.
DATA reader requires original matching denial audit presence without choosing
an upper cardinality. Do not re-ask either1842Z owner approval.

Standalone identity registry engine b6f619f41e924c6ae2663d66e22de85cad30d6de is
scopedGate5 approved but not registered in canonical migration runner(1..12),
no version reserved, no allocator/selection writer. MariaDB11.4.7 forbids CHECK
on AUTO_INCREMENT; never reintroduce that rejected DDL.

Selection spec ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 v0.8 hash
5cb8a371a43356849b374356212689562a8ccba7db402bb05c46715b1df704b2
still Gate1 NOT READY. Both new_order/replace_pending required. No selector code.
OpenSpec canonicalize-assignment-order-selection-schema is planning-only;
read selection-schema-planning-review-2026-09-06.md and
selection-compatibility-next-package-review-2026-09-06.md.
Next sequence: disabled five selection tables +complete registry, registered
composition-source reader, new optional-render artifact owner (old physical
order FK incompatible), legacy+two direct signed-original writer disposition,
actual all-writer stop/no active connection/exact readiness artifact, preserving
reader compatibility and cutover. A marker alone never excludes a running old
writer. Then selection/application/HTTP wiring and launch golden path.

Final goal still requires full exact-SHA VERIFY_OK and CI, clean deployment and
restart/persistence/login, original-first public golden path, requirements audit
and zero launch blockers. Do not mark goal complete at a component approval.

## Restart mechanics

Known handles7272(full run),99562(canonical candidate),58537(worker),88271(fresh),
65050(writes),80316(reads),20734/65177(architecture) all returned terminal results.
No live child agent work remains. Three existing reviewer agents are completed;
if unavailable in new session, create separately tasked independent reviewers
under applicable AGENTS instructions, preserving reviewer/author independence.
Read and apply OpenSpec skills as needed; do not request owner permission for
already authorized technical review/fixes. Keep task5.9 open until corrected
GREEN+independent Gate5. Task1.40 is pending v0.7 Gate1; task4.9 pending added
transport RED/G3. The old session stops after committing this handoff.
