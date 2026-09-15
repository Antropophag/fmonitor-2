# YII2-CONTROL-ENGINEER-ASSIGNMENT-001 — independent Gate 5 review

- Reviewer: independent Codex reviewer `/root/issue52_gate5`; authored neither specification/tests nor production implementation
- Review package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T082352Z-80bdabc7f5/package.json`
- Base commit: `0a286f3eccb5230d51e787baeeaea0acf7beb894`
- Candidate source: `6a62dc7c60c55c74212c3372d0aa3435f51b5404204b7f6ded44a6b599a1022d`
- Executable source: `2e2ce392100e0eddcb8457412c65e9f727e71f7c0cd1986bcc69798d3e920698`
- Snapshot patch SHA-256: `1d22a54afb31ad00dd57cb7390bd675be21abc708ce15052fe7fb71bedb9ec83`
- Verification plan SHA-256: `2ebe2018b938221c8e41390191e82910025fe0fb0bc39c4d9fad03aecc18717a`
- Review date: 2026-09-15
- Verdict: **CHANGES_REQUESTED**

## Complete findings

1. **CRITICAL — preparation can persist a stale engineer because authority is resolved outside the selection owner's transaction.** The accepted contract requires the selection owner to reread current assignment under the case lock and reject a mismatch with the revision shown by the form (`specs/YII2-CONTROL-ENGINEER-ASSIGNMENT-001.md:46`; `openspec/changes/standalone-control-engineer-assignment/design.md:40-44,58-59`). Instead, `app/YiiRuntime/Controllers/SelectionController.php:57-63` reads current assignment before entering `selectAssignmentOrderComposition()`, compares the form revision only at that time, and passes the resulting engineer ID into the unchanged existing command. The existing application path still treats that ID as its engineer input (`app/AssignmentOrderComposition/SelectionEligibility.php:15,24-28`) and has no standalone-assignment revision. An assignment replacement committed after controller line 60 and before the selection case lock therefore lets the new document snapshot the old engineer. Omitting `expectedControlEngineerAssignmentRevision` also bypasses even the controller comparison because the field is optional at `SelectionForm.php:33` and `SelectionController.php:60`. Move current-assignment resolution and shown-revision validation into the existing native selection application owner under its case lock; HTTP preparation must submit the revision, while only explicitly supported native callers may omit it and resolve current state in-owner. Add an overlapping replacement/composition test and an HTTP omitted-revision test.

2. **HIGH — the #40 checklist seam bypasses the authoritative reader and restores forbidden fallback sources.** `app/InspectionEvidence/MariaDbYiiChecklistRead.php:16-17,64` directly selects the last standalone row without schema or lineage validation and, when none exists, calls `selectedEngineer()`. That helper reads the latest selection and then `engineer()` falls through application, process event, and `fm_maintable.responsstroicontrol` (`MariaDbYiiChecklistRead.php:105-110`). The contract permits bootstrap only from the latest coherent confirmed native application and explicitly forbids selection, process-event, legacy-column, and arbitrary fallbacks (`specs/YII2-CONTROL-ENGINEER-ASSIGNMENT-001.md:14-20,66`; design lines 46-49). It also asks H to switch current reads to the new authoritative owner, not duplicate a weaker query. Use `MariaDbControlEngineerAssignmentReader` (or a thin batch adapter preserving exactly its outcomes) for `access()` and `queue()`, fail closed on `unavailable`, and cover no-standalone bootstrap plus corrupt standalone/no-legacy-fallback in the #40 HTTP seam.

3. **HIGH — standalone bootstrap lineage is accepted without validating the referenced application.** For a first standalone row, `MariaDbControlEngineerAssignmentReader.php:12` checks only that `bootstrap_application_id` and `previous_engineer_user_id` are non-null. It never verifies that the referenced application belongs to the same case/object, is the coherent bootstrap application, or snapshots the recorded previous engineer. Because the FK proves existence only, changing the reference to an unrelated application's ID still returns `found/standalone`. This violates the required case/object/previous-engineer/bootstrap lineage and fail-closed corruption outcome (`specs/YII2-CONTROL-ENGINEER-ASSIGNMENT-001.md:14-18,30-32`). Validate the first row's bootstrap application with the same native application integrity rules and exact case/object/engineer linkage; add wrong-application and wrong-previous-engineer corruption cases.

4. **HIGH — schema drift is not fail-closed at the read seam, and the mutation compatibility check ignores required keys/constraints.** The reader at `MariaDbControlEngineerAssignmentReader.php:8-13` never calls a schema compatibility owner; any table exposing the queried columns is accepted. The mutation check at `ControlEngineerAssignmentDefinitionSchemaMigration.php:16` compares column names only, so a table missing the unique case/sequence or request keys, self/application foreign keys, or sequence check is considered compatible. That can remove the database guarantees underlying idempotency, concurrency, and lineage, contrary to the reader's `unavailable` on schema corruption and the explicit canonical indexes/FKs/checks contract (`specs/YII2-CONTROL-ENGINEER-ASSIGNMENT-001.md:14,28,34,70`). Extend compatibility validation to the exact minimal schema guarantees and invoke it on reads; add a focused drift case proving no read/write proceeds.

## Standards axis

Findings 1–2 are hard violations of the repository rule that state changes have one public application owner and consumers use the authoritative fact seam (`AGENTS.md`, “Continuing rules”), not controller-owned authority or duplicate SQL. Findings 2 and 4 also violate the documented fail-closed/readiness policy. As maintainability judgments, the dense one-line owner/reader/migration methods make transactional and lineage review unusually difficult, but no formatting-only correction is requested; the four behavioral defects above are sufficient and should be corrected minimally without architecture expansion.

## Spec axis and evidence assessment

A–G happy-path outcomes, HTTP authorization, ordinary replay, two assignment writes from one expected revision, immutable application bytes, reference-only UI, and the #38 installer projection are exercised by the four source-bound GREEN records in the package. Those tests do not overlap composition with assignment mutation, omit the form assignment revision, route corrupt facts through #40, change bootstrap application lineage, or drift indexes/constraints, so GREEN does not detect the findings.

Evidence inspected:

- owner/history: `1789460237440895000-a9c65960db464f27aada85d9697afa3f.json`
- #40 preopening/opening: `1789460268069564000-7e8bd300b9e44d5689c83a675dd31134.json`
- Yii card/preparation/documents/concurrency: `1789460296607310000-de133026eb4d4279b19f6e31a33768b7.json`
- #38 installer directory: `1789460328538113000-5828b0b941ab4a09a19645a868919fb0.json`

All four records are GREEN for candidate source `6a62dc7c60c55c74212c3372d0aa3435f51b5404204b7f6ded44a6b599a1022d` and executable source `2e2ce392100e0eddcb8457412c65e9f727e71f7c0cd1986bcc69798d3e920698`. The approved Gate 3 record and its correction history were inspected. No prohibited local full suite was run. Exact-source GitHub CI and deployment remain `UNKNOWN` and are not treated as GREEN.

## Verdict

**CHANGES_REQUESTED**

Publication is blocked. Correct the four findings with sensitive executable regressions, obtain the planner-required Gate 3 delta approval for changed tests, run the refreshed focused plan, and prepare a new exact-source Gate 5 package. No generic assignment framework or order/application redesign is required: the correction belongs at the existing selection owner lock/read seam and the standalone reader/schema boundary.

---

## Gate 5 correction rereview — 2026-09-15

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T085155Z-2638f2d0fb/package.json`
- Exact correction source: base `0a286f3eccb5230d51e787baeeaea0acf7beb894` plus reconstructible snapshot, candidate source `a0da24cca74ac0cf030161ba8815befa6b475b3c7a4a4b1a16b73a968c4d51b2`, executable source `a24b7b33afa8ac1fda1c80a065c518cabc6f5e7fb38c78ecb5f71b98e0bab70c`
- Snapshot patch SHA-256: `e92a54e0f4b0da65bc991c6a386263117aee828e5faef5d31023833211279d71`
- Verification plan SHA-256: `dae615c9a9912eb08f94d13798b2cbeb76e743b27907a67a3797c9f336bb147c`
- Correction verdict: **CHANGES_REQUESTED**

