# OTIZ-SNAPSHOT-PUBLICATION-001 — independent Gate 5 review

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/otiz_review`
- Reviewed base: `origin/main` at `ad652aec264a75634f696d12fb7145ca9194c98d`
- Reviewed production commit: `31d7c09cc14cd6c871cb0a0042d61d5437c58204`
- Production commits: `12fd2e93` canonical schema, `31d7c09c` application owner and integration
- Verdict: **APPROVED**

## Findings and disposition

No blocking production finding remains.

The first review found three material defects. The application store duplicated
the role/permission SQL instead of using the agreed access policy. Publication
readiness accepted non-transactional legacy OTIZ tables and an inexact replay
unique key. Evidence-ledger readiness compared column names only, so incompatible
types, keys or engines could be admitted. These findings were corrected before
this verdict.

The local RBAC implementation now has one owner,
`IdentityAccess\MariaDbPilotAccessPolicy`; the previous PilotHttp name is a
compatibility alias, and `MariaDbSnapshotStore` delegates authorization to the
shared policy and `OTIZ_MANAGE` constant. Active-user and active-role semantics
remain unchanged. `app/Otiz` has no dependency on presentation or rapid-pilot.

`MariaDbSnapshotStore` owns and refuses nested transactions, establishes
`REPEATABLE READ` with a consistent snapshot, repeats authorization within the
transaction, and rolls back every `Throwable`. Native inputs use the same
`mysqli` connection. Header, objects, allocations, issues, totals, content hash,
receipt and the sole `draft_calculated` event commit together. Duplicate replay
losers roll back their partial transaction before reading the winner's receipt.
The actor-scoped operation fingerprint distinguishes a reused operation with a
different report date.

Receipt verification reconstructs the manifest from stored rows in schema and
specified row order, normalizes SQL values to string/null, excludes only mutable
acceptance fields, checks exact version, counts, digest and a non-pending hash,
and does not trust receipt bytes as content. Acceptance locks the snapshot first,
rejects absent/immutable/incomplete/blocking snapshots without mutation, then
writes accepted actor/time and one event in the same transaction. Legacy
accepted history remains readable without invented receipts.

The canonical v20/v21 migrations use exact readiness manifests. Every table is
required to be InnoDB with the expected ordered columns, types, nullability,
auto-increment attributes and exact named indexes. The receipt has exact primary
snapshot identity and exact unique `(actor_user_id, operation_id)`. Both evidence
decision tables retain exact operation uniqueness. Existing compatible populated
history is adopted unchanged; partial or drifted families fail closed before a
misleading receipt is created. The runtime performs readiness checks only: the
store checks v20 once and the OTIZ composition checks v21 once. Retained explicit
CLI ledger entrypoints keep readiness guards but perform no DDL.

The existing formula, native input reader and norms moved under `app/Otiz` with
thin legacy aliases. The old calculate, closure-read and acceptance SQL was
removed from `RapidPilotOtiz`; its POST routes now call the application owner.
The form retains one operation identity and report date in session storage until
a definite created response, enabling response-loss replay without changing the
existing styled UI.

The architecture checker now admits SQL only in named `MariaDb*` OTIZ adapters,
forbids reverse dependencies, and scans the three reachable legacy ledger paths
for runtime DDL despite their directory exclusion. The canonical migration
runner expectations and exact table inventories were advanced from v19 to v21;
migration-specific v19 assertions remain intact. No broad expectation or checker
baseline was weakened.

## Verification evidence

Independently reproduced on the canonical worktree:

```text
$ git diff --check origin/main...HEAD
PASS (no output)

$ php tests/Otiz/snapshot_publication_001_test.php
OTIZ_SNAPSHOT_PUBLICATION_OK

$ php tests/Otiz/snapshot_publication_http_001_test.php
OTIZ_SNAPSHOT_PUBLICATION_HTTP_OK

