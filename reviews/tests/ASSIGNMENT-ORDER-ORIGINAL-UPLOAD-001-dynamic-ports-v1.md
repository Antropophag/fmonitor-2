# Test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 dynamic ports v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit authored by Timofey Grishin
- Reviewed commit: `e2726f9743fa5b6250151083e307f395250f33d8`
- Specification: active `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v61, SHA256 `d65470e2e1da510aa1ebe6d9cce6549b8fffcc6bf66035716623eb0cad61f890`
- Public seam: `AssignmentOrderOriginalApplication::submitAssignmentOrderOriginal(Command): Result` with the approved total clock, ID-source and repository lookup ports
- Red command and intended failure: `php tests/InstallationProcess/assignment_order_original_dynamic_ports_001_test.php`; 24 intended failures and 9 positive controls across 33 cases, aggregate exit `255`
- Verdict: `APPROVED`

## Exact reviewed inputs

```text
d65470e2e1da510aa1ebe6d9cce6549b8fffcc6bf66035716623eb0cad61f890  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
11292e0647dac4e0df6527282f381fcffa9576e993d70f65a227568cb1a793b3  tests/InstallationProcess/assignment_order_original_dynamic_ports_001_test.php
c04f1e73636869adde1eff2d6d38a2af11900f16534a3291c48daa9faa65e4aa  tests/Support/AssignmentOrderOriginalDynamicPortsFixture.php
53aecac4f8f0d8c267c7667fa33a83ac2d7a30189a7646f7cdb342ebbeb948b6  docs/operations/original-command-dynamic-ports-red-v1-2026-09-06.md
8ea2b82300c157f2ca6c69b9b4eef5ab773d8e612d22f6725d4d88aa705c013f  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php (RED base)
9375d308272b6bc7a6a928f51ef68b6a0472b3d2f630be236f58b7c7eeedbb6f  /Users/antropophag/.local/state/fmonitor2-verification/original-dynamic-red-h47kdud9/evidence.json
300d90d4a749257b16dc45b7667e75ad94a17550f0d5d2eda7b03b64c00fa25a  /Users/antropophag/.local/state/fmonitor2-verification/original-dynamic-red-h47kdud9/red.log
5008207cea09e3310ba4008026afb4a2e453e75db4576c9fc3f1118172ff43bb  /Users/antropophag/.local/state/fmonitor2-verification/original-dynamic-red-h47kdud9/draft-invalid-correction-authorizer.log
```

The test commit adds only the focused public-seam test, its fixtures and RED evidence; production behavior remains the RED-base implementation. The preliminary draft log is retained as invalid authoring history and is not used as behavioral evidence.

## Findings

Traceability is complete for cumulative command-v3 findings 01/02/03. The active v61 contract already defines the four exact ID statuses, nullable ID carrier, maximum eight collision attempts, canonical UTC-second clock, post-stream accepted-fingerprint lookup, total port mappings and correction authorization. The test adds no new outcome or behavior.

The test begins with independent PDF size/hash literals and checks the exact public `AssignmentOrderOriginalIdStatus` alternatives in declaration order: `generated`, `collision`, `exhausted`, `unavailable`. Missing approved enum cases are reported as explicit `INTENDED_RED` failures before constructing an impossible scenario. They are neither skipped nor translated into fixture/environment errors. A named public `AssignmentOrderOriginalIdResult` construction also pins the approved `status`/`id` carrier surface.

Post-stream lookup sensitivity is exact. The fixture first records the existing empty availability probe and then the real accepted-operation fingerprint `dd356db041181636ce1ecfc619f9055a625d81250e59ad3543c9f5cd5b582a7d`, derived from the specification's Example A. `UNAVAILABLE` at that second lookup must select retryable `FAILED/PERSISTENCE_FAILURE`, allocate no IDs, finalize no content, abort/close the stage and close the stream once, emit no later lifecycle event, create no fact/audit and perform no delivery. Two stream reads prove the failure occurs after actual byte acquisition rather than at setup.

The ID matrix crosses root allocation for initial upload, revision allocation for initial upload and revision-only allocation for correction with seven port behaviors: immediate generated, typed unavailable, typed exhausted, generated/null, Throwable, seven collisions followed by success on call eight, and eight collisions followed by failure without a ninth call. Exact root/revision counters prove failure stops before the next ID source and that correction never requests a new root.

Successful call-eight controls pin the full accepted result, generated IDs persisted in the accepted commit, exactly one finalize/stage close/stream close/lease release, one accepted commit, zero attempt audits and one delivery. Failure cases require the full persistence-failure tuple, no finalize, exactly one abort/stage close/stream close, unchanged preexisting evidence, no accepted or attempt fact and no delivery. This detects null-ID acceptance, off-by-one retry bounds, allocation after a terminal failure and persistence with an unapproved ID.

Correction controls use an authorizer that permits both exact inherited capabilities, including `assignment_order.original.correct`. They supply an existing root/revision/composition snapshot, exact target and expected-current revision, approved reason/date, preserve the original root and allocate/persist only `revision-0002` with revision number 2. This closes the preliminary draft's wrong upload-only authorizer; the passing correction-generated and correction-Throwable controls demonstrate that final correction branches reach the intended ID boundary.

The clock matrix is independently literal and runs before stream or stage. Missing `Z`, space separator, fractional seconds, numeric offset, invalid calendar day, hour 24 and leap second must each return retryable persistence failure after exactly one clock call, with no storage begin, ID call, real fingerprint lookup, stream read, storage/lifecycle event, accepted/attempt fact or delivery; the supplied stream is still closed once. A throwing clock is a positive RED control for the already supported total-boundary mapping.

Two canonical controls at `2026-09-01T21:00:00Z` prove Moscow-date conversion: document date `2026-09-02` is accepted with exact evidence, while `2026-09-03` is rejected as future with one terminal audit and no stage. These prevent a constant clock rejection and independently pin the UTC-to-Moscow boundary.

Expected results are independent of production output. PDF bytes/hash, fingerprint, request/case/order/actor, root/revision IDs, dates/times, collision counts, results and call traces are fixed by v61. Fixture repositories and storage expose counts and canonical evidence only; they do not compute expected status from the implementation.

All 33 cases use fresh in-memory public-port graphs. There is no database, filesystem, OS, production data, real document, PII, native hook, fault selector or remote dependency. Each case is caught and reported independently before the final aggregate failure, so missing enum declarations do not prevent valid generated/Throwable/canonical controls from running.

Independent reproduction at reviewed commit matched the archive:

```text
PASS controls: 9
INTENDED/behavior failures: 24
Fatal aggregate: Dynamic ports failed 24 cases
Exit: 255
```

The 24 failures are exactly the public ID contract, post-stream lookup, typed/null/collision ID branches and noncanonical clock branches listed in the RED record. Both PHP artifacts lint, `git diff --check` passes, and archive hashes match. The preliminary wrong-authorizer draft is explicitly excluded from this classification.

No blocking traceability, public-seam, expected-value independence, branch-constructibility, correction-authorization, sensitivity, determinism or isolation finding remains. Minimal GREEN may implement only the active v61 total-port and validation behavior exercised by these exact tests.

## Required changes

None.

This approval does not establish GREEN, Gate 5, full original-command regression success, combined original-command approval, or broader release readiness.