### Prior findings disposition

1. **Resolved.** The Yii controller no longer chooses the engineer. `MariaDbSelectionUnitOfWork.php:13-16` begins the transaction and locks the exact case before constructing the session; `SelectionWork.php:20-26` then reads the standalone authority from that session, compares the shown revision, builds the engineer snapshot and replaces the provisional intent inside the lock. `SelectionForm.php:20-33` makes the assignment revision mandatory for HTTP. The two-server test deliberately queues replacement ahead of selection behind an independently held case lock and requires `[303,409]` plus no extra selection row; the omitted-field case requires 400 and a byte-equivalent fact inventory.

2. **Resolved.** Both checklist `access()` and `queue()` now call `currentEngineerAssignment()` (`MariaDbYiiChecklistRead.php:16,63`), whose only assignment source is `MariaDbControlEngineerAssignmentReader` (`MariaDbYiiChecklistPersistence.php:26-30`). The old `selectedEngineer()`/process-event/legacy-column methods remain elsewhere in the trait but are no longer reached by these #40 current-assignment reads. The added #40 cases prove coherent application bootstrap and 503 for corrupt standalone/bootstrap state without falling back.

3. **Resolved.** `MariaDbControlEngineerAssignmentReader.php:12,16` revalidates the first fact's referenced application through native composition integrity and exact case/object/previous-engineer linkage on every standalone read. The corrected owner test changes both previous engineer and referenced application snapshot linkage and requires `unavailable`.

