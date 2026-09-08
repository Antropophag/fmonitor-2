# PILOT-UI-SHELL-001 — picker containment correction GREEN v5

- Date: `2026-09-04`
- Visual follow-up base: `9681b244657b766c185b5072b0b5e4c88bf91cad`
- Production candidate: `b4d91a334c49af84fa38e60e1e3494f8836dfa15`
- Gate: `4 — bounded visual follow-up evidence`
- Result: **GREEN**

This append-only record does not rewrite prior visual evidence and does not
claim Gate 5, navigation removal, repository-wide GREEN or release readiness.
Tests, specifications, reviews and repository harness files were unchanged.

## Read-only finding

The requested computed-layout audit reproduced a real containment defect on
the previous candidate at `320x568` with 200% root text:

```text
dialog border:  left=16 top=16 right=304 bottom=552
dialog content: left=34 top=34 right=286 bottom=534
meta:           left=34 top=515.375 right=286 bottom=552.375
results/result: left=34 top=552.375 right=286 bottom=596.375
```

`meta`, `results` and `result` were genuine DOM descendants and the dialog had
white background plus `overflow-y:auto`, but `results/result` were below the
visible content box. Therefore the apparent result over the backdrop in the
full-page screenshot was not accepted as a screenshot-only artifact.

## Minimal correction

One mobile picker CSS rule now bounds vertical typography/rhythm inside the
already scroll-capable dialog:

- picker heading: `24px`, `line-height:1.2`;
- picker explanatory/meta/results/footer text: `16px`, `line-height:1.35`.

No HTML, JavaScript, route, behavior, desktop/tablet layout, navigation or
shared-consumer style was changed. CSS ownership remains GREEN.

## Exact confirmation

Existing external Playwright tooling was run without editing its source:

```text
/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-picker-containment-b4d91a3/
```

The report pins exact production candidate
`b4d91a334c49af84fa38e60e1e3494f8836dfa15`, Playwright `1.62.1`, Chromium
`151.0.7922.34` and Node `v22.23.1`. Three picker cases passed:
`1440x900`, `320x568`, and `320x568` with 200% root text.

At `320x568` with 200% root text the exact computed geometry is:

```text
dialog border:  left=16 top=16 right=304 bottom=552
dialog padding: top=18 right=18 bottom=18 left=18
dialog content: left=34 top=34 right=286 bottom=534
meta:           left=34 top=300.765625 right=286 bottom=322.359375
results:        left=34 top=322.359375 right=286 bottom=366.359375
result:         left=34 top=322.359375 right=286 bottom=366.359375
```

For every case:

```text
HTTP status: 200
meta/results/result are dialog descendants: true
meta/results/result fully inside content box: true
dialog background: rgb(255, 255, 255)
dialog horizontal overflow: false
page horizontal overflow: false
search focused: true
Escape closes dialog: true
focus returns to opener: true
```

The engineer fieldset, confirmation control and helper were measured as actual
peer regions. Their pairwise positive-area overlaps were all exactly `0` in
all three cases; extreme wrapping at 200% does not overlap adjacent content.

The isolated browser database was dropped and exact task-owned
`.test-artifacts` was removed.

## Automated guard

On exact production candidate:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=... FMONITOR_TEST_DB_PORT=23306 \
  php tests/InstallationProcess/pilot_ui_shell_001_test.php \
  --css-ownership-only
PASS: PILOT-UI-SHELL-001 actual CSS ownership

git diff --check
exit 0
```

## Exact hash

```text
17378dda0bb4714cacedc508d3cdebf94dac28ad47c74c3fe9b2e10be9cc4e9f  app/PilotHttp/pilot.css
```
