# Gate 3 test review — CHECKLIST-OPERATION-REPLAY-001

## Initial verdict

**CHANGES_REQUESTED**

Reviewed independently from prepared reviewer package
`20260915T064645Z-6b36242edc` at candidate source
`5c077f5bc6405e45dae11075d4520eccae1291b65e1235735f1a3869a345b648`.
The package records the focused verifier as `INTENDED_RED`; the failure reaches
the replay behavior and reports the expected false duplicate outcomes rather
than setup, authentication, CSRF, or JSON failures.

## Blocking findings

1. **F/G do not cross the specified public HTTP seam and do not assert the HTTP
   contract.** In `corRace()`, workers invoke
   `MariaDbYiiChecklist::accept(4512, 73, ...)` directly. The contract fixes the
   seam as the existing Yii2 checklist operation/photo HTTP endpoints, and G
   requires the losing request to return HTTP `409 conflict`. The worker only
   returns the domain result and the parent only compares `status` strings.
   Consequently these tests cannot detect regressions in HTTP mapping,
   authentication/authorization ordering, or the photo endpoint's request
   handling on the exception-recovery path. Run the two contenders through
   separate authenticated HTTP execution contexts and assert both HTTP status
   and body status.

2. **F/G do not enforce the required no-partial-write invariant.** Both race
   cases assert only that `fm2_checklist_operations` increased by one. They do
   not independently assert revision count/content, photo rows, installer facts,
   or private-file hashes. This is especially material for conflicting
   `photo_uploaded`: a losing contender may leave its different content file or
   photo/revision side effect while the operation-row count still passes. Capture
   the full persistence/private-file snapshot before each race and assert the
   precise single accepted delta (one operation, one revision, one photo/fact and
   only the winner's content), with no loser artifacts.

3. **A does not prove normalized installer-set equivalence.** The
   `item_installers_changed` exact retry uses the singleton `[7002]` both times;
   reversing the outer associative command only proves JSON key-order
   insensitivity. The normative fingerprint is a unique sorted installer set,
   and acceptance A explicitly calls out different installer order. Use a
   multi-installer accepted command and retry it in another order (and, if input
   validation permits duplicate IDs, prove duplicate elimination) while asserting
   the same accepted revision and zero writes.

## Acceptance mapping assessment

| Matrix | Assessment |
|---|---|
| A | Partial: all five scoped types have an accepted/exact-retry path and full sequential snapshot; installer-set order normalization is not exercised. |
| B | Covered through the HTTP operation endpoint with full no-write snapshot. |
| C | Covered through the HTTP operation endpoint with full no-write snapshot. |
| D | Covered for installer set, retraction original/reason, upload SHA/MIME/size/name, revoked photo/reason, and completed section; sequential attempts assert full no-write snapshots. |
| E | Covered for actor, device, and no-read actor. The no-read assertion requires the exact safe body and a full no-write snapshot, preserving #130 disclosure semantics. |
| F | Partial/blocking: a real disposable DB and two processes are used, but the workers bypass HTTP; only operation count is audited. |
| G | Partial/blocking: the intended RED exposes false `duplicate`, but HTTP `409` and loser revision/photo/file cleanup are not tested. |
| H | Covered: `item_completed` exact retry and changed normalized command are exercised through the existing HTTP helper with no-write snapshots. |
| I | Covered in the new public-seam test by an exact safe `403 rejected` body and no-write snapshot. The delivery record separately reports the broader existing journey as an unresolved baseline failure, so it must not be represented as GREEN. |

## RED quality and scope

- The recorded RED is intended and sensitive to ordinary type/device/payload,
  object, actor collisions and to a conflicting concurrent outcome.
- Healthy accepts and exact retries pass before the aggregate intended failure,
  demonstrating that setup reaches replay admission.
- The race scheduling uses a real unique insert collision candidate, but a
  simultaneous file barrier plus `BEFORE INSERT ... SLEEP(0.4)` does not itself
  prove both workers crossed the initial absence lookup. The corrected HTTP race
  test should include an observable synchronization/assertion that fails if the
  loser took the ordinary early-duplicate path; otherwise Gate 3 cannot establish
  exception-path sensitivity.
- No schema/frontend/service-worker/rapid-pilot or unrelated redesign appears in
  the reviewed test/spec snapshot.

Gate 3 may be resubmitted after the three blocking test corrections and a fresh
prepared package with executable intended-RED evidence.

## Correction review — package 20260915T065537Z-eb8d4cc196

Reviewed candidate source
`f990345a14c360a8277f6d57b19b4e8975abb392790356002dd48178993c07ad`
using only the corrected package snapshot and its exact-source `INTENDED_RED`
record.

### Prior findings disposition

1. **Resolved — public HTTP race seam and HTTP contract.** Each contender is now
   a separate process issuing an authenticated, CSRF-qualified request to the
   Yii2 photo endpoint. F asserts HTTP `200 accepted` plus HTTP `200 duplicate`;
   G asserts HTTP `200 accepted` plus HTTP `409 conflict`.
2. **Resolved — race persistence/private-file audit.** Both races capture a full
   pre/post fixture-and-file snapshot and require exactly one operation, one
   revision advance, one photo, one private file, and no changes to unrelated
   facts. Distinct conflicting photo bytes make an orphan loser file observable
   as an extra private-file entry.
3. **Resolved — installer normalization.** The accepted installer set
   `[7002, 7001]` is retried as `[7001, 7002]`, while outer JSON key order also
   changes, and the sequential snapshot must remain identical.
4. **Resolved — exception-path sensitivity.** The trigger now sleeps on
   `fm2_checklist_photos`, after the operation's early absence lookup and
   operation insert. The first transaction therefore holds the operation-ID
   uniqueness state while the second request, already admitted through the
   public endpoint, collides at its operation insert. This distinguishes the
   integrity recovery path from a post-commit ordinary early duplicate.

### Remaining blocking finding

1. **A/F do not assert that replay returns the originally accepted revision.**
   Section 4.1 and acceptance A require the exact replay response to contain the
   original `accepted_revision`; F likewise requires the race loser to replay
   that accepted result. `corReplay()` validates only HTTP/status and the
   no-write snapshot, discarding the result returned by `corAccepted()`. The race
   checks collect each response's `revision` but compare only HTTP/status arrays.
   A faulty implementation could return `duplicate` with a missing, stale, or
   unrelated revision and this verifier would pass. Retain the accepted revision
   for each of the five scoped operation retries and assert exact equality; in F,
   assert that the accepted and duplicate response revisions are both the single
   persisted race revision.

## Final Gate 3 verdict

**CHANGES_REQUESTED**

The corrected intended RED is healthy, public-seam, real-DB, race-sensitive and
now covers the prior findings. Gate 3 remains blocked only on the observable
existing-result/revision assertion required by A and F. No production review or
rapid-pilot inspection was performed.

## Final correction review — package 20260915T065903Z-818a1e1d80

Reviewed candidate source
`33b00f9f35febdb311c88ba985d0f3f46207c889f57943b61f7bd4ec54056241`
and exact-source executable digest
`4c54d55484b3d5ff26b59614e17a56fb6048ec3ea3b1108651de5cab374a5f98`.

The sole remaining finding is resolved:

- Every sequential exact replay now selects the single persisted operation by
  `client_operation_id` and requires the HTTP `duplicate` response revision to
  equal that row's `accepted_revision`. Because `corReplay()` is used for
  `item_completed` and each of the five scoped types, the assertion covers all
  healthy retry calls in the verifier.
- The equivalent HTTP race now requires both collected response revisions to be
  exactly `raceBase + 1`, in addition to the existing accepted/duplicate HTTP
  outcomes and single persisted operation/revision/photo/file delta.
- The package's `INTENDED_RED` reaches and passes these revision assertions; it
  continues to fail on the expected false duplicate conflicts, including the
  conflicting HTTP race. Thus the correction did not replace behavioral RED
  with a setup or assertion-shape failure.

## Complete Gate 3 verdict

**APPROVED**

All previously recorded findings are resolved. The executable specification is
adequate for implementation: it exercises A–I through the required public seam
and disposable real database, is sensitive to normal and integrity-recovery
duplicates, independently checks response and persistence/private-file effects,
and preserves the `item_completed` and #130 regression expectations. No
production implementation or rapid-pilot content was inspected.

## Fresh Gate 3 restart — package 20260915T073934Z-d6189068b6

Gate 3 was restarted after the later validation-bypass coverage finding. This
review used only immutable package candidate source
`9b3b3f117b4f6a41128081bf8d17d303179e52a63e0d88355c8eb6610b43b505`,
executable digest
`52cb3dd028addb3bdc60f7bc4e7d18e7ddfee5e07e6ef12dec3b7463e6c99e65`,
its snapshot, plan, and exact-source `INTENDED_RED` evidence.

### Validation-before-replay coverage

- A reused accepted `item_installers_changed` ID with duplicate installer
  identities `[7001, 7001, 7002]` is sent through the Yii2 operations endpoint.
  The test requires the existing validation outcome HTTP `422 rejected` and an
  unchanged complete fixture/private-file snapshot.
- A reused accepted `photo_uploaded` ID and unchanged declared metadata is sent
  through the Yii2 photo endpoint with a different valid generated PNG body.
  The test requires HTTP `422 rejected` and an unchanged complete
  fixture/private-file snapshot. This specifically prevents replay comparison
  from bypassing content/hash validation merely because stored and declared
  metadata match.
- The same accepted photo command with an empty body separately requires the
  existing transport outcome HTTP `413 rejected` and the same no-write audit.
  It is not collapsed into domain replay/conflict behavior.

The recorded RED fails only the first two new behavioral assertions: each
incorrectly receives HTTP `200 duplicate`. The empty-body `413`, healthy
prerequisites, prior exact replays, race setup, authorization, and response
decoding all proceed, proving that RED reaches the duplicate admission branch
rather than failing at setup, CSRF/authentication, malformed JSON, or transport.

The executable additions are bounded to the discovered validation ordering gap;
the verification plan remains the existing CRITICAL A–I public-seam/disposable
MariaDB acceptance and focused consumers. They add no schema, client/offline,
harness, architecture, or unrelated checklist requirement.

## Fresh complete Gate 3 verdict

**APPROVED**

The intended RED is executable, branch-sensitive, independently audits response
and persistence, and is sufficient to prevent the identified validation bypass
while retaining the established exact replay, race, `item_completed`, and #130
coverage. No production code or rapid-pilot content was inspected in this
restart.
