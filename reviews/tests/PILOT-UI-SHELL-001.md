# Test review: PILOT-UI-SHELL-001 v0.3

- Gate: 3 — fresh independent test review
- Reviewer: separately tasked agent `/root/ui_test_review_v3`
- Test/spec authors: other agents; this reviewer authored neither reviewed artifact
- Reviewed specification commit: `232876506d558a617f010e1632ce6031ab4a1934`
- Reviewed test commit: `0de036f772e26bf18a99b19ae1e065bb433eae70`
- Specification: [`specs/PILOT-UI-SHELL-001.md`](../../specs/PILOT-UI-SHELL-001.md), version `0.3`, `APPROVED 2026-08-29`
- Public seam: raw HTTP `GET|HEAD` successful pilot pages and CSS assets, plus the explicitly specified production-source composition manifest
- Red command and intended failure: `php tests/InstallationProcess/pilot_ui_shell_001_test.php` reaches the queue with minimal predecessor privileges and fails `queue status`: expected `200`, actual `503`
- Date: `2026-08-29`
- Verdict: `APPROVED`

## Findings

None.

- **Traceability and seam — pass.** The test cites v0.3 A–E and observes raw HTTP, parsed DOM and served CSS. Source inspection is limited to the normative production composition boundary; no private renderer method is an oracle.
- **Sensitivity — pass.** The minimal-privilege queue fixture catches the v0.3 requirement that the queue must not join process/order/event/task projections. Per-item assertions bind independently fixed identity, facts, ordering and sole canonical link. Card/form assertions catch the predecessor headings, breadcrumbs, action copy, fact order, controls and hostile escaping. Exact shell, ownership, responsive and focus contracts remain sensitive to plausible omissions.
- **Expected-value independence — pass.** Text, URLs, fixture order, people, dates and CSS declarations are literals from v0.3 and its approved predecessor examples. Nothing is derived from production rendering, discovered selectors, private SQL or result order.
- **Rejected cases — pass.** The test covers asset route/method priority, missing asset `503`, empty queue, empty installer catalog, corrupt queue data, escaping and zero mutation. The predecessor suites retain the broader Host/auth/route/capability/integrity matrices.
- **Determinism and isolation — pass.** Unique database/user/artifact names, local-only dependencies and `finally` cleanup isolate the run. GET/HEAD parity, repeated/concurrent representations and database/filesystem/`shlz-ui` fingerprints prove the read-only boundary.
- **RED validity — pass.** Syntax succeeds. The focused test fails only after its server and fixture are live: current v0.2 production still reads process dependencies for the queue, producing `503` where v0.3 requires `200`. This is the intended missing behavior, not broken setup.
- **Inherited-suite state — understood, not a Gate 3 defect.** `PILOT-HTTP-AUTH-001` is green. The current v0.2 UI implementation makes list/card/prepare predecessor suites red on exactly the conflicts corrected by v0.3 (queue process dependency, changed card heading/copy, changed prepare heading). Gate 4 must make the focused test and all four inherited suites green without changing the approved test.

## Verification evidence

```text
$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php
exit code: 0

$ php tests/InstallationProcess/pilot_ui_shell_001_test.php
TestFailure: queue status
Expected: 200
Actual: 503
exit code: 255

$ php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary

$ php tests/InstallationProcess/pilot_object_list_001_test.php
TestFailure: collection route exists and succeeds; expected 200, actual 503
exit code: 255

$ php tests/InstallationProcess/pilot_object_card_001_test.php
TestFailure: Example A broad reader visible literal/order: Объект монтажа № 4512; expected true, actual false
exit code: 255

$ php tests/InstallationProcess/pilot_prepare_form_001_test.php
TestFailure: heading; expected 1, actual 0
exit code: 255
```

## SHA-256 reviewed-input manifest

Set digests hash the `LC_ALL=C` binary-path-sorted per-file `sha256sum` manifest and pin both bytes and membership. The complete individual manifests were inspected when calculating these digests. The review path is metadata because self-hashing is circular.

```text
c57d5c61bbaa99e4f2bbc38b45a8a5943f06bf9c23945ad5b85ea8f8e7e584cc  specs/PILOT-UI-SHELL-001.md
1af50e7ef628895874b0afbb291942502a6bac9367ec117124bdf7855081911e  tests/InstallationProcess/pilot_ui_shell_001_test.php
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
35759ce4856d703e197c1e70e00a14ec316b3e94104ca959a5d4abf19c50c669  specs/PILOT-PREPARE-FORM-001.md
721a3a6e06efb18da2702c379a785c5ed863c251622b059437bea95deccb5d54  docs/development-process.md
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
250f582106c9e15db622473d0d9f13d0dc0a256592e3a4c4545b1cff49a06a27  public/router.php
a25da1465484a2b78564edda024cf7bd934151ace1d5bb4904ada4b4e12d60bd  app/PilotHttp/production-entrypoint.php
aa451f641196e201dfeb14e90e95834820e7ed6df3347c577ab9383201ff6e20  app/PilotHttp/*.php sorted membership manifest (43 files)
0cbb6e423ca836f2d615141536b92bb6d48b507c76dbbab4faced291bb22d946  app/InstallationProcess/*.php sorted membership manifest (26 files)
METADATA  reviews/tests/PILOT-UI-SHELL-001.md
```

Any spec/test/predecessor/support/bootstrap/router byte change or scanned-set membership change invalidates this approval. Gate 4 may change production only and must preserve the approved test bytes and expectations.

## Required changes

None. Gate 3 for `PILOT-UI-SHELL-001 v0.3` is `APPROVED`. Gate 4 must restore all inherited suites to green; Gate 5 still requires real-browser evidence at 320 CSS px and 200% text zoom.
