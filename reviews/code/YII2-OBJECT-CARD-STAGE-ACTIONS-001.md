# Independent final review — YII2-OBJECT-CARD-STAGE-ACTIONS-001

- Date: `2026-09-22`
- Reviewer: separately tasked agent `/root/gate5_object_card`
- Independence: the reviewer authored none of the specification, OpenSpec artifacts, tests, production implementation, or supplied evidence
- Base: `3c242f34e8f30986f1b8354c4ef947a4c63936dc`
- Commit: `0bc9bd3964e4d9bfcc8653da3968f598c481ceac`
- Exact candidate source: `42db0921985836c651a9eb2bc62050eef7d5d24babbf97e9e564b49af41dc485`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T004806Z-0948f96ca5/package.json`
- Verdict: **APPROVED**

The prepared package, all required context, normative and delta specifications,
delivery authorization, prior Gate 3 history, complete production/test diff, focused
evidence, and adjacent object-card boundaries were reviewed. This review changes
only this review record.

## Findings

No blocking or non-blocking correctness findings remain.

## Spec and production conformance

- `MariaDbYiiObjectCardProjection` derives `pendingComposition` only from the latest
  canonical selection without an accepted current revision, validates its immutable
  member/engineer snapshots, and keeps it separate from the applied application and
  `confirmedOriginal`. An opened card with a later pending selection therefore keeps
  the applied crew, original, status, and checklist basis.
- The controller exposes existing access decisions rather than creating authority:
  `objects.read` still guards the card; selection, original upload, opening,
  checklist read, PTO, and declaration use their existing capability/access seams.
  Restricted actors retain the informative state while command forms, links, and
  URLs are omitted.
- The view has exactly one `.fm2-next-action` and orders exceptional/terminal,
  documentary, working, ready-to-open, pending-original, and no-selection branches
  as required. Accepted/applied documents remain truthful; a pending selection does
  not synthesize a document row or readiness fact.
- The diff introduces no route, mutation, schema, status, permission, task, file, or
  event owner. Object-details editing/history, completion forms, document history,
  checklist links, and construction-control/queue consumers remain on their existing
  boundaries.

## Authorization and authorship

The delivery record documents owner authorization on `2026-09-22` through PR merge
without deployment. Root authored the contract/tests and the separately identified
`gpt-5.6-sol/low` executor authored production. The Gate 3 and final reviewers are
independent of both. This satisfies the declared `CRITICAL` lifecycle authorship and
review separation.

## Post-Gate 3 test delta

Gate 3 approved source `613d6153...`. The current
`yii2_object_card_stage_actions_001_test.php` remains byte-identical to its approved
digest `e67b98e0...`. The only later acceptance-test content delta is in
`yii2_object_card_stage_matrix_001_test.php`: its helper extracts the single
`.fm2-next-action` section and scopes forbidden competing-CTA assertions to that
section instead of searching the whole response.

That correction matches the normative scope in contract sections 4 and 6: the
forbidden commands are forbidden as competing **primary actions**, while section 5
explicitly preserves adjacent completion forms and authorized view links. Positive
heading/command assertions still target the primary block, and whole-response
cardinality still proves exactly one `.fm2-next-action`. The delta removes false
conflicts with preserved secondary controls; it does not weaken the approved stage
or authorization matrix. **Gate 3 remains APPROVED for this delta.**

## Verification and adjacent boundaries

- Package-bound exact-source records report GREEN for both executable acceptance
  suites in the isolated integration profile, bound to candidate source
  `42db0921...`, executable source `f20dba24...`, and environment `824c2a50...`.
- The delivery record reports the construction-control queue, object-card browser
  presentation, object-details editor/history, documentary HTTP, object-card
  auth/read, object queue, governance, architecture, and PHP-lint focused checks
  GREEN, with the already disclosed environment-only nested-harness qualification.
- A reviewer-side direct retry confirmed PHP syntax for all three changed production
  files. Its subsequent unprofiled integration run stopped at the fixture's existing
  queue CSRF source with HTTP 503, so it supplies no replacement regression result
  and does not override the package-bound isolated GREEN evidence.
- Canonical full local `make test` / `make verify` was not run, as required. The
  delivery record leaves exact-source CI after this final review; this approval does
  not report CI GREEN or authorize deployment.

## Standards

No documented-standard violation was found. Two minor duplication opportunities
are accepted as non-blocking: pending/confirmed selection snapshot mapping in the
projection and repeated pending roster/document markup in the view. Both are small,
local, follow existing array-shaped projection/view conventions, and do not obscure
the state or authorization seams enough to justify expanding this slice.

## Verdict

**APPROVED.** The production implementation conforms to
`YII2-OBJECT-CARD-STAGE-ACTIONS-001`, preserves applied-versus-pending and
authorization semantics, and has adequate exact-source focused evidence for final
review. Gate 3 remains approved after the scoped assertion correction. Exact-source
CI and the remaining PR/merge workflow are still pending and must be recorded
separately; deployment is outside authorization.

---

# CI correction re-review — exact source `7119b168`

- Date: `2026-09-22`
- Reviewer: separately tasked agent `/root/gate5_object_card`
- Independence: unchanged; the reviewer authored none of the correction, tests, or evidence
- Previous reviewed commit: `0bc9bd3964e4d9bfcc8653da3968f598c481ceac`
- Corrected commit: `bd5a0819b9a784d6ba9c11b687e07c1c025d3cea`
- Exact corrected source: `7119b1682926e5300b9fa6798e691d6ba48acefaf5632ac83013dfe5f43d8dcd`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T011956Z-1917fbdde0/package.json`
- Verdict: **APPROVED**

