# Assignment-order original production safe-log amendment — independent Gate 1 review

Date: 2026-09-05  
Reviewer: separately tasked agent `/root/safe_log_gate1_review`  
Reviewer independence: did not author the owner decision, planning artifacts,
executable specification, tests, or production implementation; this review
record is the reviewer's only repository change  
Reviewed commit: `5c9a530286e8d0bad4d245b14052da89ae246ea6`  
Verdict: **CHANGES_REQUESTED**

## Exact reviewed evidence

```text
09fbfb8d9a5405989ad40150d011b557d8fea1ba030530fbf68ce06c3a710abb  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
482fb11555e9f10968a2485c2f22e863543c67fa78c41cd8148ce3f1b0e4ce09  openspec/changes/replace-pilot-registration-with-original-upload/design.md
42a03479da33ecc99f2b6e6f76600480a723e7a8c8e25296d33285584d8d3c88  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
dd4c5f05ddfadfcff57a8f4303165a806a43a4b339227407ceac6a37b8d7d2b2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
3da95e342c0f49d96cfd2d91aca12ba6eecdba3fb75777333fab04e0b1ae5ec6  docs/operations/assignment-order-original-production-safe-log-owner-resolution-2026-09-05.md
```

The four OpenSpec artifacts and owner-resolution bytes above are the exact
versions at the reviewed commit. The earlier blocker remains unchanged
historical evidence at
`docs/operations/assignment-order-original-production-safe-log-owner-blocker-2026-09-05.md`.

The active normative executable specification inspected for coherence was:

```text
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

## Owner traceability and planning assessment

The four amended OpenSpec artifacts consistently carry the owner's approved
production-only decision. They require a third mandatory production config
field `safeLogFile`; an already existing canonical, configured-path
non-symlink regular file; current-effective-user ownership; exact mode `0600`;
no create, repair, replacement, truncation, chmod or chown; validation before
database and private-storage access; a real append-only cleanup/release logger;
and one fixed construction failure without path, secret or underlying-error
leakage. Proposal and design keep this field distinct from the previously
approved worker/evidence-reader serializable config contract. Tasks preserve
Gate ordering and prohibit test/production work until Gate 1 and independent
Gate 3 have passed.

The change remains on the existing assignment-order-original application
construction seam. It does not move domain facts into a screen, HTTP, worker,
cron or `rapid-pilot/`, does not alter composition/opening behavior, and does
not edit the historical blocker. The valid and invalid scenarios are
observable at factory construction and through real cleanup/release diagnostic
append behavior.

## Blocking finding

### The active executable specification still mandates the superseded two-field/no-op production contract

`docs/development-process.md` requires the normative executable behavior to be
approved at Gate 1; OpenSpec explicitly cannot replace that specification. At
the reviewed commit, `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` still
declares `AssignmentOrderOriginalProductionConfig::__construct` with exactly
`privateStorageRoot` and `tablePrefix`, with no `safeLogFile`. The same active
specification also states that the production factory binds inert/no-op
observer implementations. Those normative statements permit and require a
production composition that the new owner-approved OpenSpec requirement now
forbids.

This is not merely a future test omission: a RED author consulting the stable
executable specification would be required to call a two-field public config
and accept an inert production safe-log, while a RED author consulting the
delta specification would be required to call a three-field config and observe
real append-only diagnostics. Both contracts cannot be approved
simultaneously, and Gate 1's observable public seam is therefore ambiguous.

Required correction: amend the active executable specification coherently and
append-only so its production config signature, factory bindings, validation
ordering, path/type/owner/mode/no-repair rules, fixed redacted failure, and real
append-only cleanup/release log behavior agree with the exact owner-approved
OpenSpec contract. Preserve the separate worker/evidence-reader contract and
all existing exact cleanup/release event formats. Then obtain a fresh
independent Gate 1 review over the new exact executable-spec and four-artifact
hash set before changing executable tests or production.

## Verification and gate consequence

The reviewed commit changes only the four named OpenSpec artifacts and the new
owner-resolution record; no executable specification, test, or production file
is part of its diff. The owner-resolution's four recorded OpenSpec SHA-256
values independently match the reviewed bytes.

Gate 1 does not pass for commit
`5c9a530286e8d0bad4d245b14052da89ae246ea6`. Task 1.34 remains open, and Gate 2,
Gate 3, and production implementation for this production safe-log amendment
remain prohibited. This review does not reopen or modify the separately
approved worker/evidence-reader safe-log contract, parser work, or historical
evidence.
