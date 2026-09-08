# PILOT-UI-SHELL-001 — independent upload-first integration Gate 5 v1

- Date: `2026-09-04`
- Review started: `2026-09-04T15:03:40+03:00`
- Reviewer: separately tasked agent `/root/ui_shell_gate5_final`
- Integration base: `796307ed6bd52bf1f98cc07b6dadd98bc3224fe8`
- Approved Gate 3 v7 review commit: `910079c6d08baad9e3199e99089740af8048fc88`
- Approved Gate 3 v7 reviewed head: `5774aedff5ffe4376fd55800a8ef05e61c7ef2fd`
- Production candidate: `cd25498db67f154170529a25804026d4cdf4efb5`
- Reviewed evidence head: `d0308e3333af32139693b4e7254563738f8adcbe`
- Reviewed range: `796307ed6bd52bf1f98cc07b6dadd98bc3224fe8..d0308e3333af32139693b4e7254563738f8adcbe`
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specification, tests, Gate 3 records,
production implementation, GREEN records, browser evidence or full-verification
record. This append-only review record is the only review edit. No production
or test file was changed during review.

## Gate 5 decision

The focused executable UI-shell behavior and CSS ownership correction are
GREEN, but mandatory P0 responsive browser acceptance is not met. Gate 5 is
therefore **CHANGES_REQUESTED**. The repository-wide failure is separately and
correctly retained; it is not used as a waiver or as the reason for this bounded
verdict.

### G5-UI-1 — P0 200% text layout visibly overlaps (`BLOCKING`)

`PILOT-UI-SHELL-001 v0.4` section 4 requires no clipped or overlapping text at
`320 CSS px` with 200% root text, and section 8 requires peer identity, actor,
label and control regions not to overlap. The submitted screenshots contradict
that requirement:

- `queue-320x568-text-200.png` shows the identity/header, primary navigation
  and queue title occupying the same top area;
- `prepare-320x568-text-200.png` shows the same header/navigation overlap and
  the engineer choices squeezed into a narrow column with severe character and
  word fragmentation.

The submitted JSON corroborates the first failure for all three routes: header
`y=-71..151`, navigation `y=-7..87`, and main starts at `y=72`, so the regions
overlap. The first navigation link also has focused `y=-7`, contradicting the
section 8 requirement that the focused rect be inside the viewport after browser
scroll.

Return to Gate 4, correct the application CSS for the approved text-resize
contract without changing approved test expectations, and record a new exact-SHA
browser matrix.

### G5-UI-2 — browser report does not execute the exact oracle (`BLOCKING`)

The evidence runner's `allFocusVisible` value only checks that the outline is
not `none`; it does not check full focused-rect containment in the viewport.
It records region boxes but does not evaluate the required reading order or
peer-region intersection. Its `clipped` query covers only descendants of
`main` and only `overflow-x` hidden/clip, rather than every visible owner and
both text dimensions required by section 8. Consequently the published
`overflow=0 / clipped=0 / wide=0 / focusFailures=0` aggregation is not proof of
the exact five-part oracle, and it labels the observed negative focused rects as
successful.

The new evidence must record every required boolean per case and must fail when
any required containment, ordering, overlap, text-accessibility, native-control
geometry or focus condition fails. This is an evidence correction, not authority
to add repository browser/harness infrastructure; section 8 still assigns it to
delivery/manual smoke.

## Standards axis

**CHANGES_REQUESTED** for the P0 accessibility/layout defect above. An
independent standards-axis inspection additionally identified maintainability
risk in serialization-coupled composition: `PrepareFormView.php` injects the
picker script by replacing `</body>` and derives compatibility content through
literal `<main>` offsets, while `PilotSessionView.php` injects feedback against
an exact serialized class marker. These are judgement-call Feature Envy /
Message Chain and Duplicated Code smells rather than a separate product
decision. They should be removed through the existing explicit shell/view
composition seam if touched during correction; they do not authorize a broad
harness or domain refactor.

## Specification axis

**CHANGES_REQUESTED** solely because mandatory `PILOT-UI-SHELL-001` section 8
browser acceptance is contradicted and incompletely measured. No other missing
UI-shell behavior, security regression, unauthorized mutation or CSS ownership
violation was found in the bounded automated review.

## Independent reproduction

On exact reviewed head `d0308e3333af32139693b4e7254563738f8adcbe`, during
`2026-09-04T15:00:00+03:00` through `2026-09-04T15:03:40+03:00`:

```text
php tests/InstallationProcess/pilot_ui_shell_001_test.php --css-ownership-only
PASS: PILOT-UI-SHELL-001 actual CSS ownership

php tests/InstallationProcess/pilot_ui_shell_001_test.php
PASS: PILOT-UI-SHELL-001 public UI shell

php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

php tests/InstallationProcess/pilot_route_csp_001_test.php
php tests/InstallationProcess/pilot_route_csp_inventory_001_test.php
php tests/InstallationProcess/pilot_route_csp_completion_final_html_001_test.php
php tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php
all PASS

php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
both PASS

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

make lint
exit 0

openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid
```

`pilot_object_card_001_test.php` reached the separately reviewed navigation
transition (`Expected` no `/pilot/`, `Actual` retained `/pilot/`) and exited
`255`; this is the already classified pre-navigation-removal RED, not a waiver
or a new UI-shell finding.

Range `git diff --check 796307e...d0308e` exits `2` because the new full
verification Markdown record contains two trailing-space line endings. The
ordinary worktree `git diff --check` is clean because no uncommitted diff was
present. A subsequent evidence-only correction should also remove that diff
hygiene defect in a new commit rather than rewriting history.

The submitted full verification record was inspected and accurately reports
`FULL_VERIFICATION_FAILURE` in `unit-test`, `db-test`,
`characterization-test`, and `e2e-test`, with no literal `VERIFY_OK`. This Gate
5 decision makes no repository-wide GREEN, integration, CI or release claim.

## Reviewed hashes

```text
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
b85724b83453a8387b0a7ff742ca0a3586c3bf0ed90267dc03d2d3a644d37c4b  tests/InstallationProcess/pilot_ui_shell_001_test.php
89b18048743e0ad872f6ddeae85ec6f0cbd77ce5948ddcba2652ec7f31ea8a48  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v7.md
0f87d2612a52e3ab30f289654ca5a8f9274da5a21a263085cd9f5293efcb5d51  app/PilotHttp/pilot.css
7700dbf1d0fdff1812e65e143c40472cb2e44086c303c206851a24f88809b00b  browser report.json
db1e527829f2587b20d101a70b6c36ee0e4d8c8c8b31e2779556c41fdcaae04d  browser runtime.json
2bf3ede125b53ce441783e882eeddfc1386776c72a86a86b57adc9b96b164efa  queue-320x568-text-200.png
0bbb45f6d327ee7032ff8ed3d5c0100bd66575636d66d240703fe3a0ca6c609a  prepare-320x568-text-200.png
b9e54e7297c2908c7a914f428f27a51acaefc650786f14a6fed4d85880e7da2c  picker report.json
```

Gate 5 remains closed. A fresh independent Gate 5 is required after the
production correction and complete replacement browser evidence; this review
does not approve navigation removal or any changed integration composition.
