# Gate 5 code review — CHECKLIST-OPERATION-REPLAY-001

## Verdict

**CHANGES_REQUESTED**

Independent reviewer: `/root/issue131_gate5` (separately tasked agent; not an
author of the specification, tests, or implementation).

Reviewed only the reconstructible immutable package
`20260915T073348Z-b9866ae8dc`: base
`0a286f3eccb5230d51e787baeeaea0acf7beb894`, patch SHA-256
`3b1be636b4fc52f0fe1973f7e27e6acbd54d8e3762c5d78506a208ef029f5c7a`,
candidate source
`264926d13def902199f4813350cc7d3dc5a9abbadd14fc471465e8c937d0b5c9`.
No `rapid-pilot` content was inspected.

## Blocking findings

1. **The early replay path bypasses existing type-specific validation.** In
   `app/InspectionEvidence/MariaDbYiiChecklistMutation.php:24`, `replay()` runs
   after only the common UUID/time/base/section checks and before `selected()`,
   `storePhoto()`, and the remaining operation-specific validation at lines
   46–82. This violates the normative ordering in
   `specs/CHECKLIST-OPERATION-REPLAY-001.md:56,68`, which requires an exact replay
   only after effective validation and preserves existing validation outcomes.

   Two concrete false-success cases follow from the candidate:

   - For an accepted `photo_uploaded`, a retry can reuse the same ID and declared
     SHA/MIME/size/name but supply missing or different bytes. `replayPayload()`
     compares only the declarations and returns `duplicate`; the existing byte,
     image, length, and hash validation in `storePhoto()` is never reached.
   - For an accepted `item_installers_changed` with `[7001, 7002]`, a retry with
     `[7001, 7001, 7002]` is normalized by `replayPayload()` to the accepted set
     and returns `duplicate`, while the existing `selected()` admission rejects
     duplicate input identities.

   Preserve the no-write replay path, but perform the applicable side-effect-free
   validation before classifying the ID collision (or make the comparator enforce
   exactly the same admission rules). The photo validation must validate supplied
   content without writing a file.

2. **The executable verifier cannot catch this regression.** The exact photo
   replay in `tests/Yii2/yii2_checklist_operation_replay_001_test.php` always
   supplies the original valid bytes, and the installer replay changes only set
   order without duplicate identities. Add public-HTTP assertions that malformed
   retries retain the existing rejection envelope and zero-write/file behavior.
   Because this changes an approved test, the delivery process requires plan
   recomputation and a fresh Gate 2/Gate 3 cycle before another Gate 5 review.

## Conformance assessment

Apart from the finding above, the production delta is bounded to the existing
native Yii2 checklist owner. The same `replay()` comparator is called by the
ordinary duplicate and post-rollback integrity-recovery paths; it compares
case/type/actor/device/section/item and typed stored payload, fails closed for
malformed stored JSON/shape, returns no foreign revision on conflict, and leaves
the canonical `item_completed` implementation unchanged. The photo-race cleanup
is limited to a newly created unreferenced content-addressed file.

The approved Gate 3 record maps A–I and the packaged exact-source command
`php tests/Yii2/yii2_checklist_operation_replay_001_test.php` is recorded GREEN.
That GREEN does not cover the validation bypass above. The package does not
record exact-source CI; consistent with the delivery tasks, CI and PR remain
pending and are not represented here as GREEN or complete.

## Correction review — package 20260915T074633Z-ee08431491

### Fresh verdict

**APPROVED**

Reviewed independently using only the corrected immutable package and its
reconstructed snapshot: base
`0a286f3eccb5230d51e787baeeaea0acf7beb894`, patch SHA-256
`2fab2deb68dfcbd2de3a9139d24069bc7e01b48a3c7464d546e923accfc8b039`,
candidate source
`f86e1ad062cbd1fcbf6a878b5303f0b1a4cccb63e2f04460cceb703ef3ce2436`,
and executable source
`ee3174d16372a41de04b3ea0cbabcf538af513f953f7961fd0ca9892a445ecc5`.
No `rapid-pilot` content was inspected.

### Prior findings disposition

1. **Resolved — type-specific validation is preserved without replay writes.**
   Both the ordinary early lookup and post-rollback integrity recovery call the
   same `replay(..., $bytes)` method. After context and typed payload equivalence
   is established, `replayValid()` now rejects duplicate installer identities
   and validates photo bytes, detected MIME, byte count, lowercase SHA/hash,
   name, and size bounds without touching storage. A valid reordered unique
   installer set and a valid exact photo/body retry still return the stored
   `duplicate` revision. A genuinely different context or meaningful payload is
   still classified as `conflict` before exact-replay validation.

2. **Resolved — the restarted verifier is regression-sensitive.** Through the
   public Yii2 HTTP seams it now requires duplicate installer identities to
   retain HTTP `422 rejected`, a valid but different PNG body under matching
   declarations to retain HTTP `422 rejected`, and an empty photo body to retain
   its transport HTTP `413 rejected`; every case independently requires an
   unchanged database/private-file snapshot. It retains the reordered unique
   installer and valid photo exact-retry assertions. The fresh Gate 3 restart is
   independently `APPROVED` and records intended RED specifically on the two
   prior false-success paths.

### Final assessment and evidence

The candidate implements the five typed replay identities, uses the same policy
for normal and exception/race recovery, fails closed for malformed stored
payloads, preserves authorization-before-disclosure, produces zero writes for
replay/conflict/rejection, and keeps `item_completed`/#130 behavior regression
only. A–I remain covered, including equivalent and conflicting real-DB HTTP
races and accepted-revision assertions. The production change remains confined
to `app/InspectionEvidence/MariaDbYiiChecklistMutation.php`; there is no schema,
frontend, offline, new-owner, or unrelated refactor scope growth.

The package records exact-source GREEN for
`php tests/Yii2/yii2_checklist_operation_replay_001_test.php`, with no missing
acceptance tests. Exact-source GitHub CI and PR publication remain later delivery
steps and are not represented by this approval as already GREEN or complete.