The corrected package, exact `0bc9bd39..bd5a0819` delta, CI failure inventory,
focused correction results, inherited contract, and the prior Gate 3/final review
history were reviewed. This addendum supersedes the earlier final verdict only for
the corrected exact source.

## CI failure disposition

First PR run `35673748261` had primary failures in `e2e` and `Integration (1/2)`;
`verify` and `Quality Graph` were downstream aggregate failures. The complete
documented `REGRESSION_FAILURE` inventory is:

1. `tests/Yii2/yii2_preopening_browser_001_test.php`
2. `tests/Yii2/yii2_inspection_journey_001_test.php`
3. `tests/Yii2/yii2_object_card_stage_actions_001_test.php`

All three failures have one product cause: commit `0bc9bd39` intersected the
authoritative checklist-owner access result with a newly added
`processCap('checklist.read')` presentation gate. That narrowed established access
outside this slice and contradicted the contract's inheritance of existing
checklist authorization. The correction restores `canReadChecklist` directly from
`MariaDbYiiChecklist::access(...)[read]`; it does not grant access, bypass the owner,
or change checklist mutation authorization.

## Production and test delta

The production delta is exactly the one-line removal of that competing gate in
`ObjectCardController`. Projection, applied-versus-pending semantics, view priority,
routes, writers, schemas, and all other authorization decisions are unchanged.

The stage-matrix test removes the actor-95 negative checklist case. That actor is
readable according to the existing checklist owner, so the removed assertion encoded
a false authority assumption introduced with the defect. The positive working-stage
case still proves that readable access exposes the checklist CTA, while the inherited
preopening/inspection tests protect the authoritative access behavior. Contract
section 4 requires the CTA “при checklist read”; it does not introduce a new exact
process capability. Removing the invalid negative therefore restores, rather than
weakens, the reviewed contract oracle. The prior Gate 3 approval remains valid for
the corrected test delta.

## Corrected-source evidence and status

- Package-bound `yii2_object_card_stage_actions_001_test.php` and
  `yii2_object_card_stage_matrix_001_test.php` records are GREEN on exact source
  `7119b168...`, executable source `83be2ac7...`, isolated environment
  `824c2a50...`.
