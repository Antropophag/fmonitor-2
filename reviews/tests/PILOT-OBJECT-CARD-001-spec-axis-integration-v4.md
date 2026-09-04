# PILOT-OBJECT-CARD-001 — independent Spec-axis integration Gate 3 review v4

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/object_card_upload_gate3`
- Reviewed commit: `cd49e8a7a5f0c179d535a32607d3c9e566504cc5`
- Gate 2 base: `4f0b653bace5db8abe9465fb0c386e783551666a`
- Public seam: configured raw HTTP `GET|HEAD /pilot/objects/4512`
- Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the specifications, tests, production, RED
evidence nor correction. This append-only review record is the only edit.

## Blocking findings

### G3-v4-1 — shared-shell conversion removed the broad-reader action/link ceiling

The previous `pocStructure()` asserted an exact href multiset. V4 deletes that
assertion to accommodate shared navigation and breadcrumbs, but does not replace
it with the exact configured shared-shell href set. The broad actor `19` and new
permissionless actor `25` only reject the known upload-first label and canonical
prepare URL. A renderer can therefore add another process-action anchor with a
different label/URL, or any arbitrary extra link, and both tests still pass.

This weakens the earlier approved broad-reader/no-action matrix and conflicts
with the card contract's bounded links plus the UI-shell requirement that an
unavailable process action is not rendered under another control. It also
means the new permissionless case does not actually prove “no action”; it proves
only “no exact prepare action”.

Required correction: define the exact allowed anchor/href multiset for a
configured read-only card (including skip, shared primary navigation and exact
breadcrumb duplicates), then add only the single canonical prepare anchor for
the capable eligible case. Wrong-state, PTO, broad-reader and permissionless
cases must reject every additional interactive process action, not just one
known label/URL. Preserve the separately approved sole-next-step section oracle.

### G3-v4-2 — breadcrumb current item is not tied to the requested object

The new `breadcrumb current` XPath is a union of an exact `4512` predicate and:

```xpath
starts-with(normalize-space(.), 'Объект монтажа № ')
```

The broad predicate accepts `Объект монтажа № 9999`, a blank suffix, or other
wrong current object text. Because the union is counted only for cardinality,
all uses of `pocStructure()` can pass while the breadcrumb identifies a different
object. The v4 evidence claims an exact current object, and the approved
UI-shell/card contracts require the current label to contain the exact route ID.

Required correction: parameterize the structure oracle by expected object ID
or derive only the test fixture's independently fixed route ID at the call site,
and assert exactly one current span with exact text. Do not use a prefix oracle.

### G3-v4-3 — the new actor-specific shared-shell case omits primary-nav current

Actor `19` separately asserts that the `/pilot/objects` primary-navigation link
is current. Actor `25` calls only `pocStructure()`, whose navigation predicate
checks the landmark but no `aria-current`. Since the new admission seam is
actor-specific, a production branch can return a shell without a current nav
item only for actor `25` and still pass. The configured shared-shell contract
requires exactly one current navigation item on every successful page.

Required correction: move the exact current primary-navigation assertion into
the common configured structure oracle (including cardinality of all current
nav items), or assert it independently for actor `25` as well.

These gaps affect mandatory observable behavior. Gate 4 is **not authorized**
for the v4 test bytes. Correct Gate 2, capture fresh intended RED evidence and
obtain another independent Gate 3 review.

## Sound portions of the correction

- Actor `25` is an explicit active legacy user using the active legacy role `5`.
  `LocalRbacFixture` creates an active local identity, active assigned local
  role and no permission rows from the explicit fixture entry. The pre-HTTP
  assertions pin the actor tuple and exact zero local-permission/process-
  capability counts; actor `19` remains a separate `objects.read` broad reader.
- The permissionless response uses GET/HEAD parity and independently fixed full
  Example A content literals. Current production reaches the intended shared-
  shell RED before that later admission assertion; the test does not fabricate
  a setup failure.
- Exact ordered stylesheet assertions require only `shlz.css` followed by
  `pilot.css`; `.fm2-shell`, sidebar, navigation and `.fm2-main` landmarks are
  required. Ordered `navigation.js` then `object-details.js`, CSP and no-inline
  execution assertions remain intact. The old `legacyDocument` cannot satisfy
  the missing `pilot.css`/`.fm2-*` requirements.
- No artifact-table privilege was added. The v4 test diff changes neither the
  exact DB grants nor production/support files. Existing SELECT-only, forbidden
  read/write, DB/filesystem snapshot and cleanup probes remain.
- The complete content/state/current-order/team/event, route/method/query/body,
  authorization/error/redaction, CSP/scripts, escaping, action-cardinality for
  the eligible capable case, PTO/wrong-state cases, determinism, zero-write and
  corruption matrices remain byte-present except for the weakened global href
  ceiling identified above.

## Reproduced evidence

At `2026-09-04T12:16:55+03:00`:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check \
    4f0b653bace5db8abe9465fb0c386e783551666a..cd49e8a7a5f0c179d535a32607d3c9e566504cc5
PASS (no output)

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_card_001_test.php
PHP Fatal error: Uncaught TestFailure:
Example A required shared-shell DOM pilot stylesheet
Expected: 1
Actual: 0
... pilot_object_card_001_test.php(569): pocStructure()
exit 255
```

Migration, explicit identity/permission setup, least-privilege checks, HTTP
success, full Example A content, CSP and both ordered scripts pass before this
failure. It is a qualifying configured presentation RED, not setup failure.

## Reviewed SHA-256 inputs

```text
ef25a2aa4a6c1678a3dbc955dc4899e268dc1c57b847cbf184dc7b0b0eff49ae  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
9ada7e095804f9f4866d4adc38f14d2609541aa7ec606f375125154284ec6567  docs/operations/pilot-object-card-spec-axis-red-v4-2026-09-04.md
7bd594aea6aa60240bc474862c64cc4e3be17020437d326caa40e1c17430429b  reviews/tests/PILOT-OBJECT-CARD-001-upload-first-integration-v3.md
c727613bb08cebc2753bb57292a8aadd05fc1ff4867698981e438748fd8db91e  app/PilotHttp/ObjectCardView.php
a76cbf70ace1cfa6445ad84eac133267b300accda562ba421f0bba581a7957cd  app/PilotHttp/PilotHttp.php
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
```

The review record path is metadata because a self-hash is circular. Relevant
spec/test/support bytes or scanned-set membership changes require fresh review.
No GREEN or Gate 5 claim is made.
