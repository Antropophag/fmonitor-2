# RAPID-VISUAL-CONTRACT-UPLOAD-FIRST-VERIFIER-CORRECTION-001 — independent Gate 3 review

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/rapid_visual_gate3`
- Test author: parent agent `/root`
- Reviewed commit: `bce294df9ad955190a2074c3a912da61930faf65`
- Reviewed parent: `5e2cf0b8ba5f08defa2b79fd2d549e80afc5162b`
- Specifications: `PILOT-UI-SHELL-001 v0.4`, `PILOT-PREPARE-FORM-001 v0.2`, `PILOT-OBJECT-CARD-001 v0.2`
- Public seam: repository visual/focus contract verifiers over the canonical rapid-pilot and production view sources
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specification, correction, production or
Gate 2 evidence. This append-only review record is the only persistent review
edit. All temporary sensitivity mutations were restored byte-for-byte before
the verdict and commit.

## Finding

### G3-RV-1 — stylesheet-order assertion observes only the first `PilotView` document branch (`BLOCKING`)

The correction properly replaces the stale interpolated `$pilotCssHref`
locator with the current literal `/pilot/assets/pilot.css`, but retains a
single pair of `strpos()` calls over the entire `PilotView.php` source. The
file contains two canonical configured document branches. Both are required to
emit `shlz.css` before `pilot.css`, yet `strpos()` observes only the first
occurrence of each literal.

Sensitivity proves the gap: reversing only the second branch to emit
`pilot.css` before `shlz.css` leaves `php rapid-pilot/verify-visual-contract.php`
GREEN with exit `0`. The test therefore does not catch a plausible regression
in one supported document composition and cannot substantiate the Gate 2
record's claim that both canonical shells are checked.

Return to Gate 2. Make the verifier require every relevant configured
`PilotView` document composition to contain the exact ordered pair, or use an
equivalently complete deterministic source assertion. Reproduce sensitivity
for each branch independently and request a fresh independent Gate 3. Do not
change production to satisfy this finding.

## Traceability and retained coverage

- Removing the obsolete future primary demands is aligned with the approved
  upload-first contract: current `PrepareFormView` owns the primary
  `Загрузить распоряжение`, while original-upload and opening controls await
  their named application slices.
- The new negative assertion rejects either exact premature card control label
  `Открыть работы` or `Загрузить оригинал` in `ObjectCardView`.
- The existing rapid `ObjectDetails` assertion still requires its approved
  upload action to use `shlz-button--primary`.
- All other visual ownership, font, breadcrumb, navigation, calendar, OTIZ,
  checklist, mobile geometry and focus assertions are byte-identical in the
  reviewed correction.
- Expected literals come from the approved specs and latest bounded Gate 5
  approvals rather than production output. The verifier is deterministic and
  reads repository sources only.

## Independent reproduction and sensitivity

Baseline on exact reviewed head:

```text
$ php -l rapid-pilot/verify-visual-contract.php
No syntax errors detected in rapid-pilot/verify-visual-contract.php

$ php rapid-pilot/verify-visual-contract.php
Visual contract OK: shlz-ui ownership, Golos Text delivery, breadcrumbs, and primary actions verified.

$ php rapid-pilot/verify-focus-contract.php
Focus contract OK: native and shlz-ui interactive families use neutral, visible focus chrome.
```

Temporary mutations, each applied alone and then fully restored:

```text
# reverse shlz.css/pilot.css in the first configured PilotView branch
VISUAL_CONTRACT: shlz.css must load before pilot.css
exit 1

# remove shlz-button--primary from PrepareFormView upload submit
VISUAL_CONTRACT: primary action lacks shlz-button--primary: Загрузить распоряжение
exit 1

# add an exact >Открыть работы< control to ProductionObjectCardRenderer
VISUAL_CONTRACT: future original/open controls must not precede their application slices
exit 1

# reverse shlz.css/pilot.css only in the second configured PilotView branch
Visual contract OK: shlz-ui ownership, Golos Text delivery, breadcrumbs, and primary actions verified.
exit 0
```

After restoration, both verifiers again pass and production hashes equal their
pre-sensitivity values. `git diff --check` is clean.

## Exact reviewed-input manifest

```text
e43573d2e52ced83958a39c02544b95c8126eb530fd9ef9cb693c3f0b5d5a832  rapid-pilot/verify-visual-contract.php
77021c6243e5688d3524f405a1b4d59e60f7ce6c708bccd7a8fb771337bbfa98  app/PilotHttp/PilotView.php
20b6975a9e2917341b09fa1fb43096b4e9ba9a64787301847acaa3d63c621eed  app/PilotHttp/PrepareFormView.php
9a1ce9ab49346ec76c72206f7ccc08cc640f4a76b98809878fa0f3faa73eeef3  app/PilotHttp/ObjectCardView.php
6204502b1ba0060dbeb585d96639ffc306a1eea145904169e6798ba206ce5749  rapid-pilot/ObjectDetails.php
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
1cc1ae2948509346d7e44e8ebaa5b6314f4f6496279c9a1bd718cf5f776b1af4  docs/operations/rapid-visual-contract-upload-first-verifier-correction-2026-09-04.md
47fbb292797b24e1772d3a8deb7a26a27b78818a7137c1f7cecaff9fdfd7a109  reviews/code/PILOT-UI-SHELL-001-upload-first-integration-v4.md
dd442cb073be2b3b91f648ea39098245e2bc64589dc33d72f6f6d2a356f21cb7  reviews/code/PILOT-OBJECT-CARD-001-upload-first-integration-v2.md

METADATA  reviews/tests/RAPID-VISUAL-CONTRACT-UPLOAD-FIRST-VERIFIER-CORRECTION-001.md
```