$ php tests/Otiz/runtime_schema_001_test.php
PASS canonical OTIZ v20/v21 schema preserves history and every retained runtime ensureSchema is readiness-only

$ python3 tools/architecture/check.py
ARCHITECTURE CHECK PASSED (7 rules)
```

The schema author additionally reproduced the full 19-assertion OTIZ workflow,
migrated-evidence decision ledger, native-only input verifier, production
migration runner, selection canonical registration, inspection-item complete,
and focused syntax/diff checks. The root reproduced the 77-test unit group and
premium formula verifier.

The reviewed headless browser smoke used a disposable fixture and a DB principal
with only SELECT/INSERT/UPDATE/DELETE. It performed real login and clicks through
prepare, deliberately lost the successful calculate response, reloaded with the
same operation identity/date, replayed, accepted, and downloaded XLSX. It observed
one native object, a 60,775,000-kopeck pool, one receipt, one publication event,
a 7,380-byte XLSX, and no console, page or unexpected network errors. Screenshots
showed the existing styled UI. The disposable DB account was removed.

Full CI is still pending and must be recorded separately. This Gate 5 approval
does not claim CI completion, deployment, archive readiness, A02/A03 resolution,
or production financial readiness.

## Supplemental CI-fixture review

The first CI run, PR 61 run `34279763684` at head `167cbaa9`, exposed a
verification-harness fixture defect: miniature repositories used by the
inventory and CI self-tests did not create the newly mandatory `tests/Otiz`
directory. The correction changes only three verifier tests. Both miniature
repository builders now create that directory; the inventory test additionally
proves that an unregistered OTIZ test fails closed and adds the three registered
OTIZ database suites to the exact historical membership expectation. Existing
members and digest-sensitive expectations remain present. No production source,
runtime behavior, category assignment, runner implementation, or failure policy
changed.

Independent review found the correction narrowly matches the concrete CI
failure and preserves sensitivity. Both focused Python suites pass, Python
compilation and `git diff --check` pass. Gate 5 remains **APPROVED** with these
supplemental verifier-only files:

```text
9961dcb36fefba605517a40d994480bd7b2791a5c966c4946fc3212d3a4c521b  tests/Verification/verification_inventory_001_test.py
d924887be0f088548a65445e2cb01d0f07c653d909b95353f091bd8e1b46b293  tests/Verification/verification_native_suites_001_test.py
de1da7bc9d1c66e60f4ad1adaeed8d55fcdfd34b931498e862001e22293a270e  tests/Verification/verification_ci_001_test.py
```

## Exact reviewed hashes

```text
d20a453e321786c407f3e7dc8c703df67a659286bdf1407a5876c436ba53aef0  specs/OTIZ-SNAPSHOT-PUBLICATION-001.md
f64c2a4b8a14440d97660af79a50b1f25fb490d5ddc9c917069fbfcb2fcefa8c  openspec/changes/atomic-otiz-snapshot-publication/proposal.md
01db632de6e55662f9c1c2692869bfcf95b2855f74380efb7ab82e61201bfdb5  openspec/changes/atomic-otiz-snapshot-publication/design.md
8daebd71ba3d1919fc495fe1ecc658c485e05360a899dff9171b4d35d4461ee2  openspec/changes/atomic-otiz-snapshot-publication/tasks.md
0bb7574af0cfd3642a019c1cd6ffe46842b27823bbd42cfb8c1f1316b401a8f0  openspec/changes/atomic-otiz-snapshot-publication/specs/otiz/snapshot-publication/spec.md
c16b9255c1e29631c4df63c2166bad110256b004fc847778b4fe99775a74e9f6  app/Otiz/MariaDbNativePremiumInputs.php
af9c5f997b96a51e38d8dd114ff0a658429286c9a6f9a3306147ffb762610095  app/Otiz/MariaDbSnapshotBuilder.php
33bc30e880b0b7c3328aef312c3dae036e7361d170e77ae595f413827159353d  app/Otiz/MariaDbSnapshotPublicationRecords.php
4121cc0860d9b1a487187ce38add2c9b900c8c53b859621018947f15314f0e49  app/Otiz/MariaDbSnapshotStore.php
f5a6293473103fa4252bf2e066af3689037476778eabf7692b869591daa6c044  app/Otiz/NativePremiumNorms.php
b9638dea0fc17ab71bc444fe5e379ce10bba21f2e1958c037877631be1c4eea1  app/Otiz/PremiumCalculation.php
400349fd55e932994ce2d77ca8a1a7248995209d4b10a9b12161ed7382b791f9  app/Otiz/SnapshotPublication.php
1b2cb10a2da70629d7651f8ce3754556949c98fb62633d3c4a1c1de1f35fa1f5  app/IdentityAccess/MariaDbPilotAccessPolicy.php
bb398151accf6030641ce5ec90dbfbf2863203e9581cf196dafcefcb870e2999  app/PilotHttp/AccessPolicy.php
5c3cf8da9bc3d5820dbf7625337e6545bf82462d2d681e63388cbec8c9eb4854  app/InstallationProcess/MariaDbOtizSchemaManifest.php
6942bfc7a38601a69e77ef0a1998a3d9c30c8b16f91a16e6a5942c3344c5f2e4  app/InstallationProcess/OtizPublicationSchemaMigration.php
4406d25c50f37ffc6843efacb7e9412f81b5ad2af969b115e89a031b9f7654ec  app/InstallationProcess/OtizEvidenceSchemaMigration.php
493207fc36965ea34b54cc8a192c4a716bf1189bbd5878f6e640b231f77eca53  app/InstallationProcess/ProductionPilotMigrationCatalogue.php
57abb076de184c39708d017dddc883556d9f30e94bf212d53d2d6deebdbca7aa  rapid-pilot/Otiz.php
f4479b9a00b376d0b652125d01ded9a5c95213387956ac291fe25583d60847d2  rapid-pilot/otiz.js
1cc1fd3991982185df26d2873e9aa3f2f82b3df3b0c137ff3735cb331285b3df  tools/architecture/check.py
ea53e74d242fc97983d772e01c83b2781b182e61cee83458e4a905d22ed7f6ae  tests/Otiz/runtime_schema_001_test.php
609d5ee1c27820f0e096efd70d65be3c974ced8c2383bb4d1fd991cf02317b96  tests/Otiz/snapshot_publication_001_test.php
5015d9c7909c4e0766c43eeecfe2852a21eda85beb17c142be8f65488353bc48  tests/Otiz/snapshot_publication_http_001_test.php
4b4b92e3377f945049c4f81c9be152cc0e5eb49bc4f92cc13835ec152059ae61  tests/Support/OtizPublicationWorker.php
1a92c462e7f8d2a9c2258b83c2771b27699081c040bf49a593c92d58dad86450  tests/Otiz/snapshot_publication_browser_001_test.mjs
d3b2369f21b5a4aea02a19be575c0c6ca93ed157d24aa00c79b8dc5aec349884  tests/Support/OtizBrowserFixture.php
```

## Supplemental canonical-frontier compatibility review

The remaining first-run CI failures were stale v19 fixtures after the production
catalogue acquired v20/v21. Eight files contain the bounded correction.
`PilotDemoDatabase` now expects the real v21 result and inventories all 13 OTIZ
tables. The demo launcher writes and validates v21 ready markers. Its existing
public bootstrap test asserts the exact sorted 62-table catalogue and literal
marker; before the launcher fix this new assertion produced the valid RED
expected 21, actual 19.

The real canonical-compatibility harness advances its no-op result to v21, and
the quality-graph toy repository creates the mandatory `tests/Otiz` directory.
The workforce fixture adds the same 13 exact table names; inspection evidence
corrects only its contradictory terminal scalar; installation completion avoids
recreating an OTIZ family already produced by canonical migration while retaining
literal creation/manifest checks in isolated readiness-drift scenarios.

These changes preserve behavioral and fail-closed assertions. They add no
runtime DDL, command, domain fact or permission. Demo bootstrap, canonical
compatibility, quality graph and all three schema fixtures pass; syntax and
`git diff --check` pass. Gate 5 remains **APPROVED**:

```text
ef452ec1bf1c74b11d87150cfba7f6e13d1f456cce20000ca3ed60331b50144c  app/demo/PilotDemoDatabase.php
b47e9d89498aae81c5b764c9906082ae075098dc0ed03c3818a0491160baec2b  bin/fmonitor2-pilot-demo.php
adbe3dcd6ed4e2d1dfeafacf1f83d4555f7a65b25e23a3dcfe39ff8e70f603f4  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
e7394a6f98cbd488a49ebfe1b0db46e970145438ba6c2aa2525bd1645605ea3a  tests/InstallationProcess/inspection_evidence_schema_001_test.php
81fec07b511ec82e4fae8176d529fb04a4e24331e0b111ac0c03e045a1490672  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
45e4d870e4135f5036996b93a3bf118b400bb00f398b85ed2cbe85208cbacdcb  tests/InstallationProcess/workforce_canonical_runner_001_test.php
ec263e3a304074cfb07786faa95e441d25711fc3b0cc6fb76f90d9c062404279  tests/Verification/harness_otiz_canonical_compat_001_test.php
4c8d6afbe437dfede307caebeeb9dcf33fd53ab01bd7b7c3d0fb0ab9ff677433  tests/Verification/quality_graph_ci_setup_001_test.php
```

## Supplemental namespace-qualification review

CI run `34281317284` passed fast checks and exposed one remaining governance
violation in the PilotHttp compatibility alias: the global `dirname` and
`class_alias` calls were unqualified inside a namespaced source tree. Prefixing
both calls with `\` is the complete change. It does not alter class identity,
autoload behavior, authorization semantics or production state.

The focused global-call test passes, the complete local governance category
passes all nine members with zero failures, and `git diff --check` passes. Gate 5
remains **APPROVED** for the corrected compatibility alias:

```text
a26141039f4b4ff0987b984890121c6fa2d27ad670c32a9641a81f5da73fea1c  app/PilotHttp/AccessPolicy.php
```

## Supplemental recurrence-prevention review

The qualification miss recurred because the lower-level structural checker did
not own the existing HTTP global-call convention, local review omitted the
separate governance test, and truncated CI-log triage hid the second occurrence.
The prevention change addresses those exact causes without duplicating its
oracle. `make architecture-check` now runs the existing
`pilot_http_auth_001_global_calls_test.php` before the structural checker.
`AGENTS.md` requires that focused test for every `app/PilotHttp/*.php` change and
requires a complete failed-job/`REGRESSION_FAILURE` inventory before excerpts.
The guardrail documentation states the same composite gate and accurately
retains the lower-level checker's narrower meaning.

A temporary unqualified global call in an owned PilotHttp probe made the new
composite gate fail; the probe was removed in `finally`. The clean composite
gate then reported the HTTP qualification PASS followed by all seven
architecture rules PASS. The full local governance category also passed all
nine members. This is an enforcement change only; it adds no runtime behavior
or second token scanner. Gate 5 remains **APPROVED**:

```text
36f0e005c966872e3932aea0dbab2a66c5e4755cc3cee49b13b8b63a572937c0  Makefile
00c36ad2c1bc6e9d20e0267262bf07bc283dd5f269296bcba7db6ac38e7faeb3  AGENTS.md
f749258de05ef9af008ed1977d74cfe7aed1a616d3f93d6cb7b2f38b99871569  docs/architecture/guardrails.md
a26141039f4b4ff0987b984890121c6fa2d27ad670c32a9641a81f5da73fea1c  app/PilotHttp/AccessPolicy.php
```
