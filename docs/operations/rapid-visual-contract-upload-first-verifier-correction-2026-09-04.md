# Rapid visual contract — upload-first verifier correction

- Date: `2026-09-04`
- Gate: `2`, verifier-only integration correction
- Production changes: none

The verifier retained two predecessor assumptions after the independently
approved upload-first UI integration:

1. it searched `PilotView` for a removed interpolated `$pilotCssHref` fragment,
   although both canonical shells now contain the exact public literal
   `/pilot/assets/pilot.css` after `/pilot/assets/shlz.css`;
2. it required future card controls `Открыть работы` and `Загрузить оригинал`
   before the named original-upload/composition/opening slices exist.

The corrected oracle still requires exact stylesheet order and the current
`Загрузить распоряжение` primary action. It now explicitly rejects premature
future original/open controls in `ProductionObjectCardRenderer`; the existing
rapid adapter assertion for the approved upload action remains unchanged. CSS
ownership, focus, font, breadcrumb, calendar, OTIZ and mobile geometry checks
are byte-identical.

Fresh execution and targeted sensitivity are required before independent Gate
3 review. No rapid-pilot domain behavior or production renderer is edited.

```text
$ php -l rapid-pilot/verify-visual-contract.php
No syntax errors detected in rapid-pilot/verify-visual-contract.php

$ php rapid-pilot/verify-visual-contract.php
Visual contract OK: shlz-ui ownership, Golos Text delivery, breadcrumbs, and primary actions verified.

$ php rapid-pilot/verify-focus-contract.php
Focus contract OK: native and shlz-ui interactive families use neutral, visible focus chrome.

# temporary future-control sentinel in ObjectCardView
$ php rapid-pilot/verify-visual-contract.php
VISUAL_CONTRACT: future original/open controls must not precede their application slices
exit 1
```

The temporary production sentinel was removed immediately and production hashes
remain unchanged.
