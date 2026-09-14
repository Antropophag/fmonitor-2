# №66 — возобновление 2026-09-14

Owner request: «Реализуй №66». Root owns scope/spec/tests; separate gpt-5.6-sol/low executor implements and independent reviewers decide Gates 3/5. Autonomous spec/test delegation was not authorized. Read-only investigation: root and inspect_existing_66 (gpt-5.6-sol/low); no implementation or gate approval performed.

Checkout: `/Users/antropophag/code/fmonitor-2-issue66`, branch `codex/issue-66-overdue`, base `fda41a50605146cba8c34a7011e33325dde52dbd` (origin/main at preparation). Existing planning/history remains at `/Users/antropophag/code/fmonitor-2-otiz-excel-20260909`, branch `codex/otiz-excel-66`, commit `b71cc9a3`.

## Gate 1 decisions pending

The prior `OTIZ-EXCEL-OVERDUE-CERTIFICATES-001` candidate explicitly leaves two material decisions unresolved. Asked owner again during this assignment; no answer recorded yet:

1. Original Excel T capture: current raw `plan_finish_date` captured with provenance at each publication, immutable upstream capture, or separate FKR confirmation. Adjusted `workdateendadjusted` is Excel V and cannot substitute for T.
2. Recurrence without new progress: fund 100 RUB, progress 100%, paid 90 RUB, Kss 0.9 yields 9 RUB under the requested cumulative Excel formula, versus no new amount under prior interval semantics. The legacy implementation yields zero but is not an owner decision.

Global EU2 waiver remains outside issue66; it is not a blocker for this assignment.

## Required reconciliation before RED

- Canonical v24 is already occupied by shipped OTIZ settlement. Allocate the next current frontier for additive certificate/entitlement schema; do not reuse the old plan’s v24 reservation.
- Evolve existing `OtizSettlement` / `MariaDbOtizSettlement` application seam, reusing authorization, receipts, object locks and signed closure history. Do not introduce a competing payment writer. Current budgeting combines paid and holds and lacks entitlement supersession.
- Use production Yii2 controllers/composition and existing snapshot publication/history seams. Old rapid-pilot UI/payment migration tasks are stale.
- Consume effective completion root/correction evidence. Native premium inputs still read deadline/PTO from registered order snapshots and require replacement with agreed operands.
- Certificate owner does not yet exist; reuse PDF/storage protocol patterns while keeping independent certificate facts.

## Next step

Record owner decisions, reconcile the existing OpenSpec and normative contract with current main, prepare the root verification package for the complete acceptance matrix, then author RED and dispatch independent Gate 3. No tests, implementation, commit, PR, full CI or deployment claimed. Local full make test/verify remains prohibited; use bounded local checks and one exact-source full GitHub CI after review.

## Owner decision — original deadline capture, 2026-09-14

Supersedes pending decision 1 above. After confirming the retained Excel research identifies T as planned completion (`plan_finish_date`), owner answered the capture-timing question: «На момент выполнения расчета».

Each new calculation captures the current known raw `plan_finish_date` at calculation execution time, preserving its exact value and source locator/hash in that calculation snapshot. Later source changes do not rewrite existing snapshots. A valid current certificate deadline remains the approved override; adjusted Excel V (`workdateendadjusted`) is not a fallback. No separate FKR confirmation or earlier assignment-time freeze is required for the original deadline.

Decision 2 (recurring payment without new progress: 9 RUB versus 0) remains unanswered. This timing decision does not resolve it or approve tests/implementation.

## Owner decision — recurring Excel calculation, 2026-09-14

Owner confirmed: «Сохраняем поведение ексель». Only confirmed payments enter paid-before; draft calculations never count as payments. With fund100/progress100%/Kss0.9, confirmed paid90 gives pool9; after confirming those9, a new otherwise unchanged calculation gives0.90. Preserve this cumulative recurrence instead of imposing NO_NEW_AMOUNT from unchanged progress. This supersedes pending decision2 above; both product blockers are resolved.

