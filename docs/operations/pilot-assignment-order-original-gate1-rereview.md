# Fresh independent Gate 1 rereview — assignment-order original upload

Date: 2026-09-02  
Reviewer task: `/root/assignment_order_spec_rereview`  
Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001`  
OpenSpec change: `replace-pilot-registration-with-original-upload`  
Verdict: **APPROVED**

The reviewer did not author or modify the reviewed executable specification,
OpenSpec artifacts, canonical product documents, disposition notices, tests, or
production code. This append-only review record is the only review output.

## Reviewed immutable inputs

```text
f84cb5be0741c7a0b200f29cd7db244a2147f37248247d2bd985303d7a7555b9  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
d6a5261cbbd7f12c2c8fd5b21f9d23d93040576d0060a0730900d2617901c566  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
c1bb88a37ebeca9ffd64ba8cd8a6d462e2d7f1aacbdeccc8374c856b96636f24  openspec/changes/replace-pilot-registration-with-original-upload/design.md
19edb7afc2a6e0bb50ee37ecf825ca8a3080531fcf6a20e74674d5400dfd35ec  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
3066e28dd0daffc7152dac55e0f10c75a14dfc2ffc65d7bc13266dc77554a3fb  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
39a0d3454c63f64a54a8bbe8a8f8abd172f2f7576a236319894ab25cf81f4a4d  docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md
511e27b3dd7ab87d0c510b947ff1afa7825afe71c9e8b587556e651c9730035c  docs/operations/pilot-assignment-order-original-active-contract-disposition-2026-09-02.md
```

The prior `CHANGES_REQUIRED` record at
`docs/operations/pilot-assignment-order-original-gate1-review.md` and the
planning approval at
`docs/operations/pilot-assignment-order-original-planning-rereview-v3.md` were
reviewed as historical inputs; neither is treated as approval of these hashes.

## Former findings closure

1. **Supersession/disposition is complete.** Task 1.2 is checked and its
   recorded manifest hash matches. The shared application interface, behavior
   inventory, registration/opening/authorization specs, blocked E2E spec and
   combined-PDF delta carry explicit target-predecessor or supersession notices.
   Historical tests and reviews remain evidence rather than new target approval.
2. **Request replay is executable without stream reads.** After shape and
   authorization, a terminal `requestId` hit owns retry identity and returns the
   stored terminal result before reading or comparing the supplied stream. A new
   intent must use a new request ID; only a request miss consumes bytes and
   performs accepted-operation fingerprint lookup. The RED no-read oracle and
   semantic collision behavior no longer contradict each other.
3. **Lineage and revision reachability is closed.** `rootOriginalId`,
   `targetRevisionId` and `expectedCurrentRevisionId` are separate identities.
   An expected-current mismatch reaches `STALE_REVISION`; an actual-current
   expected ID paired with another revision of the same root reaches
   `TARGET_NOT_CURRENT`; a wrong root/case/order/composition reaches
   `SEMANTIC_COLLISION`. Worked correction/retry examples use the same model.
4. **Failure matrices align.** Both executable spec and OpenSpec distinguish
   incomplete/unreadable stream (`STREAM_FAILURE`), completed invalid input
   (`NOT_PDF`/`INVALID_PDF`/`UNSAFE_PDF`), staging/finalize infrastructure
   (`STORAGE_FAILURE`), definite/absent persistence failure, unknown commit
   outcome, committed outcome and response loss.
5. **Composition confirmation is reachable.** `compositionConfirmed` is a
   boolean; literal `false` maps to `COMPOSITION_NOT_CONFIRMED`, while missing or
   non-boolean input maps to `INVALID_COMMAND`.
6. **The passive-PDF boundary is owned and testable.** Production behavior is
   pinned to `FMonitorPassivePdfInspector` algorithm
   `fmonitor-passive-pdf-v1`, not renderer TCPDF. Accepted versions, xref/Prev
   and object-stream handling, structural filters, limits, fail-closed cases and
   forbidden active keys/actions are literal. Changes to grammar, limits or
   algorithm ID return through Gate 1/2. The positive fixture is an independent
   literal oracle; adversarial fixture classes and sensitivity obligations are
   named for Gate 2.
7. **Audit atomicity is closed.** Accepted revision/result/event share one DB
   transaction. Valid-shape rejection/conflict terminal result and safe attempt
   audit are atomic; audit failure replaces that outcome with retryable
   `PERSISTENCE_FAILURE` and leaves no terminal result. Retryable stream/storage
   and ambiguous persistence failures do not become terminal request hits, with
   their best-effort audit/log behavior stated separately.
8. **Private orphan ownership is explicit.** The authorized maintenance seam
   `reconcileAssignmentOrderOriginalPrivateOrphans` has exact principal
   capability, request/cutoff/batch/cursor input, result shape, one-hour horizon,
   digest-scoped lock, repository reference recheck, bounded batch,
   append-only maintenance audit and at-most-once/idempotent delete semantics.
   Retry/reuse and reconciliation serialize on the same digest lock.

## Gate 1 assessment

The executable spec exposes one business state-changing application command
and one separately authorized maintenance seam required by its private-storage
failure protocol. Actors, preconditions, immutable input/result DTOs,
authorization precedence, accepted/rejected/conflict/failure outcomes,
append-only facts, audit, concurrency, worked values and RED observables are
closed enough for independent Gate 2 authorship. HTTP upload/read/download,
composition applicability and opening remain named future slices and are not
claimed GREEN.

No blocking or major finding remains for the reviewed hashes. This review
approves Gate 1 content for presentation to the owner as one exact-hash batch;
it is not owner approval, RED approval, GREEN, or Done.

## Verification

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ openspec list --json
replace-pilot-registration-with-original-upload: 2/14, in-progress

$ git diff --check
PASS (exit 0 before this review record was added)

$ base64 -d <section-5-positive-fixture> | wc -c
327

$ base64 -d <section-5-positive-fixture> | sha256sum
4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784
```

