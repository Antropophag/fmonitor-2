# PILOT-UI-SHELL-001 — independent upload-first integration Gate 5 rereview v2

- Date: `2026-09-04`
- Reviewed at: `2026-09-04T15:35:43+03:00`
- Reviewer: separately tasked agent `/root/ui_shell_gate5_final`
- Previous Gate 5 review: `ade8bc8e8e539822afa1b03a00bbb4a67b7ce4d3`
- P0 production candidates: `b78c59e6d0f3e3a3ad511e71c151030c32ee2ffd`, `b4d91a334c49af84fa38e60e1e3494f8836dfa15`, `557482811a26b0df6f4bef7d4baba0a47c5baeb5`
- Reviewed evidence head: `a1d48f011713bf9e3107a9d85dfd4d9b00b41b6a`
- Correction range: `ade8bc8e8e539822afa1b03a00bbb4a67b7ce4d3..a1d48f011713bf9e3107a9d85dfd4d9b00b41b6a`
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specification, tests, production correction,
GREEN records, browser evidence or full-verification record. This append-only
record is the only review edit. No production or test file was changed.

## Gate 5 decision

The production correction is directionally correct and every reproduced
focused executable check is GREEN. The submitted evidence nevertheless does
not prove the complete mandatory browser oracle on the final production bytes.
Gate 5 remains **CHANGES_REQUESTED**. This is a bounded UI-shell verdict and is
not a waiver for repository-wide verification, navigation removal or release.

### G5-UI-v2-1 — no complete browser matrix on final production SHA (`BLOCKING`)

The complete 12-case queue/card/prepare matrix in
`ui-shell-p0-confirm-b78c59e/report.json` pins production SHA
`b78c59e6d0f3e3a3ad511e71c151030c32ee2ffd`. Production changed afterwards:

- `b4d91a334c49af84fa38e60e1e3494f8836dfa15` changed picker containment CSS;
- `557482811a26b0df6f4bef7d4baba0a47c5baeb5` changed scalable picker CSS,
  skip-link sizing and mobile shared-consumer identity/navigation CSS.

The final `ui-shell-scale-consumers-5574828/report.json` is useful and proves
picker scaling/scroll containment at `320x568` normal and 200%, plus actor and
normal-flow behavior for four additional consumers at those two modes. It does
not rerun queue/card/prepare at `1440x900`, `768x1024`, `320x568`, and
`320x568` with 200% root text, and it does not record the entire section 8
five-part oracle for every route/mode.

Because the approved contract requires exact implementation evidence and the
prior review explicitly required a complete replacement matrix, partial reports
from three different production SHAs cannot be composed into final proof.
Produce one complete section 8 matrix against exact final production candidate
`557482811a26b0df6f4bef7d4baba0a47c5baeb5` (or its production-identical
evidence descendant), with screenshots and all booleans for every case, then
request a fresh independent Gate 5.

The inspected screenshots do show that the original overlap is fixed: the
canonical sidebar/header/navigation precede main in normal flow, the final
picker remains inside its white scrollable overlay with 2.0x font scaling, and
the full shared-consumer actor remains visible. This observation cannot replace
the missing exact-SHA matrix.

### G5-UI-v2-2 — correction range fails diff hygiene (`BLOCKING`)

`git diff --check ade8bc8...a1d48f0` exits `2` because
`docs/operations/pilot-ui-shell-p0-full-verification-2026-09-04-1528.md` lines
3–4 contain trailing whitespace. This is the same evidence-record hygiene item
called out in v1 and was not corrected. Add a new evidence-only correction
commit; do not rewrite append-only history.

## Standards axis

**CHANGES_REQUESTED** because the reviewed correction range fails
`git diff --check`. No new production maintainability, security or boundary
finding was found in the bounded CSS/two-hook correction. The serialization-
coupled view-composition smells noted in v1 remain nonblocking and unchanged;
they do not authorize a harness or broad integration refactor in this slice.

## Specification axis

**CHANGES_REQUESTED** because `PILOT-UI-SHELL-001 v0.4` section 8 requires the
mandatory complete browser evidence before Gate 5, and no such evidence exists
for the final production SHA. Within the evidence actually run, prior overlap,
focus-containment, picker-containment/text-scaling and mobile-actor findings are
closed. No additional behavior mismatch was found.

## Independent reproduction

On exact head `a1d48f011713bf9e3107a9d85dfd4d9b00b41b6a`:

```text
php tests/InstallationProcess/pilot_ui_shell_001_test.php --css-ownership-only
PASS: PILOT-UI-SHELL-001 actual CSS ownership

php tests/InstallationProcess/pilot_ui_shell_001_test.php
PASS: PILOT-UI-SHELL-001 public UI shell

php tests/InstallationProcess/pilot_prepare_form_001_test.php
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
Change 'remove-pilot-work-navigation-item' is valid

git diff --check
exit 0 (clean worktree)

git diff --check ade8bc8...a1d48f0
exit 2 (two trailing-whitespace findings in the new verification record)
```

`pilot_object_card_001_test.php` exits `255` only at the already classified
navigation-removal transition (`/pilot/` remains present). No navigation
production removal is reviewed or approved here.

The final full-verification record was inspected. It accurately reports
`FULL_VERIFICATION_FAILURE` for `unit-test`, `db-test`,
`characterization-test`, and `e2e-test`; literal `VERIFY_OK` is absent. This
review neither hides nor promotes those failures.

## Reviewed hashes

```text
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
89b18048743e0ad872f6ddeae85ec6f0cbd77ce5948ddcba2652ec7f31ea8a48  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v7.md
b85724b83453a8387b0a7ff742ca0a3586c3bf0ed90267dc03d2d3a644d37c4b  tests/InstallationProcess/pilot_ui_shell_001_test.php
8866d9270868f68f27fa69b0b9644126c059476fcbba5c0470fe0de99e3c8050  app/PilotHttp/pilot.css
20b6975a9e2917341b09fa1fb43096b4e9ba9a64787301847acaa3d63c621eed  app/PilotHttp/PrepareFormView.php
69bfead7c4b90dc0ddf0d407af7de162120272b9b4b7c8c6de2294b3adf38834  ui-shell-p0-confirm-b78c59e/report.json
333138ca288f945e02094fc607c7482e864f29f2848dcf6422501f4888856992  ui-shell-picker-containment-b4d91a3/report.json
c409fceb4f33a1ae01eabebf13316ff9d671b9dbff591784154b4046bae50750  ui-shell-scale-consumers-5574828/report.json
92eae9eb15f6b19a785f631fef03f563d1c7c820ae1147eef18c251398de2cae  docs/operations/pilot-ui-shell-enlarged-text-visual-green-v4-2026-09-04.md
82aa18b71f5b68264cdb5d1296daffa3584551d67f31d60d432c7fb9ac5238e0  docs/operations/pilot-ui-shell-picker-containment-green-v5-2026-09-04.md
111dd5caec4a247577cf973c9545e42f69be4a6b87808ba6b1c28995bead0b6d  docs/operations/pilot-ui-shell-scaled-picker-mobile-actor-green-v6-2026-09-04.md
8c16111650bddd92740e7af5aca2cd3c98b647f6b2edb7d06ccf500b4b41bce4  docs/operations/pilot-ui-shell-p0-full-verification-2026-09-04-1528.md
```

Gate 5 remains closed. Fresh approval requires a single complete exact-final-SHA
browser matrix and a clean correction range; no old approval is reused for the
changed integration composition.
