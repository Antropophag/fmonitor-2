# Manual feedback: restore order UI and bounded installer selection

Owner reported that the native selection page discarded the designed rapid-pilot
layout and loaded every installer. This is a regression of the accepted UI, not
permission to restore legacy domain writers.

## Change

The selection and original-upload pages reuse the incumbent order form, summary,
team section, engineer section, file drop, action footer and helper layout. The
installer chooser is a native modal. The page contains only the saved selection;
opening the modal sends no catalogue request. Two search characters initiate an
authorized server query returning at most 20 eligible records. Additional results
load only on an explicit request. Selection survives query changes, loading another
page, closing/reopening the modal and saving/reloading the composition.

The native read query filters employment and full-snapshot proof before pagination.
Unknown hire dates remain unknown and require the existing successful full-delivery
proof. The selection command still performs its own authorization and eligibility
checks. Original, template, application and opening writers remain unchanged.

## Evidence

- Initial focused renderer test was RED: the original selection page had no modal
  and embedded catalogue rows. A read-only real-stand request also exceeded its
  30-second browser navigation timeout before the repair.
- `selection_picker_view_manual_test.php`: PASS, modal/order layout, no embedded
  catalogue, same-origin search CSP.
- `installer_search_http_manual_pilot_test.php`: PASS, min query length,
  authorization, leading-zero personnel search, 20/3 pagination without overlap,
  no persisted mutations.
- Existing selection/replay/replacement/PDF/history HTTP flow: PASS. Its initial
  catalogue assertions now reflect the owner's modal requirement; the submission
  helper models the hidden inputs created by the picker. Domain assertions remain.
- Existing manual execution and checklist HTTP smokes: PASS.
- Private headless runner `runtime/selection-browser-fixture.php` plus
  `runtime/selection-browser.cjs`: PASS. Real synthetic login, initially empty
  selection, modal without preload, one character without fetch, search pages
  20 and 7, empty result, two selected people retained across searches, composition
  save/preselection, and original PDF accepted with HTTP 201. Zero console/page
  errors. Synthetic database and files are cleaned by the fixture.
- Desktop modal and original-upload screenshots and mobile modal/original
  screenshots were inspected. Modal fits 390x844; original has no horizontal
  overflow. Native search consumes Escape to clear a nonempty input; Escape with
  an empty input closes the dialog, and the explicit Done action also closes it.
- Visual contract and focus contract: PASS. Impeccable detector invoked through
  `sh` on changed UI files: exit 0. Direct context launcher was not executable;
  DESIGN.md and the Windows ServiceDesk path were absent. Existing rapid-pilot
  markup/styles and public shlz-ui modal docs were used as visual evidence.

Independent focused review: `reviews/code/selection-picker-manual-2026-09-07.md`.
This repair does not establish full production gates or full golden-path completion.
Deployed source/image and live browser receipt follow after installation.
