# QUALITY-GRAPH-GOVERNANCE-001 v0.2 — independent Gate 1 re-review

Date: 2026-09-02  
Reviewer: `agent:/root/qg_gate1_review`  
Reviewer role: separately tasked Gate 1 reviewer; did not author the specification, approval evidence, OpenSpec artifacts, audit, or research  
Specification: `specs/QUALITY-GRAPH-GOVERNANCE-001.md` v0.2  
Owner approval: `docs/operations/quality-graph-governance-owner-approval-2026-09-02.md`  
Prior review: `docs/operations/quality-graph-governance-gate1-review.md`  
Verdict: **CHANGES_REQUESTED**

## Outcome against the six prior blockers

1. **Exact-commit cycle: not resolved.** Separating the reviewed implementation commit from later evidence-envelope commits is the correct direction. However, canonical test-review metadata requires `reviewCommit`, and canonical code-review metadata requires `reviewRecordCommit`; the checker must require those values to agree with receipt commits and with content at those commits. A Git-tracked review file cannot contain the SHA of the commit that first contains its final bytes: changing the placeholder changes that commit SHA. This is the same self-reference cycle at each review-record commit. Remove self-commit identities from content, or define them as checker-derived enclosing commits while metadata names only the already-existing artifact commit being reviewed. The exact derivation and receipt equality rule must be observable and non-circular.

2. **Authoritative metadata binding: partially resolved, but test lineage is missing.** Strict `delivery-metadata`, canonical identity syntax, and receipt-to-record equality close the prior ability to invent review identity/verdict solely in the receipt. But neither the receipt nor RED/test-review metadata names or hashes the test artifact(s). Therefore the checker cannot establish which test produced RED, which test the independent reviewer approved, or whether that test changed before GREEN. This fails the promised `specification → RED → independent test review → GREEN` lineage and permits an unrelated test/review pairing. Add normalized test paths and content hashes (or an equally exact test-set digest) to authoritative RED and test-review metadata and the receipt, and define invalidation/equality at the RED, review, and implementation commits. Also state the exact mappings `authors.test == red.author`, `authors.implementation == green.author`, and receipt review fields to metadata rather than relying on the general phrase “exact equality.”

3. **Gate chronology: structurally improved but still blocked by finding 1.** Strict Git ancestry and content-at-stage-commit checks are machine-verifiable and correctly reject reversed/unrelated histories. Once review-record commit identity is made non-circular and test artifacts are bound, this blocker is resolved in substance.

4. **Representative-PR oracle: resolved in the executable spec, but OpenSpec tasks are stale.** The v0.2 table correctly separates old-harness parity from added governance/publisher coverage, requires an actual open PR, identical head SHA, and run URLs/IDs. Task 6.1 still permits an “equivalent PR ref,” contradicting the normative spec. Update it to require the actual open GitHub PR so planning artifacts remain coherent.

5. **Append-only correction history: resolved.** Immutable receipt IDs, a single acyclic supersession chain, one current leaf, earlier-commit targets, and failure for broken/multiple histories provide a deterministic correction model without rewriting historical receipts.

6. **Input/discovery trust boundary: resolved for the production seam.** Fixed production discovery, bytewise ordering, fail-closed file/root cases, no production overrides, checkout-derived HEAD, and `GITHUB_SHA` equality remove the caller-controlled production head/root ambiguity. A test-only temporary root is appropriately isolated from the public Make seam.

## Additional coherence findings

- OpenSpec design decision 2 still describes gate `timestamps/sequence` and an explicitly generated/updated receipt, while v0.2 makes chronology Git-derived and receipts immutable with supersession. It also says the sidecar-versus-front-matter format remains to be chosen even though v0.2 chose fenced `delivery-metadata`. Update the design to the approved contract.
- The OpenSpec delta scenario still requires Gate 5 to approve the exact graph `head commit`; v0.2 intentionally requires the code review to approve the implementation commit and then permits a constrained evidence envelope through current HEAD. Update that scenario to describe the implementation/envelope distinction.
- Task 3.3 still asks for timestamp/sequence fields, inconsistent with Git-derived chronology, and task 7.1 asks lineage governance on the “exact reviewed commit,” on which the later code-review record/receipt cannot yet exist. Update tasks to the implementation-commit plus constrained-envelope model.

## Owner approval assessment

The new approval record is sufficient evidence that the owner approved v0.2 and authorized RED, implementation, independent reviews, and an unmerged representative PR while withholding merge, branch-protection, removal, and parity-waiver authority. The prior approval-status objection is resolved. Because the executable contract still needs substantive changes for findings 1 and 2, the resulting next version requires fresh owner confirmation before Gate 2.

## Conditions for approval

Define a constructible, checker-derived review-record commit identity; bind exact test artifacts through RED, test review, and GREEN; state the remaining metadata mappings explicitly; and reconcile the OpenSpec design, delta scenario, and tasks with that contract. Then obtain owner confirmation of the revised executable version and request another independent Gate 1 review. No v0.2 test should advance as an approved Gate 2 test.
