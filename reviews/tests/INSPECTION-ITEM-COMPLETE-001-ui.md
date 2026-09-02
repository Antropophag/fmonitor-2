# Test review: INSPECTION-ITEM-COMPLETE-001 UI/client RED

Verdict: `CHANGES_REQUESTED`

Reviewer: separately tasked independent agent `/root/item_ui_test_review`

Date: 2026-09-01

## Reviewed artifacts

- Approved executable specification `specs/INSPECTION-ITEM-COMPLETE-001.md`,
  SHA-256 `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- OpenSpec design
  `openspec/changes/migrate-inspection-item-completion/design.md`, SHA-256
  `19d2cde38a3105e1c533039ee43aad7e5266402840d21c859fa43bed55d6167d`.
- Focused runner
  `tests/InstallationProcess/inspection_item_complete_001_ui_client_test.php`,
  SHA-256 `b3fdcd6c8116043e722d54f85896c190c8db8487f765606e190652c06c3f82c9`.
- Included public HTTP fixture and assertion helper
  `tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php`,
  SHA-256 `db8fa8f19a36618251320ff8e2bf7db35bf6e323ce26cdb162882efcc26f9f82`.
- Delivery rules in `docs/development-process.md`.

No production code or reviewed expectation was changed during this review.

## Independent RED reproduction

Commands:

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_ui_client_test.php
php -l tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_ui_client_test.php
```

Both syntax checks passed. The RED harness exited successfully and classified
the underlying test failure as `RED_ASSERTION`. The reproduced failures were:

```text
root exposes item-only completion capability
item completion controls are not disabled by an inert ancestor
photo upload controls stay disabled
installer correction controls stay disabled
bulk/section completion controls stay disabled
client consumes the item-completion capability independently
client consumes the non-item capability independently
global legacy enabled gate cannot block item enqueue
RED_ASSERTION: expected failing behavior observed
```

This is a behavior assertion failure after the HTTP fixture starts and serves
the checklist page, not a database or other setup failure.

## Review assessment

- **Traceability and scope:** the rendered-page assertions trace to the approved
  rule that an active holder of exact capability `inspection.item.complete` may
  complete an item on any object, while photos, installer correction and
  section completion remain outside this slice. The assigned engineer and
  legacy-role preservation probe protects existing admission paths.
- **Public seam:** the HTML assertions observe the real public checklist GET
  response. They are deterministic under the fixed fixture and distinguish
  item controls from photo, installer and bulk/section controls.
- **Expected-value independence:** expected item-only authority comes from the
  approved specification/design, not production logic. The literal control
  counts are fixed by the served deterministic checklist fixture.
- **Isolation:** the wrapper reuses the established endpoint fixture, and the
  reproduced run completed without an external-system or setup failure.

## Finding

### 1. Client-side sensitivity is insufficient and inspects implementation text rather than observable behavior

The three JavaScript checks only search `app/PilotHttp/checklist.js` for two
attribute-name strings and the absence of one exact minified substring. They do
not execute the client or click an item control. A nonconforming implementation
can therefore pass by mentioning both attribute names in dead/unrelated code
and expressing the same global block with whitespace, a renamed variable, an
early return in a helper, or another equivalent condition. Conversely, a
conforming refactor can fail solely because it spells the implementation
differently. Thus the test does not reliably prove its own acceptance claim
that the item-only user can enqueue `item_completed` independently of the
legacy operations gate.

This violates Gate 3's public-seam and sensitivity requirements. Replace or
supplement the source-text probes with an executable client/public-browser
interaction that loads the rendered item-only page, activates an item toggle,
and observes an item completion enqueue/request while proving photo, installer
correction and bulk/section actions remain unavailable. The test must remain RED
for the current global-gate behavior, and the new RED evidence requires fresh
independent review before Gate 4.

The rendered HTML assertions themselves have no blocking finding. Gate 3 is not
approved until the client behavior is observed at an executable public seam.
