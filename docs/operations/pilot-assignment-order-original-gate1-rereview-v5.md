# Fresh independent Gate 1 rereview v5 — assignment-order original upload

Date: 2026-09-03  
Reviewer: separately tasked agent `/root/original_pdf_gate1_review`  
Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v4  
Scope: v4 CAS-conflict content-lease amendment and the complete current contract; no tests or production implementation reviewed  
Verdict: **APPROVED**

## Independence

The reviewer did not author or edit the executable specification, OpenSpec
artifacts, canonical product documents, prior reviews, owner decisions, tests,
or production code. This append-only review record is the reviewer's only file
change. The reviewer is not the v4 amendment author or any prior Gate 1 reviewer.

## Exact reviewed artifacts

```text
97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
a99946c8662b8cf6dbc21ff8e513bf0813cc6d6604a92087a03c019e2922c482  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
b81f11b5aabd69645404b624d5301cd65a209b870d06ef587dcb34eebbcfc9b2  openspec/changes/replace-pilot-registration-with-original-upload/design.md
4dbe286f0b55fdd2c4bed82f2ddedf8887f10741be444a07b47b31893e860e2d  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
08a3f37cc6d03e1057f5ceb0347ff53c337a5369bef455b0e23961229c78cbf7  docs/operations/assignment-order-original-upload-gate2-constructibility-gap-2026-09-02.md
ddaea0f53c84258b8e7aca076f40f2f18e59c8dc34c362105d1d661295b1323e  docs/operations/pilot-assignment-order-original-gate1-rereview-v3.md
c3151db747091a8a15508998b44e693ba7d3d29b9995c3678f0dc2e763949878  docs/operations/pilot-assignment-order-original-gate1-rereview-v4.md
a567abfc10383822cbec3debe423dab042b5b1c03b7b25df5e88d6c89f39f6ea  docs/operations/assignment-order-original-and-construction-queue-owner-approval-2026-09-02.md
39a0d3454c63f64a54a8bbe8a8f8abd172f2f7576a236319894ab25cf81f4a4d  docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md
511e27b3dd7ab87d0c510b947ff1afa7825afe71c9e8b587556e651c9730035c  docs/operations/pilot-assignment-order-original-active-contract-disposition-2026-09-02.md
```

The reviewer also read every `contextFiles` path returned by
`openspec instructions apply`, the complete executable specification, all prior
assignment-order-original planning and Gate 1 reviews, and the recorded owner
direction and prior exact-hash approval. The prior approval is historical and
does not approve this v4 batch.

## v4 amendment assessment

The sole blocker in the v4 predecessor review is closed coherently:

1. A successful finalize or verified reuse returns exactly one typed private
   content lease in the digest exclusion domain shared with maintenance.
2. A CAS loser keeps that lease held through both mandatory rereads, in the
   fixed order accepted fingerprint then current lineage. Maintenance therefore
   continues to observe `LOCKED` while the application resolves the race.
3. Only after those rereads select the provisional result does the application
   attempt release exactly once. The selectable outcomes are the winner's
   `REPLAYED`, an exact non-replay `CONFLICT/*`, or
   `FAILED/PERSISTENCE_FAILURE` when reconciliation cannot establish the
   lineage outcome.
4. Typed `FAILED` or a thrown release failure preserves that selected result,
   records the exact redacted cleanup log once, and leaves the exclusion token
   under storage-owned bounded recovery. It cannot expose or delete the blob.
5. For a non-replay conflict, the terminal result and safe attempt audit still
   follow the atomic section 11 transaction after the release attempt. Release
   failure expressly MUST NOT skip it. Audit-transaction failure retains its
   existing `FAILED/PERSISTENCE_FAILURE` precedence.
6. No delivery observer can run before the release attempt. The detailed
   executable rules, failure matrix, design decision and delta scenario all
   specify the same lifecycle.

This removes both unsafe alternatives identified by the predecessor review:
neither premature maintenance deletion during reconciliation nor a permanently
unreleased CAS-loser lease is permitted by the contract.

## Complete-current-contract assessment

No blocking or major finding remains across the full current batch.

- The public business mutation remains one application command with exact
  process authorization, request-replay precedence, immutable initial and
  correction lineage, deterministic CAS outcomes and no composition/opening
  mutation. The separately authorized maintenance seam is bounded to private
  orphan recovery required by the publication protocol.
- DTOs, result/reason enums, parser and byte policy, date/clock ownership,
  semantic fingerprint, repository commit statuses, failure precedence, safe
  audit/log behavior and evidence schemas remain closed and mutually coherent.
- The exact PHP construction surface is syntactically valid. Production and
  verification factories keep dependency selection explicit; verifier-only
  observers, faults and worker IPC cannot be selected by production runtime
  input.
- Product and pilot truth consistently use original PDF plus a separate opening
  action. HTTP/read/download, composition application and opening replacement
  remain named future changes and are not claimed GREEN by this slice.
- All former Gate 1 findings remain closed. `TARGET_NOT_FOUND` is still present
  in every exhaustive conflict representation, and the typed lease continues to
  protect old finalized-orphan reuse through DB outcome resolution.

The concise orphan paragraph in executable section 10 describes the ordinary
commit/rollback/unknown paths; the later normative section 15 and the explicit
CAS row/scenario extend that same lease lifetime for `CONFLICT`. They do not
offer competing release points.

## Gate decision

Gate 1 technical review for the exact v4 hashes above is **APPROVED**. This
record permits presentation of that exact batch for the new owner approval in
task 1.7. It is not owner approval and does not authorize Gate 2, tests,
production implementation, GREEN, or Done. Task 1.7 MUST remain the next gate.

## Verification evidence

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ awk '<extract all normative php fences>' specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md | php -l
No syntax errors detected in Standard input code

$ git diff --check
PASS (no output before this review record was added)
```