4. **Partially resolved; HIGH remains.** Read now invokes schema compatibility, and compatibility checks the primary/sequence/request unique sets, self/application FKs and sequence check (`ControlEngineerAssignmentDefinitionSchemaMigration.php:16-21`). It does **not** validate the migration's `installation_case_id -> fm2_installation_cases.id` foreign key, even though that exact FK is created at line 13 and is the persistence guarantee for the required object/case lineage. It also accepts arbitrary types/nullability/auto-increment and omits the declared object/sequence index; merely matching column names does not establish the exact minimal schema contract. The only new drift test drops `uq_control_request` (`tests/InstallationProcess/control_engineer_assignment_001_test.php:82`), so it cannot distinguish the current validator from one that accepts a missing case FK or incompatible column definition. Complete the minimal compatibility check for the table's correctness-bearing column definitions, case/self/bootstrap FKs, uniqueness/check constraints and declared lookup index, then add at least a missing-case-FK read/write fail-closed witness.

### New complete finding

1. **HIGH — a supposedly inert native engineer field still changes idempotent replay identity.** The correction correctly replaces the engineer inside the transaction, but `SelectionApplication.php:10-16` first builds the request intent from `SelectAssignmentOrderCompositionCommand::controlEngineerUserId`. On first success `SelectionWork.php:25-43` persists an intent rebuilt with the authoritative current engineer. On retry `SelectionResolution.php:27-28` substitutes the stored engineer only when the caller's intent engineer is null. Therefore an older native caller that repeats the same accepted request with a non-null stale/forged engineer receives `request_id_conflict`, even though the contract says that field is inert and ignored and old native callers must use current authority (`specs/YII2-CONTROL-ENGINEER-ASSIGNMENT-001.md:46`). Normalize this obsolete field out before request comparison (while retaining the server-resolved engineer in persisted composition identity) and add an exact native replay whose supplied engineer differs from current, proving selected then replayed with one selection/fact set.

### Gate and evidence assessment

The package binds four acceptance GREEN records, all at candidate `a0da24cca74ac0cf030161ba8815befa6b475b3c7a4a4b1a16b73a968c4d51b2` / executable `a24b7b33afa8ac1fda1c80a065c518cabc6f5e7fb38c78ecb5f71b98e0bab70c`:

- owner/history/schema: `1789461885081656000-cc72f248571640d2abad0136e1e145f0.json`
- #40 preopening/opening: `1789461916531606000-2de8d8a54c624e71869dc7052ed82b14.json`
- Yii A–H/concurrency: `1789461946295869000-77c7eda543074d52b3b28638dff6574d.json`
- #38 installer directory: `1789461986294147000-0d8879e6bc3c4d208dc82445af0dd903.json`

They establish the resolved cases listed above but are insensitive to the remaining schema and native-replay findings. The correction package also contains changed executable tests after the last recorded Gate 3 approval: the embedded `reviews/tests/YII2-CONTROL-ENGINEER-ASSIGNMENT-001.md` ends with approval for prior candidate `6a62dc7c60c55c74212c3372d0aa3435f51b5404204b7f6ded44a6b599a1022d`, not this correction's concurrency/bootstrap/schema test delta. Under Gate 5 policy, those changed tests require fresh independent Gate 3 delta approval and a subsequent exact-source package even if the two remaining code findings are corrected.

