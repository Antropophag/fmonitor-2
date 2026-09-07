# Manual-pilot confirmed-original compound opening — independent review

- Reviewer: Codex agent `/root/auth_review`, independently tasked; did not author the reviewed command, compound owner, application-operation refactor, or tests.
- Review date: `2026-09-07` (`Europe/Moscow`).
- Review base / current `HEAD`: `c9bd432afbc23497d45b1355f2408db271672191`.
- Exclusion: `app/AssignmentOrderComposition/MariaDbOriginalOpening.php` was authored by this reviewer and is not included in this verdict; root reviews that prerequisite separately.
- Test verdict: `APPROVED`.
- Code verdict: `APPROVED` for the exact artifacts below.

## Exact reviewed identities

```text
8c57e4df5ef8181a53d4d17591a58d180b383f4ee13c5caafd3c05cabb840a8d  app/AssignmentOrderComposition/OpenConfirmedOriginalCommand.php
2de896337b0edff334bbe06f9abc1bf22bbcbe4d7ff1ad0d18633ed4336b57a2  app/AssignmentOrderComposition/ProductionConfirmedOriginalOpeningFactory.php
f149e6e3e31ba5b194e6d9978ba4e3b4c93c095a0715ed1dd9472e011a7c2486  app/AssignmentOrderComposition/MariaDbConfirmedOriginalOpening.php
e2b265690e653e648fb06ca8478e3189b91cbe113685babc8417126a90af907b  app/AssignmentOrderComposition/MariaDbAssignmentOrderApplicationOperation.php
c96a1c615f695039d2d970513f29f63ea73d54fc7e8fdb2ccea29bd1a7a77b49  app/AssignmentOrderComposition/MariaDbAssignmentOrderApplication.php
3fcc7228cbca365f6bd00625df523b3510b6cf528720ab2d87b8971c788d097c  tests/AssignmentOrderComposition/confirmed_original_opening_001_test.php
7885bf3d625c4da13f9db68419d2cc679b8de81d8303dad04924c72ceb2c143c  tests/AssignmentOrderComposition/confirmed_original_opening_http_001_test.php
```

Exact diff SHA-256:

```text
ba3629cc906cd95981df5989341a84637d072b65a42d22b05f40c81bade9dfa9  MariaDbAssignmentOrderApplication.php diff
65914e006bdf1b98926a4b32b6db9c4ac6e6381c1b83607ad609b063a7a10b5d  OpenConfirmedOriginalCommand.php new-file diff
387e286d3f3f45b386fc54e5b5cb65e0378383fd6eb4568544b121e863962054  ProductionConfirmedOriginalOpeningFactory.php new-file diff
bba38a9b6b328ed6d2a96d45dc741e6fa4e8e1e23606b1ee0585f63f124b28db  MariaDbConfirmedOriginalOpening.php new-file diff
002759a0c7a7a09c8ec5aedd84b439d1de7111446606994ce9bb426a5b1e8658  MariaDbAssignmentOrderApplicationOperation.php new-file diff
a4170eb481b84c7e0b0eff30da1d63ba77a7bc869c2962285a7bc469bf0dd4ea  confirmed_original_opening_001_test.php new-file diff
571103a2d64eb98772383ffc0fe6469cb4c03b115c9b3c428832faed3934b43d  confirmed_original_opening_http_001_test.php new-file diff
```

## Findings

No blocking findings remain.

`MariaDbConfirmedOriginalOpening` is the single public compound command owner. It validates canonical command shape, requires an idle caller connection, checks `installation.open` before reading mutable command inputs, obtains the current original reference and clock before opening its transaction, then begins one READ COMMITTED transaction. Inside it, the owner locks the exact installation case, reauthorizes `installation.open`, locks the current selection/application sequence, confirms the original through the same issuer instance, and either reuses the current exact application or invokes the transaction-neutral application operation.

The extracted `MariaDbAssignmentOrderApplicationOperation` contains no begin, commit, rollback, or isolation control. It preserves the former application validation, issuer guard, current-sequence, completion/PTO, document-date, eligibility, append-only application/event/attempt and replay behavior. The standalone `MariaDbAssignmentOrderApplication` continues to pass only `assignment_order.composition.apply`, checks that permission before original resolution and again under lock, owns its own transaction, and commits deterministic application attempts as before. Holding only `installation.open` therefore does not grant the standalone apply seam.

