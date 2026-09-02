# Test rereview: INSPECTION-ITEM-COMPLETE-001 UI/client RED

Verdict: `APPROVED`

Reviewer: separately tasked independent agent `/root/item_ui_test_review`

Date: 2026-09-01

## Scope and reviewed artifacts

This is the independent Gate 3 rereview after
`reviews/tests/INSPECTION-ITEM-COMPLETE-001-ui.md` rejected source-text checks
that did not execute the checklist client.

- Approved executable specification `specs/INSPECTION-ITEM-COMPLETE-001.md`,
  SHA-256 `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- OpenSpec design
  `openspec/changes/migrate-inspection-item-completion/design.md`, SHA-256
  `19d2cde38a3105e1c533039ee43aad7e5266402840d21c859fa43bed55d6167d`.
- Focused runner
  `tests/InstallationProcess/inspection_item_complete_001_ui_client_test.php`,
  SHA-256 `b3fdcd6c8116043e722d54f85896c190c8db8487f765606e190652c06c3f82c9`.
- Included endpoint fixture and UI assertion
  `tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php`,
  SHA-256 `92c675e810acf7c6ddbb34271838b21935b3aba4ca7df64327ceb3bcf0d3924e`.
- Executable client harness
  `tests/InstallationProcess/support/inspection_item_complete_ui_browser.js`,
  SHA-256 `a1846b2ff624e16cc44023a76f86875de98f8dee6982af6f5619640c40908e2d`.
- Gate 2 record
  `docs/operations/inspection-item-completion-ui-red-evidence.md`, SHA-256
  `113db6bdf69a233123d480332cd453bdf4fcdf7031fd36475a99cfa79d865a37`.
- Gate rules in `docs/development-process.md`.

No production code or reviewed expectation was changed during this rereview.

## Independent verification

Commands:

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_ui_client_test.php
php -l tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
node --check tests/InstallationProcess/support/inspection_item_complete_ui_browser.js
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_ui_client_test.php
```

Both PHP files and the Node harness passed syntax checks. With the test database
healthy, the focused runner exited according to the RED harness contract and
reproduced:

```text
Item-only UI/client contract:
- root exposes item-only completion capability
- item completion controls are not disabled by an inert ancestor
- photo upload controls stay disabled
- installer correction controls stay disabled
- bulk/section completion controls stay disabled
- item click observably enqueues only item_completed
- item click observably sends item_completed
RED_ASSERTION: expected failing behavior observed
```

The request reached the real checklist GET page and the failure was emitted by
the reviewed UI/client assertions. It was not a database, HTTP-start, Node,
syntax or fixture setup failure.

## Independent findings

- **Traceability:** the test fixes the approved capability-only case: an active
  exact-capability engineer may complete an item despite another engineer's
  assignment. The UI keeps photos, installer correction and bulk/section
  completion outside this slice, matching the approved explicit exclusions and
  the rule that only `item_completed` is delegated.
- **Executable public interaction:** the assertion derives the root capability
  dataset from the actual public HTML, loads the actual served production
  `checklist.js` into a deterministic DOM/IndexedDB/fetch harness, activates the
  item toggle, and observes both the persisted operation and outgoing request.
  It no longer approves attribute-name mentions or a particular source spelling.
- **Sensitivity:** a retained global legacy gate produces neither the
  `item_completed` IndexedDB operation nor request and therefore fails. A client
  that emits a legacy/non-item operation also fails the exact type assertions.
  Separately, the real HTML must expose exactly the item-only capability, remove
  the inert ancestor, leave all 42 item toggles usable, and leave photo,
  installer and bulk controls disabled. Plausible regressions in either server
  rendering or client enqueue are observable.
- **Expected-value independence:** capability separation and operation type come
  from the approved spec/design. The control counts come from the deterministic
  served checklist fixture, not from production authorization calculations.
- **Non-item behavior:** the harness attempts bulk, installer and photo
  activation before the item click. Their disabled fixture state mirrors the
  separately asserted public HTML state; the final persisted/sent type sets
  prove no non-item operation escaped those attempts.
- **Determinism and isolation:** IDs, projection, crew and control states are
  fixed; the client harness uses process-local maps and mocked fetch only. The
  Node child completed, its stdin/stdout/stderr were closed and it was reaped
  with `proc_close`. The enclosing endpoint test's `finally` path removed its
  owned database/artifact tree and stopped/reaped the HTTP process; its existing
  forced-hung-child cleanup self-check completed without cleanup findings.

## Verdict

The prior blocking finding is resolved. This focused UI/client RED satisfies
Gate 2 and Gate 3 for the narrow item-only interaction. `APPROVED` permits
minimal GREEN without changing the reviewed expectations. Any expectation or
harness-contract change requires a fresh demonstrated RED and independent test
review.
