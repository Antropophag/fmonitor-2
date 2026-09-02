# Fresh independent Gate 1 rereview v4 — assignment-order original upload

Date: 2026-09-02  
Reviewer: separately tasked agent `/root/assignment_original_v4_review`  
Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v3  
Scope: correction after v3 content-lease and `TARGET_NOT_FOUND` findings; no tests or production implementation reviewed  
Verdict: **CHANGES_REQUIRED**

## Exact reviewed artifacts

```text
beb74f4838cb863de5277845056f557f7f1e093b4221f4b9d12c23b5bba951d8  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
a99946c8662b8cf6dbc21ff8e513bf0813cc6d6604a92087a03c019e2922c482  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
81392c11d9b1654f1441728dcdade6898b136d62fe452a3eb4b361037ac2f2f6  openspec/changes/replace-pilot-registration-with-original-upload/design.md
48f770debba45a6b21797a766ed78cf1573788f4d0b302b1e8fcf83c3a1f6b50  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
665a09f00f3e90f0cca7a9953de8fe7c680535279a07a61fb01bcb8a482f6ca5  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
08a3f37cc6d03e1057f5ceb0347ff53c337a5369bef455b0e23961229c78cbf7  docs/operations/assignment-order-original-upload-gate2-constructibility-gap-2026-09-02.md
```

## Outcome

The amendment closes both explicit v3 findings in substance. Successful
finalize/reuse now returns a typed private-content lease that owns the immutable
content and the digest exclusion token shared with maintenance. Upload holds it
through accepted commit, definite rollback, and the fresh terminal-request
lookup resolving an unknown commit outcome. Maintenance therefore observes
`LOCKED` and cannot delete content during those intervals. Acquisition failure,
typed/throwing release failure, safe logging, non-public orphan retention and
storage-owned recovery are specified. The exhaustive conflict list now includes
`TARGET_NOT_FOUND` consistently in the executable matrix, PHP enum, delta
requirement and unknown-target scenario.

Gate 1 still cannot pass because one declared repository outcome has no lease
release point.

## Blocking finding

### CAS `CONFLICT` has no specified lease release transition

`AssignmentOrderOriginalCommitStatus` includes `CONFLICT`, and the contract
requires a conflict to trigger fingerprint/current-lineage rereads before the
application chooses exact `REPLAYED` or stale-conflict behavior. The normative
lease rule, however, says release occurs exactly once only after `COMMITTED`,
definite `ROLLED_BACK`, or the fresh lookup following `OUTCOME_UNKNOWN`. It does
not say whether the lease is released immediately after `CONFLICT` or after its
required reconciliation reads, and the release-failure matrix likewise has no
conflict branch.

Following the literal closed contract leaks the digest exclusion token on every
CAS loser, leaving maintenance permanently `LOCKED`; inventing either release
point and its typed/throwing failure result would make the RED author supply a
missing application rule. Add `CONFLICT` resolution to the exact lease lifetime
and release/failure mapping. A minimal coherent rule is to keep the lease until
the required fingerprint/current-lineage rereads select the terminal result,
then attempt release exactly once; release failure must not replace that selected
result and must use the same safe-log/storage-owned recovery semantics.

## Prior-finding disposition and scope

- **Typed lease through commit/unknown:** resolved for `COMMITTED`,
  `ROLLED_BACK`, and `OUTCOME_UNKNOWN`; shared maintenance exclusion is explicit.
- **Maintenance deletion safety:** resolved for every interval for which lease
  lifetime is specified; locked candidates are retained and reference checks
  precede finalized-content deletion.
- **Lease acquisition/release/orphan failures:** resolved except for the omitted
  `CONFLICT` branch above.
- **Exhaustive conflict reasons:** resolved; `TARGET_NOT_FOUND` is present in all
  normative representations.
- **Other v3 surface:** no additional API, evidence-schema, five-FD worker,
  maintenance pagination, audit/replay, PDF, authorization or scope finding was
  identified in this rereview.

## Verification evidence

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ php -l <concatenated normative PHP blocks>
No syntax errors detected in Standard input code

$ git diff --check
PASS (no output)
```

The working tree was already heavily dirty. This reviewer changed no reviewed
specification, OpenSpec artifact, test or production file; only this append-only
review record was added. Task 1.6 must remain open and Gate 2 remains blocked
until a fresh rereview approves corrected exact hashes.
