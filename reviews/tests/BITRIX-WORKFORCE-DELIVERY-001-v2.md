# Independent Gate 3 amendment review — BITRIX-WORKFORCE-DELIVERY-001

- Verdict: **APPROVED**
- Reviewer: `/root/bitrix_gate3`, separately tasked agent; not an author of the specification, tests, support harness or production implementation.
- Test author: root agent in a previous session.
- Date: 2026-09-07.
- Reviewed HEAD: `1031b6e2677518baf3cf10f9eda3df568d9460f3`.
- Specification: version 0.2, SHA-256 `136c20f8a3d167cda67414198acaad605ec933f1b4bc76fd6188e723a949e7fc`; independent Gate 1 amendment is `APPROVED`.
- Prior Gate 3 record: `BITRIX-WORKFORCE-DELIVERY-001.md`, `CHANGES_REQUESTED`, retained unchanged.
- Public seam: `BitrixWorkforceDeliveryFactory::create(...): BitrixWorkforceDeliveryClient`, then `fetch(): BitrixWorkforceDeliveryResult`.

## Amendment disposition

Both prior findings are resolved. `BitrixDeliveryFixture.php:13` now makes the native suite fail setup unless its real PHP runtime exposes asynchronous DNS, millisecond request/connect timeouts, pretransfer timing and POSIX effective UID. The cases at `bitrix_workforce_delivery_001_test.php:33-35` launch separate native PHP processes with `curl_init` and `posix_geteuid` actually disabled by PHP configuration. The worker verifies the named function is absent before constructing the production client. These cases still call the same public factory/fetch seam and require `configuration_unavailable`, pages 0, attempts 0 and no batch. They neither replace functions nor intercept Curl/network behavior. Together, the capable-runtime assertion and two real missing-prerequisite processes make omission of the runtime gate observable.

The server records the native request version and headers, and `bitrix_workforce_delivery_001_test.php:12` now requires HTTP/1.1 plus exact JSON Content-Type and Accept values. Retry request arrival times independently require the one- and two-second bases and cap each observed interval at the specified 250 ms jitter plus an explicit 250 ms native scheduling allowance (`:24`). This is a reasonable nondeterminism allowance for a real socket/process test; exact production random bounds remain inspectable at Gate 5.

The two cases at `bitrix_workforce_delivery_001_test.php:36-41` close the post-attempt deadline gap. After the real HTTPS server receives the request and reaches a bounded readiness barrier, the fixture sends SIGSTOP only to its owned PHP client, releases a real HTTP 200 or 401 response, waits 1.15 seconds against a one-second budget, and resumes the worker in `finally` (`BitrixDeliveryFixture.php:59-68`; `bitrix_delivery_https_server.py:46-50`). A success cannot escape after expiry, and deadline takes precedence over the otherwise applicable authorization result. There is no clock replacement, Curl interception or insecure transport. Fresh fixture directories isolate barrier files, and existing bounded worker/server teardown remains effective.

## Complete test assessment

Traceability and expected values remain sound: the driver cites the approved spec, uses the public seam, constructs literal expected records independently with `bwdPerson()`, and asserts exact result states, counters, pagination, selected fields and raw values. Native TCP accepts independently support attempt counts. The generated CA and leaf certificate, verified health request, untrusted-CA case, hostname mismatch and redirect trap provide true native TLS and redirect evidence.

Rejected schema, scope, pagination, HTTP/API, timeout, credential and size/person-limit cases fail closed without partial batches. Retry classes and exhaustion, connect-versus-request timeout behavior, later-page failure, repeated fetch, credential rotation, result-copy isolation and prior-result stability are sensitive. Safe JSON whitelisting, empty stderr, protocol-only stdout and private-value checks cover result/output privacy. Certificate generation, server operations, client workers and waits are bounded; teardown resumes a stopped worker through `finally`, stops owned processes and deletes the task-owned fixture tree. No real Bitrix endpoint, database, remote system, production secret or product state participates.

## RED and reviewed identities

Reviewed final RED v5 at `/Users/antropophag/.local/state/fmonitor2-verification/bitrix-delivery-20260907/red-v5.log` and independently reran:

```text
PATH=/opt/homebrew/bin:/Applications/Docker.app/Contents/Resources/bin:$PATH php tests/InstallationProcess/bitrix_workforce_delivery_001_test.php
exit 1; 12 SETUP_OK; 12 CLEANUP_OK; 12 intended factory-absence failures
```

Every fixture first completed healthy native verified TLS setup. Every failure is the explicit missing-production-factory RED, rather than setup, certificate, server or cleanup failure. The transport assertions remain behind the absent factory and therefore are reviewed expectations, not claimed GREEN evidence.

```text
e9e3e105af67ca8a465e4ff21c5f3d9cf3f4a1712e576381922a2c3bf02c5c16  tests/InstallationProcess/bitrix_workforce_delivery_001_test.php
1bda63f9be863da275ae480ba8183f38c6f35d6c0b36da29e533dd143378e628  tests/Support/BitrixDeliveryFixture.php
8ffd797eb549940ac74e4f25dc0338e02479ea9d2d38850800f88e1e3b6aa3b7  tests/Support/bitrix_delivery_client_worker.php
7e3fa98d26ded7128afe6610be3e5487110bc762fbe0ee22abec89435da220f4  tests/Support/bitrix_delivery_https_server.py
```

No blocking Gate 3 findings remain. Gate 4 may implement only enough production behavior to satisfy these reviewed expectations without changing them. Focused GREEN, relevant regression and architecture checks, and independent Gate 5 remain required. Only this v2 review record was added.
