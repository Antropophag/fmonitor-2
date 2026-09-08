# Autonomous checkpoint — 2026-09-07 05:35 UTC / 08:35 МСК

Goal ACTIVE, exact objective unchanged, no budget. Previous goal turn was concrete
progress; this turn also delivered production code/tests/reviews and new evidence.
No complete/blocked claim. Start HEAD58203f3945d00c17ca1c96d5ec446aa40d618d86 was clean;
closing HEAD follows commit in answer. Branch codex/remove-pilot-work-navigation-v2.

Read previous comprehensive checkpoint:
`docs/operations/autonomous-reference-users-handoff-2026-09-07-0425Z.md`.
All its restrictions/approvals/preview preservation remain binding; do not redo them.

## Completed this turn

Change `read-assignment-order-original-history-download`:6/6tasks complete, not
archived. Spec ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001 v0.1 final SHA
ca1a153bafdf9a08f0d74e2219a3b90c59a14f24da9a56b0bf34f727f100fd11.
Gate1initial/v2 and Gate3 APPROVED; tests/REDcfd5a27. Production exact source
 eec882f274902c3d4842aa73d4665411b0a855aa, Gate5 APPROVED:
reviews/code/ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001.md.
No code findings outstanding. Docs/evidence closing3d2b5f8 before this handoff.

Public trusted in-process port, namespace AssignmentOrderOriginal:
- AssignmentOrderOriginalHistoryReaderFactory::create(mysqli,privateRoot,prefix='');
- readHistory(objectId,orderId,afterRevisionNumber=0,limit=50), exact13field page;
- prepareDownload(objectId,orderId,revisionId), exact9field metadata containing
  exact9field revision +immutable fully checked bytes;
- shared status found/not_found/invalid_argument/unavailable, values iff found;
- prefix0..25, syntactic absolute root; factory no SQL/FS/env/connection;
- idle-only owned consistent readonly DBsnapshot, caller TX never owned/closed;
- registered selected source and full StoredReader lineage/backing reused through
  MariaDbOriginalApplicationReferenceSource; no diagnostic EvidenceReader calls;
- canonical root owner0700/0750, PDF/digestlock regular/no symlink/singlelink,
  exact0600 including specialbits exclusion, current POSIX UID and lstat/fstat
  coherence; existing readonly shared nonblocking lock, no create/chmod/repair;
- exact<=20MiB/EOF/hash checked before success, all locks/descriptors released;
  prepared value keeps no resources and remains immutable after corrections;
- metadata does not depend on privateRoot availability; missing file means download
  unavailable while healthy history remains found.
No actor grant, HTTP endpoint, schema/application/opening or migration version.

Native18cases (9×prefix0/25) GREEN, including two distinct327byte PDFs, both20MiB
accepted cases, old/current buffers, pagination/newcorrection, two accepted roots'
bidirectional foreign-revision rejection, invalid/config/DB/TX/corrupt backing,
root/file/lock mode and aliases, missingroot/lock, two bounded native digest lease
workers, exact DBrows/DDL/privatefile metadata and stream-count preservation.
RED18setup/cleanup/intended failures retained; final tests amended after independent
review to include root0750positive, unsafe-but-readable0755/0644 validation negatives,
lock aliases and explicit idle checks. These are not privilege/OS-denial probes.
Test/helper/worker hashes in Gate3 and red ops doc. All test resources cleaned.

Four regressions PASS: original application reference14, original lineage106,
selected original binding2constructors, original HTTPflow. Architecture7 PASS,
lint11new production+3test/helper files PASS, diff PASS. Native test is discovered
by canonical db runner. No fullmakeverify repeated here: latest full18916ae predates
clone repair/history API and failed only known protected bootstrap/E2E. No VERIFY_OK.

Details:
- docs/operations/original-history-download-red-2026-09-07.md
- docs/operations/original-history-download-green-2026-09-07.md
- external /Users/antropophag/.local/state/fmonitor2-verification/original-history-20260907
  red-v4.log,green-final.log,green-manifest.json,regression0..3,architecture.log.
All execution handles terminal; review agents completed and reusable.

## New workforce evidence / next safe independent work

Read-only characterization found hourly-bitrix-workforce.php still writes four
workforce tables directly, without native sync application ownership. Script SHA
0ce1cb3b62418d64aeb05f8bba8a52cc6605011e75a1b5768df1f8f218c6e212.
PB-14 appended to docs/operations/pilot-behavior-inventory.md. Detailed evidence:
`docs/operations/workforce-sync-characterization-2026-09-07.md`.