## Implementation checkpoint

Pure `PremiumCalculationV2` implemented by /root/implement_calc (sol/low), root-owned spec/tests, independent /root/review_calc Gates3/5 APPROVED. Seven test groups and four generated focused commands GREEN on retained candidate863674f8. Reviews link exact snapshots/evidence. Two Gate3 returns addressed validation/trace/multibyte coverage; all previous RED runs retained. No full local suite or CI run. Certificate application/schema/HTTP tests remain a work-in-progress RED candidate, no Gate3 approval or implementation yet. Both product decisions resolved; no owner questions pending.

## Continuation checkpoint — 2026-09-14 19:41 UTC

Owner's «А чего встал?» resumes the original implementation, without new product questions. Root continues scope/spec/test authorship; separate sol/low implementers and independent reviewers remain mandatory. Work stays in `/Users/antropophag/code/fmonitor-2-issue66`; concurrent feedback checkout is untouched. No push, PR, full CI or deployment yet.

Pure calculator validation extraction is independently Gate5 APPROVED: reviewer package `20260914T193035Z-53029fe020`, exact GREEN record `1789414228414116000-8b585c04991c4dfcbb41533c9ecce74c`; public contract/tests unchanged. Root added publication/payment core, race, HTTP and native mobile browser tests; those are incomplete as an integration candidate until the old v1 fixture adaptations and review are finished. Publication production remains v1.

Certificate initial application/schema/recovery GREEN records were followed by HTTP fixture correction (fresh server bootstrap instead of replacing an active router); HTTP GREEN `1789413976265040000-b4c9932edc354554aa3d72d1a7fd6a60`. Independent Gate5 returned six concrete findings, retained in `reviews/code/DEADLINE-TRANSFER-CERTIFICATE-001.md`. Root authored witnesses for all six; correction Gate3 APPROVED at package `20260914T193422Z-54e7660ebc` with three exact INTENDED_RED records. Separate certificate executor is correcting production. Root additionally extended recovery to run the actual pre-v25 tool from commit79b3a4ce against a v25 DB/bundle, and is updating current-frontier test inventories; these later root deltas still require review. Historical V22/V23/V24 recovery profiles stay unchanged.

Native input Gate3 APPROVED. Separate input executor made the full test pass, then switched certificate evidence to the public currentEvidence owner and is formatting/extracting readable helpers. Root corrected two fixture setup errors after review: a missing provenance-copy source was replaced with a complete synthetic decoy record, and second/third public selections send expectedSelectionRevision1/2. Native cases without migration provenance are explicitly admitted; classified cases require native_candidate. These test/spec deltas require final independent review. No native final exact GREEN/Gate5 yet.

Final freeze must bind all focused GREEN to one exact source. Earlier evidence remains historical when another agent changed unrelated executable files; harness rejections are not approvals. No local full make test/verify; full matrix will run once in exact-source GitHub CI after independent approvals and focused checks. All raw logs/packages remain under the external delivery-harness directory.

## Owner scope correction — 2026-09-14 19:49 UTC

Owner explicitly objected to scope creep and asked whether work was maintaining legacy rapid-pilot compatibility. Root acknowledged the error and withdrew that work: app/demo and rapid-pilot production edits were removed; root changes to pilot_demo_bootstrap, pilot_e2e_flow and pilot_http_auth were removed. The unused PublishedSettlementFixture and unreviewed entitlement/race/HTTP expansion drafts were moved to `~/.local/share/fmonitor-2/issue66-scope-trim-20260914` for history. New same-version draft supersession, entitlement events and reversal-lifecycle redesign are not part of66. They were never implemented.

Revised publication contract uses existing owners/lifecycle, wires V2, paid-only evidence, cumulative target, exact source-bound trace/allocation and A02 stale-old-formula refusal. Current native browser is the product acceptance; certificate UI derives technical provenance instead of asking the user for sourceLabel/sourceLocator. No new product questions. Native/schema current integrity checks remain; no promise to support old pilot flows.

