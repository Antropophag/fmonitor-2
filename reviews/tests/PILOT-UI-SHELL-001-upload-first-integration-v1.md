# PILOT-UI-SHELL-001 — independent upload-first integration Gate 3 review v1

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/ui_shell_integration_gate3`
- Reviewed exact HEAD: `195dbafc02896fdb89e51fb42df0bba821a4e9d0`
- Replacement base: `d2759ea7c74a634ce549d5cad7dff995615f01b5`
- Public seam: configured and compatibility raw HTTP `GET|HEAD` pilot pages
  and configured assets
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specifications, tests, production, or RED
evidence. This append-only record is the only review edit. No production or
test file was changed during review.

## Blocking findings

### G3-v1-1 — the replacement weakens the approved shell script boundary

The change to `pusCommon()` replaces the prior zero-script assertion with an
allowlist that rejects only inline and visibly remote `src` values. It thereby
permits an arbitrary same-origin script on root, queue and card pages and also
permits other non-HTTP schemes. The prepare assertion then positively requires
`/pilot/assets/navigation.js` before `/pilot/assets/picker.js`.

That is not a Prepare v0.2 replacement. `PILOT-UI-SHELL-001` section 3 says the
shell slice connects no JavaScript, and its only approved successor exception
is the exact route-specific `/pilot/assets/picker.js` on prepare; the other
shell controls remain native. `PILOT-PREPARE-FORM-001 v0.2` likewise says the
picker is the sole route-specific script. No reviewed input supplied to this
Gate 3 approves `navigation.js` or a new script-bearing shell composition.

Source/mutation analysis confirms lost sensitivity: adding
`<script src="/pilot/assets/unreviewed.js"></script>` to root, queue or card
would pass the changed common assertion. A root/queue/card script assertion
must remain exact zero, and configured prepare must require exactly one
external deferred script, `/pilot/assets/picker.js`, unless a newly approved
executable specification explicitly replaces that boundary. The successful
prepare response must also retain the exact approved CSP with
`script-src 'self'`; a CSP-only sibling suite cannot authorize an extra asset.

### G3-v1-2 — homogeneous workforce provenance is asserted in the wrong mode

The fixture gives both eligible installers the same exact source and update
moment. Prepare v0.2 section 7 therefore requires one group-level provenance
pair. The replacement instead requires two row-associated strings, one for
each person, and never asserts absence of the per-row provenance list.

This expectation is contradicted by the approved predecessor and by the
independently passing prepare suite, which asserts exactly one scoped paragraph
with one `<br>` and no `.fm2-picker-provenance` list for the same homogeneous
case. A production mutation that always selects row mode can satisfy the new
UI-shell expectation while violating the approved rule. Correct the cumulative
test to require the exact group pair and absence of row provenance here; retain
mixed-provenance association in the predecessor suite rather than deriving an
alternate rule from current production output.

### G3-v1-3 — two superseded Prepare v0.1 expectations remain

The replacement is incomplete in two independently reachable branches:

- the configured zero-installer branch still expects
  `Нет монтажников, допустимых для планового периода объекта.` instead of the
  v0.2 replacement `Нет допустимых монтажников.`;
- the unconfigured compatibility prepare page still checks for
  `Состав распоряжения` instead of the latest approved predecessor's
  upload-first representation (`Загрузить распоряжение`, upload-first intro,
  breadcrumb current `Распоряжение`, picker/fallback and no mutation controls).

`PILOT-UI-SHELL-001` section 3 defines compatibility as each route's latest
approved predecessor HTML, not a permanently frozen v0.1 prepare page. The
current one-marker compatibility check would also accept a hybrid document
that retains stale copy while omitting the approved upload-first structure.
Update both branches and make compatibility assertions sensitive to the
replacement's route-specific structure, script/stylesheet contract and exact
card/prepare links without weakening root, queue or card compatibility.

## Other assessment

- **Traceability and exact inputs:** hashes match the RED record and the owner-
  approved Prepare v0.2/RBAC input set. The object-card Gate 5 approval is
  current and the focused card suite passes.
- **Public seam:** product behavior is observed through the real router, raw
  HTTP, response headers and parsed DOM. The source scan is limited to the
  explicitly specified architecture boundary.
- **Picker grammar and safety:** the reachable source assertions use fixed
  IDs, names, tabs, position, empty busy state and unselected state, require
  empty direct record elements, no initial hidden IDs, fail-closed hidden
  controls, engineer prefill/unconfirmed state, hostile engineer escaping and
  no upload/submit/CSRF/revision controls. The full client normalization,
  Unicode, selection, removal, ceiling and malformed-data sensitivity remains
  in the passing Prepare predecessor suite.
- **Shell/navigation/CSS:** the retained assertions continue to cover the
  configured stylesheet order, skip link, one current navigation item,
  unavailable labels, breadcrumbs, headings, queue/card order, responsive CSS,
  focus declarations, descriptor bytes and source ownership. The script
  weakening above prevents approval of that retained boundary.
- **Authorization, failures and zero writes:** unchanged matrices retain
  configured CSS failure, route/method priority, capability failure, empty and
  broken list behavior, GET/HEAD parity, repeat/concurrent determinism, and
  complete DB/artifact/`shlz-ui` fingerprints. Passing predecessor suites cover
  the deeper RBAC, picker, CSP, corruption and failure matrices.
- **RED validity:** the canonical run reaches a successful parsed configured
  root response and fails on the intended shared-shell identity mismatch. The
  test-owned database/user/artifact cleanup completed. Early failure does hide
  the three findings above at runtime; source and mutation analysis was
  therefore required and performed.

The existing header assertion also proves only that one `header` contains all
three strings, not their specified order. Since this was an already approved
v0.4 assertion rather than a weakening introduced by the replacement, it is
recorded as non-blocking here; tightening exact identity/actor child order in
the correction would improve cumulative sensitivity without changing product
behavior.

## Reproduced evidence

At `2026-09-04T13:17:04+03:00` through
`2026-09-04T13:18:38+03:00`, on exact head
`195dbafc02896fdb89e51fb42df0bba821a4e9d0`:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php
PHP Fatal error: Uncaught TestFailure: shell identity
Expected: 1
Actual: 0
... pilot_ui_shell_001_test.php(35): assertSameValue()
... pilot_ui_shell_001_test.php(57): pusCommon()
exit 255

$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php
exit 0

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card
exit 0

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
exit 0

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_route_csp_001_test.php
pilot_route_csp_001_test: PASS
exit 0

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid
exit 0
```

