# RAPID-VISUAL-CONTRACT-UPLOAD-FIRST-VERIFIER-CORRECTION-001 — independent Gate 3 rereview v2

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/rapid_visual_gate3`
- Test author: parent agent `/root`
- Reviewed correction commit: `0b5ad7a1a577a9bec7cfcdbb92bce3c6e232ab75`
- Prior review commit: `230faa81bc9004c737ad86b27a5b1c53b2d3496e`
- Specifications: `PILOT-UI-SHELL-001 v0.4`, `PILOT-PREPARE-FORM-001 v0.2`, `PILOT-OBJECT-CARD-001 v0.2`
- Public seam: repository visual/focus contract verifiers over the canonical rapid-pilot and production view sources
- Verdict: **APPROVED**

The reviewer authored none of the specification, verifier correction,
production or Gate 2 evidence. This append-only record is the only persistent
review edit. All temporary sensitivity mutations were restored byte-for-byte.

## Findings

None.

The v1 blocker is closed. `PilotView.php` contains exactly two literal
`shlz.css` tags and two literal `pilot.css` tags. The corrected oracle requires
at least one pair, equal tag counts, and an exact adjacent ordered pair count
equal to the total shlz tag count. Consequently every shlz occurrence belongs
to a `shlz.css` → `pilot.css` pair and, because counts are equal, no missing,
extra, separated or reversed pilot occurrence can evade the assertion.

Reversing only the second configured `PilotView` document branch now produces
the intended exact failure and exit `1`. This directly closes
`G3-RV-1`; the test no longer relies only on the first occurrence.

## Traceability, independence and retained sensitivity

- The exact literal stylesheet pair is required by `PILOT-UI-SHELL-001` for
  every configured successful HTML page.
- `PILOT-PREPARE-FORM-001 v0.2` retains the current primary
  `Загрузить распоряжение`; removing its primary modifier fails the verifier.
- `PILOT-OBJECT-CARD-001` plus the approved upload-first integration does not
  authorize original-upload/opening controls yet; adding exact
  `Загрузить оригинал` card control text fails the verifier.
- The independent rapid `ObjectDetails` upload-primary assertion and all
  previous ownership, font, breadcrumb, calendar, OTIZ, checklist, geometry
  and focus checks remain present and GREEN.
- Expected literals come from approved specs and reviewed upload-first
  integration rather than generated production output. The source verifier is
  deterministic and has no production-system dependency.

## Independent reproduction

Baseline:

```text
$ php -l rapid-pilot/verify-visual-contract.php
No syntax errors detected in rapid-pilot/verify-visual-contract.php

$ php rapid-pilot/verify-visual-contract.php
Visual contract OK: shlz-ui ownership, Golos Text delivery, breadcrumbs, and primary actions verified.

$ php rapid-pilot/verify-focus-contract.php
Focus contract OK: native and shlz-ui interactive families use neutral, visible focus chrome.
```

Fresh temporary sensitivity, each mutation applied alone and restored:

```text
# reverse only the second configured PilotView stylesheet pair
VISUAL_CONTRACT: shlz.css must load before pilot.css in every document branch
exit 1

# remove shlz-button--primary from current PrepareFormView upload submit
VISUAL_CONTRACT: primary action lacks shlz-button--primary: Загрузить распоряжение
exit 1

# add exact >Загрузить оригинал< control to ProductionObjectCardRenderer
VISUAL_CONTRACT: future original/open controls must not precede their application slices
exit 1
```

After restoration both baseline verifiers pass again. The four production
hashes below exactly match the pre-sensitivity values. `git diff --check` is
clean for this review; an unrelated concurrently modified inspection endpoint
test was neither inspected, staged nor committed.

## Exact reviewed-input manifest

```text
0b09c1f6c529edc0ecf8a8ef844c132a194d12c714481a4bc008730dc3b7142f  rapid-pilot/verify-visual-contract.php
77021c6243e5688d3524f405a1b4d59e60f7ce6c708bccd7a8fb771337bbfa98  app/PilotHttp/PilotView.php
20b6975a9e2917341b09fa1fb43096b4e9ba9a64787301847acaa3d63c621eed  app/PilotHttp/PrepareFormView.php
9a1ce9ab49346ec76c72206f7ccc08cc640f4a76b98809878fa0f3faa73eeef3  app/PilotHttp/ObjectCardView.php
6204502b1ba0060dbeb585d96639ffc306a1eea145904169e6798ba206ce5749  rapid-pilot/ObjectDetails.php
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
7a17c7b6d531596edf53d96cbd2e6bcfc09b30d01b46cba3a0af538114babc1a  docs/operations/rapid-visual-contract-upload-first-verifier-correction-v2-2026-09-04.md
5878a1b25e3410aff26e6a8fd7c65f58fc5d5a0d342d6f88d412c37ce24550eb  reviews/tests/RAPID-VISUAL-CONTRACT-UPLOAD-FIRST-VERIFIER-CORRECTION-001.md

METADATA  reviews/tests/RAPID-VISUAL-CONTRACT-UPLOAD-FIRST-VERIFIER-CORRECTION-002.md
```