- The delivery record reports all three formerly failing tests plus the stage matrix
  GREEN in the appropriate isolated browser/integration profiles after correction.
- Reviewer PHP lint of the corrected controller is GREEN.
- No local canonical full suite was run. Replacement exact-source CI run
  `35675323416` for commit `bd5a0819` is currently pending/UNKNOWN. This review does
  not represent it as GREEN; merge readiness remains false until that run and the
  normal admission checks complete.

## Correction verdict

**APPROVED.** The correction is minimal, returns checklist presentation authority to
its established owner, fully explains the first CI run's failure inventory, and
preserves the normative stage-action behavior and all previously approved
applied-versus-pending semantics. Final review is approved for exact source
`7119b1682926e5300b9fa6798e691d6ba48acefaf5632ac83013dfe5f43d8dcd`.
Exact-source CI remains pending and is not waived.

---

# Current-main merge re-review — exact source `9fa2063d`

- Date: `2026-09-22`
- Reviewer: separately tasked agent `/root/gate5_object_card`
- Current-main parent: `a691c17b8639dec8e754933c889d0add6ce5538b` (merged PR #230 hotfix)
- Feature parent lineage: corrected commit `bd5a0819b9a784d6ba9c11b687e07c1c025d3cea`
- Merge commit: `36ad2f144f041568398c73e66ccabaa86b9c74fb`
- Exact merge source: `9fa2063d5f206e14ea79dca4fd79c310e64caad9fc85257b572af87d96037629`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T020713Z-6b95efeb23/package.json`
- Verdict: **APPROVED**

The merge commit and both parent deltas were reviewed at the shared object-card
boundary before push. No semantic conflict or merge-created behavior was found.

## Preservation of the stage-action change

The merge-source copies of the projection, corrected controller, stage-actions test,
and stage-matrix test are byte-for-byte identical to corrected feature commit
`bd5a0819`. In particular:

- latest unaccepted selection remains a separate `pendingComposition`;
- applied crew/original/checklist facts remain authoritative after opening;
- the single-primary-action priority and capability-dependent controls are intact;
- checklist presentation still uses the authoritative checklist-owner access result,
  without reintroducing the accidental competing `checklist.read` gate;
- the reviewed test-delta semantics remain unchanged.

The only feature-owned shared file changed relative to `bd5a0819` is
`app/YiiRuntime/Views/object-card.php`, and its additional eight-line delta is exactly
PR #230's object-details hotfix region above the workspace. It does not overlap the
stage-action branches, team roster, or document presentation.

## Preservation of current main / PR #230

The merge-source copies of `preopening.js`, both object-details browser helpers, the
object-details browser test, and the production-web cutover contract are byte-for-byte
identical to current main `a691c17b`. The merged object-card view retains PR #230's
separate persisted baseline, validation redisplay values, legacy catalogue option
handling, and `data-object-details-baseline` attribute while also retaining the
stage-action markup below it. Thus changed-field filtering, validation retry,
cancel/reset behavior, legacy-speed compatibility, and history behavior are not
reverted or coupled to stage selection.

## Exact merge-source evidence

- Package-bound stage-actions and stage-matrix records are GREEN on exact source
  `9fa2063d...`, executable source `30af09f9...`, and isolated environment
  `824c2a50...`.
- The merge verification reports the PR #230 object-details browser flow GREEN on
  the same merged checkout.
- Reviewer checks are GREEN for PHP syntax of the merged object-card view and
  controller, JavaScript syntax of `preopening.js`, and `git diff --check` against
  current main.
- No canonical full local suite was run. This merge review does not infer a future
  exact-source CI result or deployment authorization.

## Merge verdict

**APPROVED.** Exact merge source
`9fa2063d5f206e14ea79dca4fd79c310e64caad9fc85257b572af87d96037629`
preserves both the corrected object-card stage-action change and current main's PR
#230 object-details hotfix without semantic conflict. The prior Gate 3 and final
decisions remain valid for the merged source; push/CI/admission must still use this
exact source.
