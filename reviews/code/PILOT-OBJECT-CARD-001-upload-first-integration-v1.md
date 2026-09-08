# PILOT-OBJECT-CARD-001 — upload-first integration Gate 5 review v1

- Date: `2026-09-04`
- Aggregating reviewer: separately tasked agent `/root/object_card_gate5_final`
- Standards reviewer: separately tasked agent
  `/root/object_card_gate5_final/standards_axis`
- Reviewed exact head: `a0a78ea395346872651c4fae17c1683ea1f12fbb`
- Final correction production baseline:
  `72f55e355a2b1ad577aff95a9c5a63e83482ee92`
- Full integration lineage inspected: `6605670..a0a78ea395346872651c4fae17c1683ea1f12fbb`
- Public seam: configured raw HTTP `GET|HEAD /pilot/objects/{positive-id}`
- Verdict: **CHANGES_REQUESTED**

The reviewers authored none of the specifications, tests, RED evidence or
production under review. This append-only record is the only review edit.
Earlier Gate 5 approval covers an older composition and is not reused.

## Standards

Independent verdict: **CHANGES_REQUESTED**.

### G5-v1-1 — compatibility rendering parses one HTML document and duplicates another shell

Blocking maintainability finding. In
`app/PilotHttp/ObjectCardView.php:17-25`, `renderCompatibility()` calls the
configured renderer, searches its serialized result for exact `<main>`,
`</main>` and `</nav>` strings, extracts a substring and wraps that body in a
second locally implemented document shell. `legacyDocument()` duplicates the
compatibility header/navigation/document structure already owned by
`app/PilotHttp/PilotShellView.php`.

This is brittle hidden coupling at the public compatibility boundary: an
otherwise valid shared-shell markup or breadcrumb change can make the parser
select the wrong bytes or take a structurally different fallback. The behavior
currently passes, but Gate 5 explicitly includes integration boundaries and
maintainability. Expose the object-card body as an explicit composition value
or seam and let the appropriate existing shell renderer compose it; do not
parse a rendered HTML document or maintain a second compatibility shell.

The same method has a non-blocking speculative branch:
`legacyDocument(..., bool $scripts)` has one caller and it always passes
`false`; the `true` branch is unreachable at this head.

The independently run architecture policy remains GREEN (`7/7`). No other
documented-standard or smell-baseline finding was identified.

### G5-v1-2 — reviewed-range whitespace contradicts clean-range hygiene

Non-blocking evidence-hygiene finding. Working-tree `git diff --check` is
clean, but the committed reviewed range is not:

```text
docs/operations/pilot-object-card-csp-green-evidence-v1.md:3,5,7
docs/operations/pilot-object-card-full-verification-2026-09-04-1248.md:3-4
```

The latter record says `git diff --check` passed; that statement is true for
the working tree command it records, not for the full introduced range. Since
evidence is append-only, any clarification must be a new record rather than a
rewrite of its result.

## Spec

Independent aggregator verdict: **APPROVED** — 0 findings.

The exact current production and approved tests implement the changed
integration contract without introducing a new product decision:

- an exact active legacy user with an active legacy role may read the card;
  local identity existence/status/role/permission and process capability do
  not become card-read admission gates;
- inactive legacy user or inactive legacy role is denied with opaque `403`
  before an object read; the identity-only SQL principal proves the narrow
  least-privilege decision path;
- `assignment_order.prepare` is consulted only for the write/action affordance;
  it is not required for broad card reading;
- configured cards use the shared shell and exact current navigation,
  stylesheet, breadcrumb and source-only script order; compatibility selection
  remains configuration-only;
- the five semantic groups, exact identity/dates/current order/current team,
  all configured process states, action negatives, exactly one eligible
  `Загрузить распоряжение` link and newest three durable events are preserved;
- no artifact-table read was added, hostile values remain escaped, and all
  exercised GET/HEAD paths remain observationally read-only.

The Gate 3 v3/v6/v7 records are exact-byte applicable. The tests would catch
the plausible authorization, source-confusion, extra-action, shell/script,
event-order and least-privilege regressions reviewed above.

## Reproduced verification

At `2026-09-04T12:53:15+03:00` through `2026-09-04T12:54:27+03:00`:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_route_csp_001_test.php
pilot_route_csp_001_test: PASS

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ make lint
exit 0

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check
PASS (no output)

$ git diff --check 72f55e355a2b1ad577aff95a9c5a63e83482ee92..a0a78ea395346872651c4fae17c1683ea1f12fbb
docs/operations/pilot-object-card-full-verification-2026-09-04-1248.md:3: trailing whitespace.
docs/operations/pilot-object-card-full-verification-2026-09-04-1248.md:4: trailing whitespace.
exit 2
```

The broader UI-shell verifier was sampled and remains an explicitly named
predecessor failure (`shell identity`, expected `1`, actual `0`); it is not
used as card GREEN. The exact full verification record reports
`FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test`
and no literal `VERIFY_OK`. Those named unrelated failures do not erase the
bounded card behavior GREEN, but this review does not waive the repository
NO-GO or claim integration/CI/release readiness.

## Exact reviewed-input manifest

```text
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
3d0d01da364cb9575793bf43e15389371ac73ffb633b34b79963c1191e2d065d  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
ca82fca35d722c9c626eb6eb69e1aa11e7958730bc99e67541aebb610dbf5952  tests/Support/LocalRbacFixture.php
7bd594aea6aa60240bc474862c64cc4e3be17020437d326caa40e1c17430429b  reviews/tests/PILOT-OBJECT-CARD-001-upload-first-integration-v3.md
f18b804204c838965daf70c3aa81b3e2b609db67c5df58e658302aaf321c88d8  reviews/tests/PILOT-OBJECT-CARD-001-spec-axis-integration-v6.md
3d9a8388de31ba9d52f092d1f4d78914f0839e1a8a1bf491a6ccc3a3713c673a  reviews/tests/PILOT-OBJECT-CARD-001-cross-source-identity-v7.md
065d4bddd974cf8acec1becff5e85fda23adef728ef44b3b6f6f9a45a7ae647d  app/PilotHttp/ObjectCardView.php
19e5703061b69d5379a62c0086ab17b0b264fc72902c5c22dc15fd4e84fc228c  app/PilotHttp/PilotHttp.php
5f225ece450786d653badcf2f900fed23d42438740901aece904ef4aca471d25  app/PilotHttp/PilotView.php
be0e8dede13a68086bbcbc42bf8944c8b5cca721b40039641c3b16572d774768  app/InstallationProcess/MariaDbProcessUserDirectory.php
79fd4e7bee23e5474cd66e121b365d75f8218828cc9630f896da9122db061dd3  docs/operations/pilot-object-card-cross-source-identity-green-v3-2026-09-04.md
b8ca2a58dfd18746e6dc082d93e0806dbbfda99245e277b4ba8c60fb15ca9354  docs/operations/pilot-object-card-full-verification-2026-09-04-1248.md

METADATA  reviews/code/PILOT-OBJECT-CARD-001-upload-first-integration-v1.md
```

Summary: Standards — 1 blocking and 2 non-blocking findings,
`CHANGES_REQUESTED`; Spec — 0 findings, `APPROVED`. Overall Gate 5:
**CHANGES_REQUESTED**. A fresh Gate 5 review is required after correction.