No prohibited local full suite was run. CI/deployment remain `UNKNOWN` and are not approval or GREEN.

### Correction verdict

**CHANGES_REQUESTED**

The transactional authority, HTTP revision, #40 reader use and bootstrap lineage corrections are accepted. Publication remains blocked on complete minimal schema compatibility, inert-field native replay, and independent Gate 3 approval of the correction tests. Correct those bounded items and prepare a fresh exact-source Gate 5 package.

---

## Final correction rereview — 2026-09-15

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T090438Z-0ce8efd3bc/package.json`
- Exact source: base `0a286f3eccb5230d51e787baeeaea0acf7beb894` plus reconstructible snapshot, candidate `3784fb1c5b10c2d5e418b952cbdbfd51601dc7649cf6cacb83fdff7ac61a498b`, executable `b60b7a87d3d6d0206ca12e0c244d94b7e212c8a3625448736694a6591ce13462`
- Snapshot patch SHA-256: `2e8be6f3f656b53c4618dd1174e1ddeb3f70c0b2b1f3d0b7dea0215e756b9df5`
- Verification plan SHA-256: `694a48fdc74f6420a1da864dd6f9f8c6ac15ed30fe6bbf97bff6ed28a85bee11`
- Verdict: **CHANGES_REQUESTED**

### Unresolved production findings

No production finding remains.

- Schema compatibility now compares correctness-bearing type/null/default/auto-increment definitions, string collation, all declared unique and lookup indexes, case/self/bootstrap FKs, sequence check, engine and table collation (`ControlEngineerAssignmentDefinitionSchemaMigration.php:16-21`). Reader and writer retain their fail-closed calls. The new missing-case-FK case proves both read and command refuse with no facts.
- `SelectionIntent::fromCommand()` now normalizes the obsolete client engineer to null before request identity is calculated (`SelectionIntent.php:7-16`), while `SelectionWork` still persists the locked authoritative engineer in selection/composition snapshots. Thus a non-null stale engineer no longer changes authority or request identity.
- Previously resolved case-lock reread/mandatory revision, #40 authoritative-reader/no-fallback, bootstrap lineage, authorization and immutable-history behavior remain unchanged in the delta.

### Remaining regression/gate finding

1. **HIGH — the new replay regression does not exercise the corrected native seam and is insensitive to reverting the fix.** The prior finding was specifically that a non-null `SelectAssignmentOrderCompositionCommand::controlEngineerUserId` influenced `SelectionIntent::fromCommand()` and native terminal replay. The added case at `tests/Yii2/yii2_control_engineer_assignment_001_test.php:29-30` sends two HTTP forms, but `SelectionController::command()` always constructs the native command with `controlEngineerUserId = null` (`app/YiiRuntime/Controllers/SelectionController.php:89-104`). Both requests therefore reach `SelectionIntent::fromCommand()` with null regardless of the forged form values. Reverting the correction at `SelectionIntent.php:15-16` would leave this test GREEN. Add the requested native public-application regression: call the existing production composition owner twice with one request ID and otherwise identical inputs but different non-null obsolete engineer IDs; require `selected` then `replayed`, one immutable selection, and the authoritative current engineer snapshot. This is a bounded test-only sensitivity correction, not workflow redesign.

The package's four acceptance records are exact-source GREEN:

- `1789462686039538000-5203a6f8611647c69948caa2545df297.json`
- `1789462720563990000-9ba4dcdc41f34440a38bab66795ecb4e.json`
- `1789462750858548000-1ad57abbbdf14c0b9f6d4a3ead5660b2.json`
- `1789462783057737000-17f833d01fea412680823365c52f9d5e.json`

They establish schema fail-closed and retained A–H behavior, but not the native replay correction above. The package snapshot contains Gate 3 approval for the preceding finding-test delta at candidate `a0da24cca74ac0cf030161ba8815befa6b475b3c7a4a4b1a16b73a968c4d51b2`; the final small test delta's independent Gate 3 review was still running and is not part of this exact package. A fresh package must include its verdict and any sensitivity correction.

No prohibited local full suite was run. Exact-source CI/deployment remain `UNKNOWN` and are not treated as GREEN.

### Final correction verdict

**CHANGES_REQUESTED**

The remaining production corrections are accepted. Gate 5 is blocked only by the insensitive native-replay regression and the not-yet-embedded final Gate 3 delta decision. Add the direct native witness, obtain Gate 3 approval, and prepare one fresh exact-source package.

---

## Direct native replay final rereview — 2026-09-15

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T092035Z-13cd9bbe70/package.json`
- Exact reviewed source: base `0a286f3eccb5230d51e787baeeaea0acf7beb894` plus reconstructible snapshot, candidate `1f6c73b835a7b9b89179a6c939fada6834a14cf96e671bc6ced37fbed6640180`, executable `b47b6bc7b861b79cab71ed237ea411bee13097f4735ce234bbfa24c6326abcd2`
- Snapshot patch SHA-256: `a45304817f32e4dedb66c652bd273214b0ed70089febf7ebd84615e231c76b58`
- Verification plan SHA-256: `2ea887c523bcb1c4609b728b1ec8d621aaa1831fab5f997b6457075206c053cd`
- Verdict: **APPROVED**

