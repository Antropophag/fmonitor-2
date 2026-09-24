# BACKLOG-ISSUE-LABELS-001 — independent Gate 1 test review

- Reviewed: 2026-09-24
- Reviewer: independent `gpt-5.6-sol/low` agent `/root/issue256_gate1`
- Author independence: reviewer authored neither the contract nor the test
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T161242Z-f75847e0ea/package.json`
- Candidate source: `854cb1d9957c5200e758644e2b6c59e47dbba4db3e2a6e034628534d703d0100`
- Base: `199e1b38257bafd21e9254f2019f169dec641de1`
- Verdict: **RETURNED**

## Findings

### G1-01 — BLOCKER: the state-changing public seam is optional and can pass unverified

`tests/Verification/backlog_issue_labels_256_test.py:51-68` skips all GitHub-state
acceptance when `FMONITOR_ISSUE_LABELS_SNAPSHOT` is absent. The two remaining
tests inspect repository prose only. Consequently the registered focused command
can become GREEN without evidence that any label was created or updated, that the
complete open backlog was classified, or that GitHub rejected no mutation. This
does not exercise the confirmed public seam in `specs/BACKLOG-ISSUE-LABELS-001.md:8-15`
and cannot satisfy Gate 2 for a CRITICAL irreversible external mutation.

Correction: make retained before/plan/after evidence a required input to a
deterministic verifier (the evidence may remain outside the checkout), fail closed
when it is absent or incomplete, and validate the repository identity, capture
times, pagination/completeness witnesses, mutation results, and final reread. A
separate repository-only test may remain runnable without live evidence, but it
must not be mapped as coverage of the migration seam.

### G1-02 — BLOCKER: the snapshot assertion covers only schema and two cardinalities

`tests/Verification/backlog_issue_labels_256_test.py:55-68` verifies exact metadata
for the ten names and type/prep cardinality. It does not verify the acceptance
behaviour promised by the canonical and delta specs:

- preservation of unrelated and `quality-graph:*` labels;
- exclusion and non-mutation of pull requests and closed issues;
- per-issue reread, `updatedAt` conflict handling, reevaluation, or closed skip;
- concrete current reasons for `prep:needs-work` and `status:blocked`, positive
  evidence for every status, or status combinations;
- deduplication of explanatory comments and a zero-mutation dry rerun;
- conflict-stop behaviour for an existing target label with different semantics;
- partial API failure retained as failed/UNKNOWN rather than successful;
- new issues found by the after reread or an explicit reported coverage boundary;
- report reconciliation to actual applied and unapplied operations.

The test would therefore accept snapshots that violate most A1-A8 scenarios.
Correction: introduce explicit evidence schemas and adversarial deterministic
fixtures for each branch above, including negative cases that prove fail-closed
behaviour and preservation of append-only comments and unrelated labels.

### G1-03 — BLOCKER: verification-input coverage declarations are false

`openspec/changes/classify-backlog-issue-labels/verification-input.json:25-35`
marks stdout, stderr, exit status, retained evidence, filesystem effects,
idempotence, failure semantics, caller interoperability, and registry
synchronization as covered by this test. The test invokes no command, captures no
streams or status, examines no mutation plan/result, compares no before/after
filesystem or GitHub effects, and performs no second run. The generated plan then
collapses all eight acceptance areas into one broad identifier and inherits these
unsupported claims.

Correction: split the acceptance mapping into observable requirements and map
only assertions that exist. Add a deterministic verifier/executor boundary whose
exit status and diagnostics are asserted, or mark dimensions unresolved until
such evidence exists; regenerate the plan before the next review.

### G1-04 — MAJOR: the canonical contract is not executable enough for evidence validation

`specs/BACKLOG-ISSUE-LABELS-001.md:58-63` names the broad contents of snapshot,
plan, and report but does not define their required identity and correlation
fields or a deterministic success/failure rule. It does not say how completeness
of pagination is witnessed, how before/after issue identity is reconciled, what
constitutes an equivalent existing explanation, or how a mutation result is tied
to the exact planned `observedUpdatedAt`. Without these decisions, independent
expected values for safety and idempotence cannot be derived and different
executors can produce incompatible evidence while claiming conformance.

Correction: add a minimal normative evidence contract (or explicit worked
examples) for before, plan, mutation journal and after snapshot, including stable
issue/comment identifiers, observed/current timestamps, requested versus applied
operations, API outcome, pagination/count witness and rerun result. Define a
conservative equivalence rule for comments, or require a stable marker/key owned
by this migration.

## RED evidence

Command run locally (bounded; no GitHub mutation):

`python3 tests/Verification/backlog_issue_labels_256_test.py`

Result: exit 1, three tests run, one failure and one skip. The failure is the
intended missing `docs/issue-labels.md`; the public GitHub acceptance was skipped.
This proves only the documentation RED, not RED sensitivity for the migration.

No full local `make test` or `make verify` was run. No GitHub state was read or
mutated by this review.

## Files and package material reviewed

- `AGENTS.md`
- `docs/development-process.md`
- `docs/operations/current-delivery-goal.md`
- `docs/operations/issue-256-backlog-labels-delivery.md`
- `specs/BACKLOG-ISSUE-LABELS-001.md`
- `tests/Verification/backlog_issue_labels_256_test.py`
- `tools/verification/suites.tsv`
- `openspec/changes/classify-backlog-issue-labels/proposal.md`
- `openspec/changes/classify-backlog-issue-labels/design.md`
- `openspec/changes/classify-backlog-issue-labels/tasks.md`
- `openspec/changes/classify-backlog-issue-labels/specs/operations/backlog-issue-classification/spec.md`
- `openspec/changes/classify-backlog-issue-labels/verification-input.json`
- prepared `package.json`, `required-context.json`, task-context manifest and
  `verification-plan.json` named by the package above

## Process caveat and disposition

The assignment calls this a Gate 1 review, while the prepared CRITICAL plan names
the formal independent review as `gate3`. This record reviews specification
completeness and the root-authored pre-implementation test together; it does not
approve Gate 3, authorize Gate 4, or authorize any external GitHub mutation.
After the contract, tests and verification input are corrected, the plan must be
regenerated and the complete RED candidate independently rereviewed.

---

## Rereview — 2026-09-24, package `20260924T161851Z-0c8e045a93`

- Candidate source: `ae4c5cf4dac5bc0fdaca85550f64b63cfa055d4467c57d5181979ddb34e1d40e`
- Verification plan SHA-256: `99cd83b521d1bbeae446279f36f609bae0d44307052283553d09474025675dce`
- Verdict: **RETURNED**

### Prior finding dispositions

- G1-01: **partially fixed**. A deterministic external envelope is now accepted
  through `FMONITOR_ISSUE_LABELS_EVIDENCE`, and the real before envelope produces
  the intended migration RED. The ordinary registered invocation still skips the
  external test, but the package now distinguishes the focused delivery evidence
  run; that split is acceptable only if Gate 4/final evidence records the exact
  environment-bound command and file digest.
- G1-02: **partially fixed, still blocking**. Synthetic complete evidence and a
  ten-case negative matrix now cover schema/cardinality, unrelated labels,
  PR-in-plan, closed state in after, reread/reclassification, blocked reason,
  partial failure, new issue classification and rerun idempotence. The remaining
  safety holes are R2-01 through R2-03 below.
- G1-03: **mostly fixed**. Stdout/stderr are honestly not applicable and the
  envelope now exercises retained evidence, exit status, idempotence and failure
  semantics. `filesystem_effects` and `caller_interoperability` remain broad
  labels, but the repository assertions plus environment-file consumer give them
  a concrete observable meaning. Registry synchronization is supported by the
  changed `tools/verification/suites.tsv` and the planner's governance command.
- G1-04: **partially fixed**. Section 6 adds the required envelope and correlation
  rules. It still cannot substantiate comment equivalence or positive bases for
  two status kinds because those facts are absent from the envelope.

### R2-01 — BLOCKER: a skipped closed issue may still have successful mutations

`validate_evidence()` allows every issue operation whose number is in `skipped`:
the membership check at `tests/Verification/backlog_issue_labels_256_test.py:79-81`
explicitly accepts it, but no assertion requires `SKIPPED_CLOSED` or forbids
`APPLIED` label/comment operations for that number. An envelope can therefore
list issue 1 in `skippedClosedIssueNumbers`, remove it from `after`, and retain
successful mutations against issue 1 while passing. This violates the closed
issue non-mutation requirement and leaves the prior closed-boundary finding open.

Correction: for each skipped number require at least one current-state witness
showing `CLOSED`, require every associated operation to be `SKIPPED_CLOSED`, and
forbid label/comment `APPLIED` outcomes. Add a negative mutation that converts a
skipped issue operation to `APPLIED` and prove rejection.

### R2-02 — BLOCKER: positive status evidence is absent for deferred/in-progress

The plan schema has only `reasons.needsWork` and `reasons.blocked`
(`tests/Verification/backlog_issue_labels_256_test.py:56-61`). Any plan may add
`status:deferred` or `status:in-progress` without a decision/reference, execution
witness, or even non-empty reason and still pass. This contradicts
`specs/BACKLOG-ISSUE-LABELS-001.md:40-42` and the delta scenarios requiring a
positive current basis for every status.

Correction: define and validate a non-empty, status-keyed current basis for every
selected status (with source/reference identity where applicable), and add
negative cases for unexplained deferred and in-progress statuses.

### R2-03 — MAJOR: comment deduplication is self-attested, not evidenced

`existingExplanation` is a pair of booleans supplied by the plan, while the
before/after issue records contain no comment IDs, bodies/normalized keys, or
timestamps. `validate_evidence()` can only trust the boolean and count newly
applied comment operations; it cannot detect a duplicate of an existing
equivalent explanation, confirm append-only preservation, or distinguish a
changed reason. This does not meet the corrected contract's claim of evidence
validation for idempotent explanations.

Correction: retain a compact comment witness (stable ID plus normalized migration
reason key/hash and timestamp is sufficient), define equivalence deterministically,
and assert preservation plus zero duplicate writes. Add negative fixtures for an
equivalent existing explanation and for a changed reason.

### Rereview execution evidence

1. `python3 tests/Verification/backlog_issue_labels_256_test.py`
   returned exit 1: five tests, documentation RED, one external-evidence skip;
   synthetic complete and negative safety matrix passed.
2. `FMONITOR_ISSUE_LABELS_EVIDENCE=/Users/antropophag/.local/share/fmonitor-2/issue-256/gate1-before-red.json python3 tests/Verification/backlog_issue_labels_256_test.py`
   returned exit 1: five tests, external migration RED on absent exact after
   schema plus the independent documentation RED. This is sensitivity to the
   actual full before snapshot, not a GitHub mutation.
3. A read-only in-memory hostile-fixture probe passed `validate_evidence()` for
   both an `APPLIED` mutation on an issue declared skipped/closed and
   `status:deferred` without any basis (`UNSAFE_FIXTURES_ACCEPTED`), directly
   confirming R2-01 and R2-02.

No GitHub mutation and no local full suite were performed. The fresh package,
plan, corrected contract, test, verification input and prior review were read.
Gate 4 remains unauthorized until R2-01 and R2-02 are fixed; R2-03 must also be
resolved for the specified comment-idempotence scope, followed by regenerated
plan and independent rereview.

---

## Third rereview — 2026-09-24, package `20260924T162110Z-d115de3e92`

- Candidate source: `d24e0cc32ca7dfaf9f7a5ace3c5572233fe2a5fbd77aef42a80da0637f075ea0`
- Verification plan SHA-256: `eac891c5fcc8f7c4656d9ad0987221344ed379375339b8b22b0ccf9794a9216a`
- Verdict: **RETURNED**

### Prior finding dispositions

- R2-01: **fixed**. Lines 90-91 now require every issue operation for a
  `skippedClosedIssueNumbers` member to have outcome `SKIPPED_CLOSED`; the
  `skipped-but-applied` negative case rejects the previous hostile fixture.
- R2-02: **fixed**. The evidence schema and validator now require non-empty
  `reasons.deferred` and `reasons.inProgress`; both negative cases reject missing
  bases.
- R2-03: **partially fixed**. Before comments are ID/time/hash witnesses,
  append-only preservation is asserted, existing explanations must reference a
  before ID, and applied comment IDs/hashes bind to after witnesses. The remaining
  arbitrary-equivalence hole is R3-02.

### R3-01 — BLOCKER: final classification is not reconciled with the reviewed plan or operations

`validate_evidence()` checks only final type/prep cardinality at
`tests/Verification/backlog_issue_labels_256_test.py:106-123`. It never asserts
that the final schema labels equal the planned `type`, `prep`, and `statuses`, nor
that applied add/remove operations reconcile the before labels to after labels.
An in-memory hostile fixture changed the only after issue from planned
`type:product`/`prep:ready` to `type:tech-debt`/`prep:triage`, left the operation
journal claiming product/ready, and still passed `validate_evidence()`.

This allows evidence for a different classification than the one reviewed before
the irreversible writes. It also means the factual mutation journal is not
validated against observable GitHub state.

Correction: for each surviving planned issue assert exact equality between after
schema labels and `{plan.type, plan.prep, plan.statuses}` (using the reclassified
decision when `updatedAt` changed). Independently replay successful schema
add/remove operations from before and require the resulting schema-label set to
equal after. Add negative cases for plan/after disagreement and journal/after
disagreement. Define equivalent classification evidence for new issues discovered
after the before snapshot rather than trusting `classifiedNewIssueNumbers` alone.

### R3-02 — MAJOR: any existing comment ID can still be claimed as an equivalent explanation

Lines 59-61 establish only that `existingExplanation.needsWork/blocked` names
some before comment ID. They do not bind that witness's `bodySha256` to the
planned reason or to a reviewed normalized explanation key. A hostile fixture
changed the plan to `prep:needs-work`, supplied reason `gap`, pointed at unrelated
existing comment ID 5, and passed without a new comment.

Correction: add a deterministic expected explanation hash/key to the plan and
require the referenced before witness to match it, or store a reviewer-verifiable
mapping from reason key to exact comment ID/hash. Add a negative case where a
real but non-equivalent comment ID is claimed.

### Third-rereview evidence

- The ordinary focused command returned the intended documentation RED: five
  tests, one failure, one external-evidence skip; synthetic and negative matrices
  passed.
- The exact command with
  `/Users/antropophag/.local/share/fmonitor-2/issue-256/gate1-before-red.json`
  returned the documentation RED plus external exact-schema RED.
- A read-only in-memory probe printed
  `UNSAFE_FIXTURES_ACCEPTED result_disagrees_with_plan arbitrary_comment_witness`,
  directly establishing R3-01 and R3-02.

No GitHub mutation or local full suite was performed. Gate 4 remains
unauthorized; correct the two evidence bindings, regenerate the verification
plan, and submit the complete RED candidate for another independent rereview.

---

## Fourth rereview — 2026-09-24, package `20260924T162256Z-827e9bc31d`

- Candidate source: `1b687a0a1d34795391ec747b0637edb7a376986e774d7243e0c75d7b32f1eba6`
- Verification plan SHA-256: `637d94a6394d9d9df81d53584e14defb8c60dcd367e462f71ae96ee1ef946c38`
- Verdict: **RETURNED**

### Prior finding dispositions

- R3-01 plan/result half: **fixed**. Lines 123-127 require the exact after
  schema-label set to equal planned type, optional prep and statuses; the
  `after-disagrees-with-plan` hostile case is rejected.
- R3-01 operation-journal half: **open and blocking** as R4-01 below.
- R3-02: **fixed**. Existing explanation now binds comment ID and body hash to a
  before witness and to `explanationBodySha256`; applied comments bind result ID
  and hash to after and to an expected explanation hash. The hostile mismatched
  witness is rejected. Semantic selection of the expected explanation remains a
  reviewable plan judgment, which is appropriate for natural-language reasons.

### R4-01 — BLOCKER: applied label-operation journal is not reconciled to state

The previous correction explicitly required both plan/after reconciliation and
independent replay of successful add/remove operations. Only the first was added.
For non-comment issue operations, lines 105-106 merely assert that the named
label belongs to the schema. Outcome, before membership and operation kind are
never reconciled with the after state.

A read-only hostile fixture changed the two claimed applied operations from
`type:product`/`prep:ready` to `type:tech-debt`/`prep:triage`, while leaving the
reviewed plan and after state product/ready. `validate_evidence()` passed and
printed `UNSAFE_FIXTURE_ACCEPTED journal_disagrees_with_plan_and_after`.

Thus `operations` can materially misreport which irreversible GitHub writes were
performed even though the contract and delivery report treat it as the factual
mutation journal. This leaves retained-evidence and failure/audit semantics
unproven.

Correction: replay every successful `issue.label.add`/`issue.label.remove` from
the before schema-label set in append order (NOOP must preserve state; FAILED and
SKIPPED_CLOSED must not change it) and require the result to equal after. Add the
hostile journal/after disagreement to the negative matrix. The same reconciliation
should cover newly discovered issues using their classification baseline/witness.

### Fourth-rereview evidence

- Ordinary focused command: expected documentation RED; five tests, one failure,
  one external-evidence skip; synthetic and negative matrices passed.
- Exact actual-before evidence command: expected documentation plus missing-after
  schema RED.
- In-memory journal-disagreement probe: incorrectly accepted, establishing R4-01.

No GitHub mutation and no local full suite were performed. Gate 4 remains
unauthorized pending the single journal-reconciliation correction, regenerated
plan and independent rereview.

---

## Fifth rereview — 2026-09-24, package `20260924T162406Z-ebd9651294`

- Candidate source: `3acec34e758b8faf40e9b207289344b9d7b478172bbb8d3ec9011fc8eee8987f`
- Executable source: `1a212a2052f00f41d43289a3dc26eb3cc3ca3eaa7081433bbb2907232148f6e8`
- Verification plan SHA-256: `ba63e9e85b73728a633b8183ae299aff095e7bc7bc8569e7f07d0d7b8ee3c917`
- Verdict: **APPROVED**

### R4-01 disposition

**Fixed.** `validate_evidence()` now initializes replay from each before issue's
schema labels and from an empty set for each new issue, processes operations in
append order, changes replay state only for `APPLIED` label add/remove operations,
and exact-compares replay with every after issue's schema-label set. The negative
matrix includes `journal-disagrees-with-plan-and-after`; an independent hostile
probe of the former accepted fixture now prints `HOSTILE_JOURNAL_REJECTED`.

All prior G1, R2, R3 and R4 findings are closed. The canonical contract defines a
deterministic evidence envelope; the test covers exact catalogue metadata,
complete issue-set bounds, type/prep cardinality, status bases, unrelated state
preservation, PR/closed boundaries, optimistic reread/reclassification, comment
identity/hash and append-only behavior, failures, new-issue classification,
plan/result equality, operation replay and dry-rerun idempotence. The verification
input's mapping is now supportable and the refreshed planner retains CRITICAL
with independent Gate 3 and final review.

### Fifth-rereview evidence

- `python3 tests/Verification/backlog_issue_labels_256_test.py`: intended RED,
  five tests, one documentation failure and one external-evidence skip; synthetic
  complete evidence and the full negative matrix pass.
- Exact actual-before command with
  `/Users/antropophag/.local/share/fmonitor-2/issue-256/gate1-before-red.json`:
  intended external schema RED plus the independent documentation RED.
- `python3 tests/Verification/change_verification_001_test.py`: 18 tests, GREEN.
- Former hostile journal mismatch: rejected.
- `git diff --check`: GREEN.

This approval is limited to the reviewed contract, root-authored test and plan at
the exact source above. Gate 4 may proceed through the separately tasked executor
using per-issue rereads and the required retained envelope. The ordinary focused
test's external branch remains intentionally opt-in; implementation/final
evidence must record the exact `FMONITOR_ISSUE_LABELS_EVIDENCE` path and digest.
This review does not approve unknown future GitHub results, CI, publication,
merge or deployment. No GitHub mutation and no local full suite were performed
by the reviewer.