BITRIX-WORKFORCE-HISTORY-001 is explicitly EPIC/NOT EXECUTABLE/SUPERSEDED FOR
GATE2. Schema/canonical runner approvals do NOT authorize whole sync implementation.
Existing epic lists delivery, normalization, publication, catalog-read, freshness,
employment-period and scheduler followups; actual native implementations not found.
No new workforce OpenSpec change/spec/tests/code created yet.

Important inconsistency: old script sets new employed_from to day of synchronization,
ignoring source employment start. Contract requires unknown→null. Native selection
InstallerSnapshot/Eligibility/PortalQuery currently require valid non-null date
(spec ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 ~319/357). Do not fabricate dates or
silently weaken these contracts. Historical August28 research saw no employment
start dates, but no live data/probe was performed now; don't claim today's counts.

Only public official Bitrix documentation was browsed. user.get supports scalar
sort/order, select array, fixed50page/start; default ordering is IDascending.
Do not copy old epic order[ID]=ASC. Missing inaccessible/nonexistent selected fields
may be silently omitted by API. user_basic table includes needed system fields
EMAIL/UF_XING/UF_EMPLOYMENT_DATE/UF_DEPARTMENT; separate custom-field scope is not
required merely because these system names beginUF_. Links recorded in ops doc.
This is documentation evidence, not actual portal/credential/payload verification.

Next safe work can decompose executable workforce delivery/normalization/publication
under OpenSpec+gates using official docs and redacted existing evidence, while
corresponding business policy remains pending. Do not implement broad non-executable
epic or mistake fictional happy-path tests for live integration readiness. Real
Bitrix run and production imports remain forbidden this session.

## Two REQUIRED owner answers are pending

1. Earlier application-date question: after composition already applied but before
   opening, if original document date is corrected, allow explicit reapplication
   preserving earlier application fact or freeze applied date? Parent apply change
   still ONLY proposal+NEEDS_GRILL, no schema/version16/code/spec. Do not decide
   uniqueness/correction semantics before answer. Prospective new-order effective
   date already approved as DOCUMENT DATE; do not ask that again.
2. New async question this turn: allow new assignment based on confirmed current
   status of a FULL workforce snapshot when Bitrix omits employment start, keeping
   date unknown, or require confirmed start date? No answer yet. Do not treat goal
   continuation, elapsed time or selected default in UI as approval. Freshness age
   threshold also isn't defined by existing epic; hourly schedule isn't one.

History/download API and application-reference API are now available prerequisites.
Parent HTTP all-role scope still needs native actual applied-engineer authority;
never use legacy responsstroicontrol. Application/opening/nativegoldenpath remain.

## Unchanged operational limits and authoritative state

Preview8092 Users200 recovery remains complete: old image1eba93cf with immutable
imageID8fa07372e5076ca5d488a8c8cde42257d832c9fb7a199e0813e95d78a829ec8b,
readonly runtime-only startup override and old healthcheck mount. No newsource
features deployed. Do not remove override/re-run oldbootstrap, which changes auth
metadata. Existing51tables/3308sessions preservation evidence in prior handoff.
Credentials/cookies/raw snapshots stay external; NEVER print preview.env.

Protected E2E SHA unchanged8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b.
Latest full source18916aef904e37ddfbcf8afd9adb6abcdf642654 exit2, failed db/e2e only
pilot_demo_bootstrap and pilot_e2e_flow legacy registration flow. Do not weaken
protected tests or restore obsolete writers just to green. No fullVERIFY/launch claim.

Remote last read ~04:06UTC unchanged: main2bff0a0e6baaab61679321001c57cbc916609295,
branch75a642476224abe9ec99905777164b4279e743a7, PR10 OPEN/DRAFT head3ae214f75b898d171c68bb127dec10f17e03117a,
no checks. No remote mutations; never modify/merge PR10, no QG/bootstrapCI publication
before literal fullVERIFY_OK. No production imports/Bitrix run this session.

Use AGENTS/product/pilot/process, public shlz-ui (owner overrode Windows source
search), ../fmonitor read-only, primaryevidence outside repo, no boundary baseline
ratchet or new>=150line production hotspots. TestDB127.0.0.1:23306 root/
fmonitor2_test_root_local, PATH Homebrew/Docker. Goal remains active without budget.