Certificate four checks and native input test all GREEN on source719b1c81/executable612923bd. Certificate Gate5 correction review pending frozen package194550. Native Gate5 found one concrete provenance join defect; root added sensitive RED and correction Gate3 package194926. Revised publication Gate3 package194815 contains two exact intended REDs. Completion/PR/CI still pending; do not report №66 complete.

## 20:03 UTC — bounded integration checkpoint

Owner scope correction remains controlling: no rapid-pilot compatibility and no new draft/payment lifecycle. Publication Gate3 APPROVED after final stale-accept HTTP assertion; separate executor implement_inputs owns production integration. Certificate/native final approvals retained. Merged upstream b2907355 (feedback migration25), preserving all WIP in stash and external review snapshots. Certificate is now migration26, upstream recovery profiles remain immutable. Root adjusts only current schema expectations/inventories; executor implement_certificate authored production renumber. Direct certificate schema test GREEN after baseline25→26 update. Final exact capture/review/CI pending.

## 20:18 UTC — final focused source

Production executor implement_inputs finished bounded publication, calculation evidence, Yii/XLSX and derived certificate upload provenance. Root authored existing current-formula fixture updates; historical read fixtures remain V1. No production files in app/demo or rapid-pilot changed. Formatting churn removed and cohesive evidence/persistence helpers extracted to satisfy existing architecture limits without baseline changes.

Frozen candidate76539fec0ff2f9314a33e437cc5827abcefaa8fc65ea331665524daad8e24ecd, executable011b284d4a8eb03ee4a5ada7663c104869c848fd5ac4d11e043c024a83f9a541. Full source package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T201555Z-63a287a5ba/package.json`. Eight focused acceptance checks GREEN; records under external delivery-harness/records:

- Calculator1789416957048138000-5a3ec6f6a8d04718aae5abcc820d4fe1.json
- Native inputs1789416957853307000-79e5f9d6f4164441ab2427bd503cd3df.json
- Publication1789416959028583000-dfa30c9b80ab46009317e75a6a83a4c0.json
- Native browser/XLSX1789416960237496000-293234e6b1044a4cb8b92d8ed8eedaf9.json
- Certificate application1789416961399559000-aab5b69a7a3e40de8385a7bc1014b494.json
- Certificate schema1789416962568777000-0ac88614cacb4873a1e539c0017f9a7a.json
- Certificate HTTP1789416963726836000-b5d69b5b6c94431da2e6fcccadd06f69.json
- Certificate actual recovery1789416964884520000-b0737c44e6c043a8a209340536542b28.json
- make architecture-check1789416985558315000-f5c5e2648e364e408b8816151743d829.json

Existing snapshot publication, settlement owner/concurrency, Yii commands/HTTP settlement, schema frontier/settlement schema and clean-stand provisioning also passed bounded direct checks after relevant fixture changes. No full local suite. An earlier concurrent-source recovery run returned exit0/PASS but harnessUNKNOWN; it is not substituted for the exact final GREEN above. Earlier architecture hotspot failures were corrected by extraction, not waived. Publication final Gate5 is pending; schema26 final independent verdict APPROVED, preserving prior review history. CI/PR/deployment remain pending/UNKNOWN until separately evidenced.

## 20:21 UTC — independent implementation review complete

Publication Gate5 APPROVED by review_calc, schema26 Gate3/Gate5 APPROVED by review_certificate. Native inputs and calculator retained prior independent approvals. Root restored final frozen snapshot outside checkout and compared every tracked/untracked artifact byte and executable mode. All production/spec/test bytes match reviewed76539fec; only this delivery record, OpenSpec tasks and three review records differ. Harness treats this delivery-record update as part of executable_digest and rejects rebinding old evidence to the new documentation digest; original exact GREEN remains attached to reviewed source. No repeated local suite for that tooling classification. Final committed candidate receives the required exact-source CI.
