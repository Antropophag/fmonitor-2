# Code review: PILOT-HTTP-AUTH-001

- Reviewer: `Codex agent /root/http_v12_final_gate5_fresh2` (fresh independent Gate 5 reviewer; did not author the specification, tests, support harness, router, or production implementation)
- Reviewed commit: exact working-tree bytes at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd` (dirty handoff; manifest below is the fixed point)
- Specification: [`specs/PILOT-HTTP-AUTH-001.md`](../../specs/PILOT-HTTP-AUTH-001.md), version `0.12`, `APPROVED 2026-08-28`
- Approved Gate 3: [`reviews/tests/PILOT-HTTP-AUTH-001.md`](../tests/PILOT-HTTP-AUTH-001.md), verdict `APPROVED`
- Supersedes: prior Gate 5 verdict `CHANGES_REQUIRED`
- Verdict: `APPROVED`

## Standards

`APPROVED` by the separately tasked fresh Standards-axis reviewer. No documented-standard breaches were found.

The prior section 11.2 blocker is resolved: the frozen open-world oracle is GREEN and `app/PilotHttp/*.php` has no unqualified direct global call. Its unlisted `md5(...)` and namespace-relative `namespace\preg_match(...)` probes remain rejected. The six adversarial shadows (`basename`, `is_link`, `is_readable`, `getenv`, `bin2hex`, `random_bytes`) remain uncalled.

The previous forensic close/ownership findings are resolved. Production has no FFI, libc, raw/integer descriptor, `/proc`, preload, native-fd-close, or double-close path. The descriptor relinquishes ownership before one closer attempt; the closer restores its scoped handler and maps false/warning/Throwable to the exact redacted typed failure; dependency cleanup is idempotent and attempt-all. Host-first validation, exact authentication, lazy canonical environment/resource access, fixed/thin bootstrap, response headers, redaction, and the no-mutation boundary conform.

Non-blocking judgment calls: possible Divergent Change/Large Class in `app/PilotHttp/PilotHttp.php`, and possible Middle Man/Duplicated Code in the require-only public seam proxies. The repository's minimal composition contract currently chooses these seams, so neither is a documented-standard breach.

## Spec

`APPROVED` by the separately tasked fresh Spec-axis reviewer.

- Missing or partial requirements: none.
- Scope creep: none.
- Implemented-looking but incorrect behavior: none.

The reviewer independently confirmed the section 11.2 qualification repair, six-shadow behavior, CSS descriptor ownership and redaction, absence of FFI/native-fd/double-close mechanisms, Host and routing priority, authentication and exact active-role lookup, lazy CSS/DB configuration, resource cleanup, security headers, HEAD behavior, fixed production bootstrap, thin public router, and GET/HEAD-only no-mutation contract.

## Verification

```text
approved Gate 3 spec/test/support hashes                         exact match
open-world global-call oracle                                   PASS (GREEN)
focused PILOT-HTTP-AUTH-001 HTTP boundary                       PASS
all 40 InstallationProcess test files, sequential               40/40 PASS
all 40 InstallationProcess test files, parallel (`xargs -P8`)   40/40 PASS
PHP lint, every PHP file under app/bin/public/tests              PASS
git diff --check                                                 PASS
production FFI/libc/raw-fd/proc/preload/native-close search      none
post-test .test-artifacts entries                                0
residual HTTP/test/proxy/server processes                        0
```

The dirty handoff has no usable committed slice comparison. This approval is pinned to the complete exact-byte manifest below. Standards and Spec were reviewed by two separately tasked fresh agents and are intentionally reported as independent axes.

## Reviewed-input SHA-256 manifest

Captured at `2026-08-28T10:15:06Z` UTC. This covers the exact specification, approved Gate 3 review, HTTP tests/support, test bootstrap, every `app/PilotHttp/*.php`, the production entrypoint, and public router. **Any listed hash change, or any addition/removal/rename in these scopes, invalidates this approval and requires a fresh independent Gate 5 review.**

```text
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
52f2c70db36cf897588f814c12f2aad26c26b997e694603c54b8bdb9d9bcda3b  reviews/tests/PILOT-HTTP-AUTH-001.md
4c4bba041ac99d590ca5625fd06f695a924bfa4bec12c94753bce39a71cf1394  tests/InstallationProcess/pilot_http_auth_001_test.php
60110b7db537c74262310a7e982e20e6945c12b49f5d1524f0b02c0a13c58271  tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
652708eea6099b750b805b996da195c6c2b3c6eb8616270323f491591f3935f0  tests/bootstrap.php
6c8d2177d53cf8903fd0b658ec5c537b1d54549cfecf436c19fed2aea002666f  tests/Support/css_descriptor_ownership_spy.php
b23ba215387aa8328fcf71c7e71324f0363f403e7c776f84233b378e6043cb29  tests/Support/css_lstat_swap_preload.c
b5b73e59665c46a6bfc5b7f1b05476d74f3fa3893cb6ba305dc26756e56ba422  tests/Support/php_css_descriptor_fault_spy.php
3f8cfbc6bbfb2e5836013ed79fdb311841ba7916e2be38d27617333e01e5021c  tests/Support/php_input_open_probe.php
82960cd0c079c3fc587451d4fc11af29854fc39c7606a2189c3911c96e7907b0  tests/Support/php_input_open_sentinel.php
8f6fd9e35f3588a04c3d56be099abeadc68b9f0bc7a35631fcc68c06d28e6542  tests/Support/php_runtime_primitive_shadow_spy.php
9f8893e0f87d158dc76c229f681aedb970d7beeb306d59362d55cf467a671f86  tests/Support/production_environment_source_spy.php
185b00529a17adb2b40078c85dd4b70edd65b234d5d3d6c0deca8b73b7df87b7  tests/Support/production_http_router_probe.php
c3788e557d633591bc561a10a0551a21b218c1553f82f9e1e80027351ea11d06  tests/Support/raw_http_get.php
f15a1d73d5fb577116343c2c7c581ec4cbc44dfad3c9dab6a72c6de7f67e0ee9  tests/Support/real_http_entrypoint_spy.php
c6dd22b73fed01e40b646478e4b1b4f4dfa532a0467708577a1ec5aa05d9c752  tests/Support/real_production_resource_close_probe.php
38cc3a96ed6c23b7b88f6860b811d81933b5c4bfbba4f45c11bbe7a2da68edf7  tests/Support/trusted_host_server.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/CorrelationIdSource.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/CssAsset.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/CssAssetUnavailable.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/CssDescriptor.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/CssDescriptorCloseFailed.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/CssDescriptorOpener.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/EnvironmentSource.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/ErrorLogUnexpectedFailureReporter.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/HttpUser.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/HttpUserDirectory.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/InvalidHttpRequest.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/InvalidServerIdentity.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/MariaDbHttpUserDirectory.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/NativePhpFclosePrimitive.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/NativePhpStreamCloser.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/PhpCssDescriptor.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/PhpCssDescriptorOpener.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/PhpStreamClosePrimitive.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/PhpStreamCloser.php
448902cde6421e7351d74a80972fd7061d484f98803ea5e0ddf05e08b47cc86d  app/PilotHttp/PilotHttp.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/PilotHttpApplication.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/PilotHttpDependencies.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/PilotHttpEntrypoint.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/PilotHttpInfrastructureUnavailable.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/PilotHttpRequest.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/PilotHttpRequestFactory.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/PilotHttpResponse.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/PilotShellRenderer.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/ProcessEnvironmentSource.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/ProductionPilotHttpDependencies.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/ProductionPilotHttpEntrypointFactory.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/ProductionPilotShellRenderer.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/RandomCorrelationIdSource.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/RemoteUserIdentity.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/ShlzCssAsset.php
2bc42a8ce2cb6037cd260f659a794c5ee5c1f5147cd876497b31cde0457ed71f  app/PilotHttp/TrustedServerIdentity.php
3c884368676fc9e252062b2f93b711244122c16820b6e98d653c7734b01f3f92  app/PilotHttp/UnexpectedFailureReporter.php
e0ab09767ebc433ba01fa9a1206c605ed6765d82d15ba6815942df30d4cd635e  app/PilotHttp/production-entrypoint.php
250f582106c9e15db622473d0d9f13d0dc0a256592e3a4c4545b1cff49a06a27  public/router.php
```

Gate 5 is approved for these exact inputs.
