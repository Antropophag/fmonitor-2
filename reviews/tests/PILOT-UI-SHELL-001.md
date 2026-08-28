# Test review: PILOT-UI-SHELL-001 v0.2

- Gate: 3 — fresh independent test review
- Reviewer: separately tasked agent `/root/ui_test_review_v2`
- Test/spec authors: other separately tasked agents; this reviewer authored neither artifact
- Reviewed specification commit: `a3ace0149416a091d755f58982b85901de07a377`
- Reviewed test commit: `727da83e497ebb128d96f147d4ca811566315b83`
- Specification: [`specs/PILOT-UI-SHELL-001.md`](../../specs/PILOT-UI-SHELL-001.md), version `0.2`, `APPROVED 2026-08-29`
- Public seam: raw HTTP `GET|HEAD` successful pilot pages and CSS assets, plus the explicitly specified production-source composition manifest
- Red command and intended failure: `php tests/InstallationProcess/pilot_ui_shell_001_test.php` fails at the first missing shell behavior: expected title `Моя работа · FMonitor`, actual `FMonitor 2.0`
- Date: `2026-08-29`
- Verdict: `APPROVED`

## Findings

None.

- **Traceability and seam — pass.** The test cites v0.2 acceptance A–E and exercises the confirmed public HTTP/DOM/served-CSS seam. Its source inspection is limited to the specification's manifest and orchestration/view responsibility boundary; it does not invoke private render methods or assert private implementation structure beyond that normative boundary.
- **Sensitivity — pass.** Exact titles, shell landmarks, paired unavailable navigation items, current navigation, breadcrumbs, heading sequences, page-specific order/content, native controls, hostile escaping, stylesheet order/bytes, CSS ownership and exact responsive/focus declarations all fail under plausible omissions or substitutions. Queue rows associate independently fixed values and actions rather than accepting page-wide presence.
- **Expected-value independence — pass.** Text, URLs, fixture order, projection states, candidate identities, hostile literal and CSS declarations are copied from specification v0.2. They are not derived from production output, implementation selectors discovered after implementation, or SQL result order.
- **Rejected cases and predecessor obligations — pass.** The focused test covers the new `pilot.css` route grammar/method priority and missing-asset `503`, empty queue, empty installer catalog and corrupt list. The four already-approved predecessor HTTP suites were rerun and remain green, satisfying section 9 without duplicating their Host, identity, authorization, missing-object, predecessor-CSS and integrity matrices.
- **Determinism and isolation — pass.** The fixture database name, read-only user and artifact directory are unique per run; cleanup is guarded by `finally`. Before/after database, storage and `shlz-ui` fingerprints, repeated/concurrent representations, HEAD parity and absence of cookies cover the zero-mutation contract. The test uses local MariaDB, PHP's local server and local assets only.
- **RED validity — pass.** Syntax succeeds and the focused test reaches the existing public page, then fails on the exact first absent shell title. This is missing slice behavior, not broken setup. The predecessor suites pass against the same environment.
- **Prior findings — resolved.** v0.2 explicitly assigns real viewport/zoom inspection to Gate 5 manual evidence and limits Gate 2 responsive automation to served CSS/DOM. The revised test now retains and escapes the hostile engineer, asserts exact heading/breadcrumb/unavailable-item structure, parses the specified responsive declarations, and executes inherited rejected-case obligations through the predecessor suites.

## Verification evidence

```text
$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php
exit code: 0

$ php tests/InstallationProcess/pilot_ui_shell_001_test.php
PHP Fatal error: Uncaught TestFailure: shell title
Expected: 'Моя работа · FMonitor'
Actual: 'FMonitor 2.0'
exit code: 255

$ php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary

$ php tests/InstallationProcess/pilot_object_list_001_test.php
PASS: PILOT-OBJECT-LIST-001 public HTTP collection

$ php tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card

$ php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
```

## SHA-256 reviewed-input manifest

```text
734c09166faa2687c9a51a420a75b68a89ea82c071ebc7d6139efb527e53c446  specs/PILOT-UI-SHELL-001.md
1673d14d321e7f25bc1b738f029c996a4c781a9ad8165cf0b53601574563f98f  tests/InstallationProcess/pilot_ui_shell_001_test.php
721a3a6e06efb18da2702c379a785c5ed863c251622b059437bea95deccb5d54  docs/development-process.md
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
250f582106c9e15db622473d0d9f13d0dc0a256592e3a4c4545b1cff49a06a27  public/router.php
METADATA  reviews/tests/PILOT-UI-SHELL-001.md
```

## Required changes

None. Gate 3 for `PILOT-UI-SHELL-001 v0.2` is `APPROVED`; Gate 4 may implement the reviewed expectations. Mandatory real viewport and 200% zoom smoke evidence remains a Gate 5 prerequisite exactly as specified.
