# PILOT-UI-SHELL-001 — independent upload-first integration Gate 5 rereview v3

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/ui_shell_gate5_final`
- Previous reviews: `ade8bc8e8e539822afa1b03a00bbb4a67b7ce4d3`, `b697665b19e783edecc76342bd7292b8ac40ca58`
- Final production candidate: `6bf97f72254fb96849c1ef4322325a2e0b997ee4`
- Reviewed evidence head: `13340e4c1e0ec7887f62ae197a5cf3a184833adb`
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specification, tests, production, correction
evidence or browser artifacts. This append-only record is the only review edit;
no production or test file was changed.

## Gate 5 decision

The consolidated evidence now has correct one-SHA lineage and closes the prior
mobile and tablet layout findings. Focused automated behavior is GREEN and the
final evidence-only range is diff-clean. One internal contradiction remains in
the mandatory picker containment evidence, so Spec cannot receive a PASS and
Gate 5 remains **CHANGES_REQUESTED**.

### G5-UI-v3-1 — consolidated report suppresses a failed picker check (`BLOCKING`)

The authoritative report at
`/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-consolidated-6bf97f7/report.json`
pins exact candidate `6bf97f72254fb96849c1ef4322325a2e0b997ee4` and correctly contains canonical
`12`, picker `3`, and configured-consumer `8` cases. Its detailed mandatory
`320x568-text-200` picker result, however, contains:

```json
"footer": {
  "descendant": true,
  "scroll": true,
  "padded": false,
  "unclipped": true
}
```

The same report nevertheless publishes `failures.picker=[]`,
`summary.pickerFailures=0`, and overall `verdict=GREEN`. The accompanying v9
record states that the consolidated picker oracle retains all-child scroll
containment/padded visibility. The detailed false value contradicts that claim
and proves that the aggregation does not fail on every containment check it
records.

The inspected screenshot shows the footer text visibly inside the white dialog,
so this may be a measurement/aggregation defect rather than a production CSS
defect. Gate 5 cannot choose that interpretation without executable evidence.
Record a new exact-candidate report that either proves `footer.padded=true` or
defines and executes the approved containment predicate consistently; every
required detailed false must enter the failure list. No repository browser
harness change is authorized by this finding.

The report also stores picker `closed: true` but does not expose the claimed
focus-return boolean separately. The replacement evidence should retain an
explicit `focusReturned=true`, as required by the approved Escape contract,
rather than requiring the reviewer to infer what the combined boolean means.

## Closed prior findings

- One exact production SHA now owns all canonical `12`, picker `3`, and
  consumer `8` cases; `git diff --quiet 6bf97f7..13340e4 -- app/PilotHttp`
  exits `0`.
- Canonical root font is recorded as `16px` in normal cases and `16px -> 32px`
  ratio `2.0` for all three enlarged-text cases.
- All canonical region-order and pairwise-overlap arrays are clean, including
  tablet identity/navigation/main.
- Every canonical focus traversal visits its full expected sequence with zero
  recorded focus failures.
- Picker heading/body/search/meta/result font ratios are exactly `2.0`; all
  other recorded picker descendant, scroll, padded and unclipped checks pass.
- All eight configured-consumer cases retain exact actor name/email, normal
  order, zero overlap and no page overflow.
- Identity and queue-identity children have explicit ordered nonoverlapping
  geometry. Representative desktop, tablet, mobile, enlarged-text and picker
  screenshots were inspected and show the earlier visible overlaps corrected.
- Historical Markdown hard-break and blank-EOF bytes are preserved and
  disclosed append-only. The final evidence correction range
  `413969a..13340e4` and current worktree both pass `git diff --check`.

## Standards axis

**PASS** for the v3 correction. The two production changes are bounded CSS
layout declarations, use only `.fm2-*` ownership, preserve shared consumers and
introduce no new security, boundary or material maintainability defect. The
historical whitespace is truthfully disclosed without rewriting evidence, and
the new evidence range is clean. Previously noted serialization-coupled view
composition remains an unchanged nonblocking judgement call outside this
correction.

## Specification axis

**CHANGES_REQUESTED** only for G5-UI-v3-1. All reviewed HTTP, CSS ownership,
responsive layout, identity, ordering, overlap and focus behavior otherwise
conforms to `PILOT-UI-SHELL-001 v0.4` and its upload-first successor. The report
must not claim complete picker containment while retaining an unclassified
false containment result.

## Independent reproduction

On exact head `13340e4c1e0ec7887f62ae197a5cf3a184833adb`:

```text
pilot_ui_shell_001_test.php --css-ownership-only
PASS: PILOT-UI-SHELL-001 actual CSS ownership
pilot_ui_shell_001_test.php
PASS: PILOT-UI-SHELL-001 public UI shell
pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

pilot_route_csp_001_test.php
pilot_route_csp_inventory_001_test.php
pilot_route_csp_completion_final_html_001_test.php
pilot_route_csp_completion_flow_001_test.php
all PASS

local_rbac_auth_contract_001_test.php
local_rbac_objects_route_admission_001_test.php
both PASS

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
make lint
exit 0
openspec validate remove-pilot-work-navigation-item --strict
valid
git diff --check
exit 0
git diff --check 413969a..13340e4
exit 0
```

The standalone object-card verifier remains at the separately approved future
navigation-removal assertion; no navigation removal is reviewed here.

The latest full-verification record remains an explicit NO-GO:
`FULL_VERIFICATION_FAILURE count=4` for `unit-test`, `db-test`,
`characterization-test`, and `e2e-test`, with no literal `VERIFY_OK`. This
bounded review does not waive, hide or promote those failures.

## Reviewed hashes

```text
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
89b18048743e0ad872f6ddeae85ec6f0cbd77ce5948ddcba2652ec7f31ea8a48  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v7.md
b85724b83453a8387b0a7ff742ca0a3586c3bf0ed90267dc03d2d3a644d37c4b  tests/InstallationProcess/pilot_ui_shell_001_test.php
70f357ceb387c55738139f03512065d86551477359c8291dadcf7c5403ea36cd  app/PilotHttp/pilot.css
20b6975a9e2917341b09fa1fb43096b4e9ba9a64787301847acaa3d63c621eed  app/PilotHttp/PrepareFormView.php
f459baefa0cf3fa35e86af76da9224c61b01e8a3b586e10c086c5fb5ba31b1eb  ui-shell-consolidated-6bf97f7/report.json
10921e5be09bae8a584a0fd203bcd7099166b099b39dda92cf54f401755bf683  picker-320x568-text-200.png
bb6a65ae1ceba4afcfd72983c5c7e485ab725f073f0c853c4de156abb7c00628  queue-768x1024.png
077bf943936e8012b26849b56d8b94628eb04bb6d34f007fdc5f01f9d1815c7d  prepare-768x1024.png
2636b0a0e9d1b2059d32c2e4fdfb67b46aa2a31805095170b45a0e90a4f54823  docs/operations/pilot-ui-shell-identity-spacing-consolidated-green-v9-2026-09-04.md
c33408321007b4987f9ebe77f835a7a183af05e1bd51177fc472dd6a5033d4c9  docs/operations/pilot-ui-shell-v9-blank-eof-hygiene-note-2026-09-04.md
```

Gate 5 remains closed. A fresh independent review is required after a
consistent exact-SHA picker containment/focus-return evidence correction.
