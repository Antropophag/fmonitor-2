# Test review: PILOT-UI-SHELL-001 v0.4

- Gate: 3 — fresh independent test review
- Reviewer: separately tasked agent `/root/ui_test_review_v4`
- Independence: reviewer authored neither specification nor test and did not use implementation as an expected-value oracle
- Specification commit: `fb4ced7`
- Test commit / reviewed HEAD: `9646c8919455fd0e1c15019d231961849f63d6b4`
- Specification: `specs/PILOT-UI-SHELL-001.md`, version `0.4`, `APPROVED`
- Date: `2026-08-29`
- Verdict: `APPROVED`

## Findings

None.

- **Traceability and compatibility — pass.** The focused test cites v0.4 A–E and directly covers configured/unconfigured composition for all four successful routes, exact predecessor stylesheet count, absence of shared-shell DOM, and unconfigured asset `404` priority. Values come from the approved specification and pinned predecessor contracts.
- **Fail-closed capability boundary — pass.** Configured card probes independently require redacted `503` for permission denial and missing capability table. The predecessor card suite retains its broader zero-row, duplicate and fault matrix.
- **Public seam and sensitivity — pass.** Behavior is observed through raw HTTP, parsed DOM, and exact served CSS. Source inspection is limited to the normative composition boundary; no private renderer method supplies an expectation.
- **Expected-value independence — pass.** Text, URLs, ordering, people, dates, hostile input, CSS declarations and compatibility outcomes are fixed literals. The task-owned configured CSS fixture is compared byte-for-byte and is not derived from production.
- **Determinism and isolation — pass.** Unique database/user/artifact names, local resources, `finally` cleanup, GET/HEAD parity, repeated/concurrent responses, and DB/artifact/`shlz-ui` fingerprints protect the read-only boundary.
- **RED validity — pass.** The test reaches the live unconfigured compatibility route after configured success assertions and fails on the intended missing v0.4 behavior: `/pilot/` has two stylesheets instead of predecessor-exact one. Setup, server, fixture, privileges and parsing have already succeeded.
- **Inherited regressions — pass.** HTTP auth, object-list, object-card and prepare-form suites all pass unchanged at reviewed HEAD. Their broader route/Host/auth/redaction/DB/integrity assertions remain authoritative.
- **Responsive evidence — correctly deferred.** This test claims only the approved raw CSS/DOM oracle. Real-browser viewport, keyboard, reflow and screenshot evidence remains mandatory before Gate 5.

## Verification evidence

```text
$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php
exit code: 0

$ php tests/InstallationProcess/pilot_ui_shell_001_test.php
TestFailure: compat exact stylesheet count /pilot/
Expected: 1
Actual: 2
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

All four predecessor commands exited `0`.

## SHA-256 reviewed-input manifest

Set digests hash the `LC_ALL=C` binary-path-sorted per-file `sha256sum` manifest, pinning bytes and membership. The review record is metadata because self-hashing is circular.

```text
b54412b14ca3d3e8ad63fc629d3dda7e5902209c52a1b2acd92dade5ba053531  specs/PILOT-UI-SHELL-001.md
a7fd35251485818c3caad2c8526b49507d5d6bc883ecff33f2898db5879adc2f  tests/InstallationProcess/pilot_ui_shell_001_test.php
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
35759ce4856d703e197c1e70e00a14ec316b3e94104ca959a5d4abf19c50c669  specs/PILOT-PREPARE-FORM-001.md
43bb4ee0db54d7d06d29f49354dea13642b172fd864f8aa8bcf250583644abc6  tests/InstallationProcess/pilot_http_auth_001_test.php
b8becf9b47b322078ed330711b4a27e05778946e706ca25d0281a5fb747e5ce6  tests/InstallationProcess/pilot_object_list_001_test.php
e5dc0907d18bc9eca3e8180e73625b8d036fdb733f69e2532fc02bdda4fd02e6  tests/InstallationProcess/pilot_object_card_001_test.php
9aa1268e5967387c482cde573bc5861b08e1d7f23762b91bb90ac7667f1ad1a7  tests/InstallationProcess/pilot_prepare_form_001_test.php
52f2c70db36cf897588f814c12f2aad26c26b997e694603c54b8bdb9d9bcda3b  reviews/tests/PILOT-HTTP-AUTH-001.md
5b010abd57f94f840c27947741700ca4b265c5f8a1f4345db7c8f50c657bfc20  reviews/tests/PILOT-OBJECT-LIST-001.md
014bf3f5726ef7913816ebb536a0b57946b1203e96809c2ecb14f49d4d0e3d19  reviews/tests/PILOT-OBJECT-CARD-001.md
3c2a59476c7e7911ace6a83c39db5dd6b786a8cd83a121eff20de8e2deed844b  reviews/tests/PILOT-PREPARE-FORM-001.md
721a3a6e06efb18da2702c379a785c5ed863c251622b059437bea95deccb5d54  docs/development-process.md
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
250f582106c9e15db622473d0d9f13d0dc0a256592e3a4c4545b1cff49a06a27  public/router.php
a25da1465484a2b78564edda024cf7bd934151ace1d5bb4904ada4b4e12d60bd  app/PilotHttp/production-entrypoint.php
12708b9f470e13d356a1698502f6022f9e91b069071570129a53f0a69a72be99  app/PilotHttp/*.php sorted membership manifest (43 files)
0cbb6e423ca836f2d615141536b92bb6d48b507c76dbbab4faced291bb22d946  app/InstallationProcess/*.php sorted membership manifest (26 files)
METADATA  reviews/tests/PILOT-UI-SHELL-001.md
```

Any focused spec/test, predecessor contract/test/review, bootstrap/router/entrypoint byte change, or scanned production-set membership change invalidates this approval. Gate 4 may change production only and must preserve approved expected values and test inputs.

## Required changes

None. Gate 3 for `PILOT-UI-SHELL-001 v0.4` is `APPROVED`. Gate 4 may implement only enough production behavior to satisfy this test while keeping all four inherited suites green. Gate 5 still requires a fresh independent review and specification section 8 real-browser evidence.
