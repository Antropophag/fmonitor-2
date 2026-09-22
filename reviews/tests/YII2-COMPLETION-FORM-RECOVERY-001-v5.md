# YII2-COMPLETION-FORM-RECOVERY-001 — Gate 3 delta rereview v5

- Reviewer: `/root/completion_gate3` (independent; did not author specification or tests)
- Root-authored test correction: `57e6140c365690bb9e22de7a7325eb443a65ea60`
- Approved Gate 3 baseline review: `cd6d129077c282e66b04f5668d50a8ca4ff85682`
- Corrected browser script: `tests/Yii2/completion_form_recovery_browser.mjs` (`git hash-object`: `7c6cf83189af6e6c9f5697236e75a44e654db83d`)
- Review scope: committed test delta only; uncommitted production changes explicitly excluded
- Verdict: **CHANGES_REQUESTED**

## Finding disposition

The two-line delta makes an initially closed correction form usable by opening its
real `summary` control before entering values, and correctly stops requiring
production to force disclosure merely so the scenario can type. However, the
conditional opener accepts both the intended normal closed state and an unintended
ordinary-GET state where production renders the correction disclosure open. It
therefore cannot distinguish the behavior this correction is intended to preserve:
normal disclosure is closed, while a rejected `422/409` response forces it open.

The correction does not weaken the approved recovery oracle. After both `422` and
`409` replacement, the test still independently requires the correction `details`
to be open, the containing tab to be selected, and the focused error to be in the
viewport. Duplicate-submit, network/non-HTML recovery, POST counting, quiet-period,
retention, navigation, append-only correction, and immutable-root assertions are
unchanged.

The prior browser RED remains meaningful and reachable: the explicit assertion that
the required `data-completion-form="correct_declaration"` hook exists still runs
before the new disclosure step, so pre-implementation production fails at the same
missing hook rather than passing due to test setup.

`node --check` and `git diff --check` pass. The exact committed delta contains only
the browser-test file.

## Required change before rereview

Before clicking, assert that `initialDetails.open` is `false`; click its `summary`;
then assert that it is open before filling the fields. Keep the existing post-`422`
and post-`409` assertions, which separately prove forced disclosure after rejection.

The dirty production implementation was not reviewed or approved. Gate 3 remains
closed; final review and CI are unaffected.