Before and after the run the branch was clean and synchronized (`0 0`) with
`origin/codex/remove-pilot-work-navigation-v2`. No `.test-artifacts/pus-*`
directory remained.

## Reviewed SHA-256 inputs

```text
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
03590dc7e3058e57ffe251a78e05e1cb12f0f872fb63605468a2a56bcba905f2  tests/InstallationProcess/pilot_ui_shell_001_test.php
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
3d0d01da364cb9575793bf43e15389371ac73ffb633b34b79963c1191e2d065d  tests/InstallationProcess/pilot_object_card_001_test.php
f3e336e7c87b261b981704b9077381abbc036827fd39efdc1ef9b1296628bded  tests/InstallationProcess/pilot_route_csp_001_test.php
ca82fca35d722c9c626eb6eb69e1aa11e7958730bc99e67541aebb610dbf5952  tests/Support/LocalRbacFixture.php
a7169c50ce863e1a8750c24f9ef641408573dcde0e3d02080de081a479ce2417  docs/operations/pilot-ui-shell-upload-first-integration-red-2026-09-04.md
dd442cb073be2b3b91f648ea39098245e2bc64589dc33d72f6f6d2a356f21cb7  reviews/code/PILOT-OBJECT-CARD-001-upload-first-integration-v2.md
abfbf3203ad0260cb2148bd96f0cae3b63ce636cb53446d625cf5c0073b0a998  app/PilotHttp/*.php sorted membership manifest
c93275056b67331f22d580048e873a0438715950389c42475c7fcfd987201101  app/InstallationProcess/*.php sorted membership manifest

METADATA  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v1.md
```

## Required correction

1. Restore the exact approved script boundary: no script on configured
   root/queue/card and exactly deferred `/pilot/assets/picker.js` on prepare,
   with exact successful-response CSP.
2. Assert homogeneous provenance as one group pair and no per-row list.
3. Replace the remaining v0.1 zero-installer and compatibility-prepare
   expectations with exact v0.2 behavior; strengthen compatibility beyond a
   single text marker.
4. Record a new genuine RED for the corrected test bytes and obtain a fresh
   independent Gate 3 review. Do not change production before approval.

Gate 4 is **not authorized** for the test at `195dbafc...`. This review makes no
GREEN, Gate 5, navigation-removal, repository integration or release claim.
