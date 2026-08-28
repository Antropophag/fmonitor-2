# Test review: PILOT-HTTP-AUTH-001

- Reviewer: `Codex agent /root/http_v12_openworld_gate3_fresh` (fresh independent Gate 3 reviewer; did not author the specification, tests, support probes, or production)
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/PILOT-HTTP-AUTH-001.md`](../../specs/PILOT-HTTP-AUTH-001.md), version `0.12`, `APPROVED 2026-08-28`
- Supersedes: `CHANGES_REQUESTED` review with global-oracle SHA-256 `47c5d0f44f338faee2c4d108d532381e19e869725af0eb20ca2d4f638863e9da`
- Verdict: `APPROVED`

## Findings

No blocking findings.

The corrected aggregate oracle is open-world. It classifies every direct-call token before function-name filtering. Bare `T_STRING` and namespace-relative `T_NAME_RELATIVE` calls are reported regardless of name; explicit `T_NAME_FULLY_QUALIFIED` and `T_NAME_QUALIFIED` calls are accepted. Declarations, closures, methods, nullsafe methods, static calls, and constructors are excluded structurally. Independent probes prove that previously unlisted `md5(...)` and `namespace\preg_match(...)` calls are rejected, so future names cannot bypass an inventory. The structural probe proves the intended exclusions and acceptance of explicit fully/qualified calls.

Against the reviewed production tree, the aggregate is RED for exactly 60 unqualified direct-call sites, all in `PilotHttp.php`, and prints the complete file/line/name set. Bootstrap, fixtures, support probes, databases, and subprocess output are outside its scan. This is the intended Gate 2 contract failure, not setup failure.

The six behavioral shadows (`basename`, `is_link`, `is_readable`, `getenv`, `random_bytes`, `bin2hex`) sufficiently cover the concrete filesystem/environment/entropy namespace-resolution seams. The main public-boundary test proves all remain uncalled while real behavior and exact shapes are preserved. Shadows for each pure parsing/validation/encoding function would duplicate the complete lexical oracle and public HTTP behavior matrix without improving future-name sensitivity.

Expected values remain specification-derived. The HTTP test passed; afterward `.test-artifacts` was empty and no test server remained. All direct PHP supports and production PHP inputs passed lint; `git diff --check` passed.

## Verification

```text
php -l tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php  PASS
php -l tests/InstallationProcess/pilot_http_auth_001_test.php               PASS
php -l tests/bootstrap.php and direct tests/Support/*.php                  PASS
php -l app/PilotHttp/*.php                                                  PASS
php tests/InstallationProcess/pilot_http_auth_001_test.php                  PASS
php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php     RED, exit 255, exactly 60 sites
git diff --check                                                            PASS
post-test .test-artifacts entries / PHP server processes                    0 / 0
```

## Reviewed-input SHA-256 manifest

Captured at `2026-08-28T10:07:22Z` UTC. The production digest is SHA-256 over the sorted per-file `sha256sum` manifest of every `app/PilotHttp/*.php`. Any listed hash change, addition/removal/rename of a scanned production PHP file, or specification-version change invalidates this approval and requires fresh independent Gate 3 review.

```text
201885dc684287c1526c4657e5a9dd71f23d7dca74423fb5f329169e03fea358  PRODUCT.md
04ac7f9c5568935db3a3f8e53ed56a4268978601e17731ba706a5a4d3d722c10  CONTEXT.md
721a3a6e06efb18da2702c379a785c5ed863c251622b059437bea95deccb5d54  docs/development-process.md
24e106a8db1e9fbff41637da646eeb1fa411c78e71f95b1c9351a814af9ed7a3  docs/fmonitor-2-pilot-spec.md
59d2643200f6649c20f5ce6ea104d88591bf057a0afa64ab056ddd6562162886  docs/fmonitor-2-pilot-data-model.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
4c4bba041ac99d590ca5625fd06f695a924bfa4bec12c94753bce39a71cf1394  tests/InstallationProcess/pilot_http_auth_001_test.php
60110b7db537c74262310a7e982e20e6945c12b49f5d1524f0b02c0a13c58271  tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
8f6fd9e35f3588a04c3d56be099abeadc68b9f0bc7a35631fcc68c06d28e6542  tests/Support/php_runtime_primitive_shadow_spy.php
652708eea6099b750b805b996da195c6c2b3c6eb8616270323f491591f3935f0  tests/bootstrap.php
6c8d2177d53cf8903fd0b658ec5c537b1d54549cfecf436c19fed2aea002666f  tests/Support/css_descriptor_ownership_spy.php
b23ba215387aa8328fcf71c7e71324f0363f403e7c776f84233b378e6043cb29  tests/Support/css_lstat_swap_preload.c
b5b73e59665c46a6bfc5b7f1b05476d74f3fa3893cb6ba305dc26756e56ba422  tests/Support/php_css_descriptor_fault_spy.php
3f8cfbc6bbfb2e5836013ed79fdb311841ba7916e2be38d27617333e01e5021c  tests/Support/php_input_open_probe.php
82960cd0c079c3fc587451d4fc11af29854fc39c7606a2189c3911c96e7907b0  tests/Support/php_input_open_sentinel.php
9f8893e0f87d158dc76c229f681aedb970d7beeb306d59362d55cf467a671f86  tests/Support/production_environment_source_spy.php
185b00529a17adb2b40078c85dd4b70edd65b234d5d3d6c0deca8b73b7df87b7  tests/Support/production_http_router_probe.php
c3788e557d633591bc561a10a0551a21b218c1553f82f9e1e80027351ea11d06  tests/Support/raw_http_get.php
f15a1d73d5fb577116343c2c7c581ec4cbc44dfad3c9dab6a72c6de7f67e0ee9  tests/Support/real_http_entrypoint_spy.php
c6dd22b73fed01e40b646478e4b1b4f4dfa532a0467708577a1ec5aa05d9c752  tests/Support/real_production_resource_close_probe.php
38cc3a96ed6c23b7b88f6860b811d81933b5c4bfbba4f45c11bbe7a2da68edf7  tests/Support/trusted_host_server.php
66020c7e9c7b70d49f98af0be0ae4992b19cae1a0c83d78a8318347020985dc8  app/PilotHttp/*.php sorted SHA-256 manifest
```

Gate 3 is approved for these exact inputs. Gate 4 may proceed only without changing approved test expectations or reviewed inputs.