After internal application, compound opening runs inside the same transaction. Template binding, case opening and `installation_opened_from_original` are committed together. The late missing-template test reaches failure after application preparation and proves rollback of the application row, application attempt, process events, template association and case state. Invalid date, stale original and unauthorized actor likewise preserve the complete business snapshot.

Accepted replay is identified from the durable opening event by canonical UUID and a SHA-256 fingerprint covering request ID, object, order, original revision, expected application sequence, actual start date and actor. It also verifies stored actual date, actor and application identity before returning the original result. A differing payload with the same request ID maps to `request_id_conflict`; another opening maps to `already_open`. Replay rollback is checked before returning success. The commit phase sets its uncertainty marker before the single commit, so commit failure maps to `persistence_outcome_unknown`; failures before commit attempt roll back and retain deterministic/redacted reason mapping.

The first success preserves actor identity across roles: uploader 18 remains on the immutable original, while authorized opener 19 owns both application and opening. It creates one application, one template association and one correctly named opening event. Exact replay adds no row, event, attempt, association or case mutation.

The HTTP test uses the real production route and compound seam. It submits only the declared form fields, derives actor and object from trusted HTTP context, receives 303 to the object card, observes one internal application, retained original download, team and completion views, no fabricated 1C registration, and 403 for an administrator without the opening role. No separate application UI or widened authority is introduced.

## Independent verification

```text
PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/AssignmentOrderComposition/confirmed_original_opening_001_test.php
PASS confirmed original compound opening and replay
PASS confirmed original invalid date refusal
PASS confirmed original stale revision refusal
PASS confirmed original authorization refusal
PASS confirmed original late failure is atomic

PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/AssignmentOrderComposition/confirmed_original_opening_http_001_test.php
PASS HTTP original confirmation -> direct opening -> current object card

PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/InstallationProcess/manual_original_execution_smoke_test.php
PASS manual original application/reapplication/opening/template preservation smoke

PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/AssignmentOrderComposition/selection_unknown_employment_manual_pilot_test.php
selection_unknown_employment_manual_pilot_test: PASS

PHP lint for all reviewed production/test files; focused git diff --check
PASS

PATH=/opt/homebrew/bin:$PATH php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

PATH=/opt/homebrew/bin:$PATH tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)
```

The tests use disposable databases and preserve cleanup. The review performed no implementation/test edits, stand/data mutation, deployment, remote action, or Bitrix action. This approval is bounded to the exact listed artifacts and does not cover the reviewer-authored opening helper, declare full `VERIFY_OK`, or establish production readiness.

## Test supplement — existing application and corrected-original reapplication

The production hashes and code verdict above are unchanged. Root added the two
explicit OpenSpec 1.2 scenarios to the focused compound test; this reviewer did
not author them. They replace the earlier test identity with:

```text
4696649d3f4682ebc9f3b3ec85f512010e51247b5074645caaa7eecbb748e624  tests/AssignmentOrderComposition/confirmed_original_opening_001_test.php
13c3346bda3f82c6fa7606ff27f60e0e254fb3f74837c97e88cbaa3790137094  new-file binary diff
```

Supplemental test verdict: `APPROVED`.

The first scenario applies the current original through the existing standalone
public seam, removes standalone-apply authority, then proves an actor holding only
opening authority can open through the compound seam without adding a second
application row. The existing application remains byte-identical and the opening
result identifies it.

The second scenario starts with the same application, accepts a corrected current
original before opening, then proves the compound seam appends exactly one
`reapplication` row, preserves the prior application byte-for-byte and returns the
new current application identity. Both use expected sequence 1, so they are
sensitive to an implementation that resets sequence or silently replaces history.

Independent focused rerun:

```text
PASS confirmed original compound opening and replay
PASS confirmed original invalid date refusal
PASS confirmed original stale revision refusal
PASS confirmed original authorization refusal
PASS confirmed original late failure is atomic
PASS confirmed existing application opening
PASS confirmed corrected original reapplication and opening
No syntax errors detected in tests/AssignmentOrderComposition/confirmed_original_opening_001_test.php
git diff --check: PASS
```
