# QUALITY-GRAPH-GOVERNANCE-001 v0.4 — final independent Gate 1 re-review

Date: 2026-09-02  
Reviewer: `agent:/root/qg_gate1_review`  
Reviewer role: separately tasked Gate 1 reviewer; did not author the specification, owner approval, OpenSpec artifacts, tests, or implementation  
Specification: `specs/QUALITY-GRAPH-GOVERNANCE-001.md` v0.4  
Owner approval: `docs/operations/quality-graph-governance-v04-owner-approval-2026-09-02.md`  
Prior review: `docs/operations/quality-graph-governance-gate1-rereview-v3.md`  
Verdict: **CHANGES_REQUESTED**

## Previous blockers

- **Self-containing SHA:** resolved in the gate model. RED, test-review, GREEN/implementation and code-review commits are derived from Git; no evidence metadata contains the SHA of its own containing commit.
- **Strict chronology:** resolved. `base < RED < test-review < GREEN/implementation < code-review ≤ HEAD` prohibits equal, reversed and unrelated gate commits.
- **Specification author:** resolved through authoritative `kind:"spec"` metadata and required receipt equality.
- **Representative PR, immutable supersession and input boundary:** remain resolved.
- **Owner authority:** explicit v0.4 approval is sufficient and retains all stated exclusions.
- **OpenSpec coherence:** the delta, design and tasks now materially use Git-derived gate commits, exact implementation review, immutable receipt chains, actual PR phase A and the constrained evidence envelope.

## Remaining blocking findings

1. **The derived RED/GREEN commit rule contradicts the receipt-to-metadata rule.** The receipt schema still requires `artifacts.red.commit` and `artifacts.green.commit`. RED and GREEN metadata intentionally contain no self-containing commit, while the normative paragraph says: “A receipt cannot supply ... commit absent from artifact metadata.” The receipt therefore cannot be valid under both requirements. State the explicit exception: those two receipt commits are checker-derived, MUST equal the unique first-introduction commits, and are not copied from artifact metadata. Alternatively remove them from the receipt and make them purely derived values. This is acceptance-affecting because a test cannot independently decide whether the otherwise valid example must pass or fail.

2. **The Git-derived test set is not complete for executable test changes.** It includes only changed `tests/**/*_test.php`. This repository also has executable/support test artifacts under `tests/Support/**`, `tests/InstallationProcess/support/**`, `tests/bootstrap.php`, and `*_red.php`/other PHP scripts. A RED test can depend on a newly changed helper or fixture that is omitted from the receipt, independent test review and GREEN hash binding while still satisfying the stated equality. Derive the set from all changed regular paths under `tests/**` (or define an equally fail-closed dependency closure); distinguish entry-point tests from support files in metadata if useful. The same completeness principle should be stated generically for implementation changes: derive all changed regular paths in the slice interval, then allow only an explicit exhaustive evidence/status exclusion set, rather than silently ignoring an out-of-scope executable path.

## Non-blocking clarification

The phrase `git log --diff-filter=A` is safe only if “unique first commit containing the exact blob” is path-specific and any later modification makes the current hash differ from the addition commit and fail. The implementation/tests should freeze that interpretation rather than searching for an identical blob at any unrelated path.

## Conditions for approval

Resolve the receipt/derived-commit contradiction and make changed test/implementation file-set completeness genuinely fail closed. Reconcile the same exact rules in the OpenSpec delta/design/tasks and obtain owner approval of the revised executable version. No v0.4 RED should be treated as Gate-1-approved.
