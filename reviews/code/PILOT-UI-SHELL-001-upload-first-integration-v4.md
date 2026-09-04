# PILOT-UI-SHELL-001 — independent upload-first integration Gate 5 v4

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/ui_shell_gate5_final`
- Approved Gate 3 v7 review commit: `910079c6d08baad9e3199e99089740af8048fc88`
- Previous Gate 5 reviews: `ade8bc8e8e539822afa1b03a00bbb4a67b7ce4d3`, `b697665b19e783edecc76342bd7292b8ac40ca58`, `f1eb2693d11337dc42c38e7c685b4c1d87447469`
- Final production candidate: `d17803a9a259ea85e8b551ec70db2f85cf437768`
- Reviewed evidence head: `c6ead5bdae5c1ddab64100d60a51d93eefd16576`
- Verdict: **APPROVED**

The reviewer authored none of the specification, tests, production, RED/GREEN
evidence or browser artifacts. This append-only record is the only review edit;
no production or test file was changed.

## Gate 5 decision

**APPROVED** for the bounded `PILOT-UI-SHELL-001 v0.4` upload-first integration
at production candidate `d17803a9a259ea85e8b551ec70db2f85cf437768` and
production-identical evidence head `c6ead5bdae5c1ddab64100d60a51d93eefd16576`.

All findings from v1–v3 are closed with executable exact-SHA evidence. The
approval does not cover navigation removal and does not claim repository-wide
GREEN, CI readiness or release readiness.

## Specification axis — PASS

No blocking or nonblocking specification finding remains.

- One consolidated v11 report freshly exercises the final production candidate
  and contains canonical `12`, picker `3`, and configured-consumer `8` cases.
- Canonical queue/card/prepare cover `1440x900`, `768x1024`, `320x568`, and
  `320x568` with 200% root text. All report `200`, no page/form overflow, no bad
  visible targets, valid primary-region layout, zero positive-area overlaps,
  complete native Tab traversal, viewport-contained focus and visible outline.
- Normal root font is exact `16px`; all three enlarged cases are exact
  `16px -> 32px`, ratio `2.0`.
- Identity and queue-identity child order/spacing are explicitly measured with
  zero sibling overlap. Tablet identity/navigation/main no longer overlap.
- Picker heading, body, selection, search, metadata, results, result and footer
  are each descendants, unclipped and fully padded-visible after controlled
  internal scrolling. At enlarged mobile size the footer reaches exact maximum
  scroll, remains inside the content box with `8.40625px` bottom safety margin,
  and reports `paddedVisible=true`.
- Picker heading/body/search/meta/result/footer font ratios are exact `2.0`.
  Search receives focus, Escape closes the dialog, and the report independently
  records `close.hidden=true` and `close.focusReturned=true`.
- All eight representative configured-consumer cases retain visible unclipped
  actor name/email, correct normal-flow order, zero overlap and no horizontal
  page overflow.
- Representative tablet queue, enlarged mobile prepare, enlarged picker and
  enlarged administration screenshots were visually inspected and agree with
  the recorded geometry. The original mobile/tablet overlap, crushed identity,
  picker scaling and footer containment failures are absent.

## Standards axis — PASS

No documented-standard violation, security regression or new material Fowler
smell was found in the final correction. The one production change adds a
relative `.fm2-picker-footer` bottom margin inside the existing mobile rule; it
does not cap typography, change a component family, use `!important`, cross an
application boundary, or alter behavior outside the approved responsive
correction. Actual repository CSS ownership remains GREEN.

Historical hard-break and blank-EOF evidence bytes remain immutable and have
append-only hygiene disclosures. Current worktree and final evidence range are
diff-clean. Previously noted serialization-coupled compatibility composition is
unchanged and remains a nonblocking judgement call outside this correction.

## Independent reproduction

On exact reviewed evidence head
`c6ead5bdae5c1ddab64100d60a51d93eefd16576`:

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
git diff --check d17803a..c6ead5b
exit 0
git diff --quiet d17803a..c6ead5b -- app/PilotHttp
exit 0
```

An independent machine query over v11 required the exact SHA/counts and empty
failure arrays, then rejected any canonical status/overflow/bad-target/order/
overlap/focus failure, any non-`2.0` enlarged root or picker font ratio, any
picker child without descendant/padded-visible/unclipped truth, absent close or
focus return, or any configured-consumer clipping/order/overlap failure. It
returned `true`.

The standalone object-card verifier remains intentionally at the separately
approved future navigation-removal assertion. No navigation removal is included
or approved here.

## Full-verification boundary

The latest full verification remains an explicit NO-GO outside this bounded
approval:

```text
FULL_VERIFICATION_FAILURE count=4
stages=unit-test,db-test,characterization-test,e2e-test
literal VERIFY_OK absent
```

This Gate 5 approval does not waive, hide or promote those failures. Repository
integration still requires their owning gated corrections and a fresh full
verification.

## Reviewed hashes

```text
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
89b18048743e0ad872f6ddeae85ec6f0cbd77ce5948ddcba2652ec7f31ea8a48  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v7.md
b85724b83453a8387b0a7ff742ca0a3586c3bf0ed90267dc03d2d3a644d37c4b  tests/InstallationProcess/pilot_ui_shell_001_test.php
784252cb7ea31be4694204ee7a60aa9704928ad5e59dc37649d9ce1a49df7fa5  app/PilotHttp/pilot.css
7b7fe2c10572d0478003176cc5eace6b58eb5e2c661bdd837bd5980f703eb7d6  docs/operations/pilot-ui-shell-picker-aggregation-lineage-red-v10-2026-09-04.md
e21ea36dd8b9a6220fca65c0cc654f446eb263514719526502cb75f66a948cf8  docs/operations/pilot-ui-shell-picker-footer-consolidated-green-v11-2026-09-04.md
c43a3d9993b77bf290cd23e39f948a7312bd373ea61bddb804b89bdd8c816cc0  ui-shell-consolidated-v11-d17803a/report.json
1cc1cf993d6d93149e4e43177fa8e39701a0b379131529d893428fe58ff98023  ui-shell-consolidated-v11-d17803a/runtime.json
7de013d358ad73b198d14a23379dc9de28e2d77e8a4d42a9757c971dd1a6c7b4  picker-320x568-text-200.png
bb6a65ae1ceba4afcfd72983c5c7e485ab725f073f0c853c4de156abb7c00628  queue-768x1024.png
c72c4613f5dc91682e1ea1bd1476dc1e9ab41b49dfd72a9e4883bd15f485b3c1  prepare-320x568-text-200.png
```

Gate 5 for the bounded UI-shell upload-first integration is **APPROVED**. Any
later production or integration-composition change requires new executable
evidence and a fresh independent review; this approval is not reusable across
changed bytes.
