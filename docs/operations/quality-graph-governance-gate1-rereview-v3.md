# QUALITY-GRAPH-GOVERNANCE-001 v0.3 — independent Gate 1 re-review

Date: 2026-09-02  
Reviewer: `agent:/root/qg_gate1_review`  
Reviewer role: separately tasked Gate 1 reviewer; did not author the specification, owner approval, OpenSpec artifacts, audit, or research  
Specification: `specs/QUALITY-GRAPH-GOVERNANCE-001.md` v0.3  
Owner approval: `docs/operations/quality-graph-governance-v03-owner-approval-2026-09-02.md`  
Prior review: `docs/operations/quality-graph-governance-gate1-rereview-v2.md`  
Verdict: **CHANGES_REQUESTED**

## Resolved findings

- The test set is now carried by RED, test review, GREEN and the receipt as ordered path/hash pairs. The implementation set is likewise bound between GREEN, code review and the receipt.
- Review records no longer claim their own containing commit; deriving their unique first-introduction commit from Git history removes the review-file SHA self-reference identified in v0.2.
- The representative migration matrix and task 6.1 now consistently require an actual open GitHub PR, distinguish parity from added governance coverage and retain the phase-B bootstrap blocker.
- The immutable single-leaf supersession chain and fixed production discovery/input boundary remain adequate.
- Explicit owner approval of v0.3 is recorded and preserves the no-merge, no-branch-protection-change, no-removal and no-parity-waiver limits.

## Blocking findings

1. **RED and GREEN retain the same impossible self-containing commit cycle removed from reviews.** RED metadata contains `redCommit`, its receipt entry calls that value the RED commit, and the checker requires the RED file content/hash at that commit. Adding or changing the RED file to contain the commit SHA changes the commit SHA. GREEN has the same cycle through `implementationCommit` while GREEN content must exist at that implementation commit. Define separately derived evidence-record commits, while metadata names only already-existing tested/implementation commits, then specify ancestry and content checks among `tested commit < derived RED-record commit < derived test-review-record commit < implementation commit < derived GREEN-record commit < derived code-review-record commit`. Alternatively derive all containing commits and omit every self-containing SHA. The current valid-chain fixture cannot be constructed.

2. **Gate 3 is not proven to precede implementation.** The stated ancestry permits `derived test-review record commit ≤ implementation commit`. Equality allows the approved test review and production implementation to be introduced in the same commit, contrary to `docs/development-process.md`, which requires independent test approval before Gate 4 begins. This edge must be strict, and the implementation commit must be proven not to contain pre-approval production changes.

3. **“Exact” test and implementation sets have no completeness oracle.** Equality among receipt/evidence records proves consistency only for the paths the receipt author elects to list. The contract does not require the test set to equal the relevant Git diff at the RED boundary or the implementation set to equal the production/tooling diff after test approval. An omitted changed test or source/graph/workflow file therefore escapes hash binding and review lineage. Define how the checker derives the complete changed-file sets from Git boundaries, including explicit allowed evidence/status exclusions, and require exact equality with the listed sets.

4. **Specification authorship remains self-asserted.** `authors.spec` appears only in the receipt. The executable specification is governed and hashed but has no defined canonical metadata schema containing its author, and no Git-derived authorship rule is stated. Thus reviewer independence is bound for test/implementation authors but the promised canonical authorship set is incomplete. Either add authoritative spec metadata and its equality rule or remove `authors.spec` from the security-relevant receipt contract and state that Gate 1 owner approval, not receipt authorship, authorizes the spec.

## OpenSpec coherence

- Design decision 2 still ends by saying sidecar versus front matter will be selected in the executable spec, although v0.3 has selected a fenced `delivery-metadata` block. Replace the stale future-tense choice.
- Migration-plan step 4 and task 5.1 say “exact commit” without consistently naming it the exact implementation commit plus constrained later evidence envelope. Task 7.1 still requires lineage governance “on exact reviewed commit,” where GREEN/code-review/receipt records cannot all exist. Align these with the non-circular stage/envelope model chosen by the revised spec.
- The delta spec is otherwise materially aligned with exact test/implementation sets, derived review commits, actual representative PR and constrained evidence envelope, but must be updated again when finding 1 introduces derived RED/GREEN record commits.

## Conditions for approval

Remove self-containing SHA fields from RED/GREEN record commits by separating tested/implementation commits from derived evidence-record commits; enforce strict Gate 3-before-Gate 4 ancestry; define complete Git-derived test and implementation change sets; resolve spec-author authority; and reconcile the remaining OpenSpec wording. Because these alter executable provenance semantics, obtain explicit owner approval of the next version before requesting another independent Gate 1 review. No v0.3 test should advance as an approved Gate 2 test.