### Finding resolution

No findings.

The final direct test closes the sole remaining sensitivity gap. `tests/AssignmentOrderComposition/selection_native_authority_001_test.php:10-15` calls the production native composition application twice with one request ID and the same object, actor, installer set, selection revision and assignment revision, while changing the obsolete non-null engineer input from the fixture command's 73 to 999. It requires `selected` then `replayed`, compares the complete table inventory before/after replay, and requires exactly one persisted selection snapshot for authoritative engineer 73. Reverting `SelectionIntent::fromCommand()` to include the supplied engineer would change the normalized request fingerprint and fail this test with `request_id_conflict`; using 999 as authority would fail the snapshot assertion.

`tests/Support/SelectionNativeFixture.php:24-26` adds only the production schemas and one standalone fact needed to expose current assignment through the real owner. Every test receives an isolated fixture, and the existing native authorization, partial-schema, fresh-reader and no-fallback tests remain unchanged. The verification input maps this suite to A–G and the planner binds both new test/support files.

All prior findings remain resolved in the exact candidate:

- current assignment is reread under the existing selection case lock and stale shown revision conflicts;
- HTTP revision is mandatory and manual engineer fields are inert;
- #40 consumes the standalone authoritative reader and fails closed without selection/event/legacy fallback;
- bootstrap application and append-only lineage are revalidated without rewriting historical documents;
- reader/writer enforce the exact minimal schema, including case/self/bootstrap FKs, unique/lookup indexes, sequence check and column/storage definitions;
- #38 installer application projection remains unchanged in authority.

The independent Gate 3 reviewer appended `APPROVED` for the same direct-native test package and candidate to `reviews/tests/YII2-CONTROL-ENGINEER-ASSIGNMENT-001.md` after snapshot capture; this review-only addition changes no executable byte. Its assessment confirms the test's normalization sensitivity, isolated fixture and full 15-command exact-source GREEN inventory.

### Evidence

The package binds five acceptance records at candidate `1f6c73b835a7b9b89179a6c939fada6834a14cf96e671bc6ced37fbed6640180` / executable `b47b6bc7b861b79cab71ed237ea411bee13097f4735ce234bbfa24c6326abcd2`:

- native composition authority/replay: `1789463569135595000-75848308ff9446de82428ced0bb1ad45.json`
- assignment owner/history/schema: `1789463627495784000-768e5b22893f414a8d172d2307c6a3a6.json`
- #40 preopening/opening: `1789463656531988000-98f4ea6b2e82467c948697f606f0a44a.json`
- Yii A–H/concurrency: `1789463684247553000-7ea0ba3265e34c5389ebbc45e19e463d.json`
- #38 installer directory: `1789463717690496000-9500a3a99b184d15aa8b974571f82283.json`

All are GREEN, exit 0, source-bound, with no missing acceptance test. The Gate 3 record reports the complete planner-selected 15 focused commands GREEN at this same executable source. `git diff --check` is clean. No prohibited local full suite was run. Exact-source CI and deployment remain pending/`UNKNOWN`; approval does not infer either.

### Final verdict

**APPROVED**

Gate 5 approves the reviewed executable candidate for publication to exact-source CI. No unresolved specification, security, history, concurrency, bootstrap, schema, Yii transport/UI, #40/#38 or scope finding remains. Merge/deployment are not authorized by this review.
