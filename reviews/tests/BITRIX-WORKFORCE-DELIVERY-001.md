# Independent Gate 3 test review — BITRIX-WORKFORCE-DELIVERY-001

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `/root/bitrix_gate3`, separately tasked agent; not an author of the specification, tests, support harness or production implementation.
- Test author: root agent in a previous session.
- Date: 2026-09-07.
- Reviewed HEAD: `0800095eb364096fc6ff7911fb514a8b8d8be629`.
- Specification: version 0.2, SHA-256 `136c20f8a3d167cda67414198acaad605ec933f1b4bc76fd6188e723a949e7fc`; Gate 1 amendment `BITRIX-WORKFORCE-DELIVERY-001-gate1-v2.md` is `APPROVED`.
- Public seam: `BitrixWorkforceDeliveryFactory::create(...): BitrixWorkforceDeliveryClient`, then `fetch(): BitrixWorkforceDeliveryResult`.

## Material findings

1. **The Gate 1 native-capability outcome is not failure-sensitive.** Specification sections 1 and 3 require `fetch()` to check `CURL_VERSION_ASYNCHDNS`, millisecond request/connect timeout support, `CURLINFO_PRETRANSFER_TIME_T`, and POSIX effective-UID support before credential or network activity, returning `configuration_unavailable`, pages 0 and attempts 0 when any capability is absent. The approved Gate 1 record explicitly says Gate 3 must demonstrate the supported runtime. `BitrixDeliveryFixture.php:23-26` proves only that this machine can complete a verified Curl TLS request; `:38-40` then starts the production worker without asserting the required capability set, and the driver has no unsupported-capability case. An implementation that never performs these prerequisite checks would pass every reviewed expectation on this capable host. Add deterministic evidence for both the required capability set used by the native tests and the specified fail-closed result when a prerequisite is unavailable, without native interception or weakening the public seam.

2. **Several exact native transport requirements can regress while the suite remains green.** The server records request headers at `bitrix_delivery_https_server.py:44`, but `bitrix_workforce_delivery_001_test.php:11-12` checks only path, method and JSON body; it never requires both `Content-Type: application/json` and `Accept: application/json`. More materially for bounds, `bitrix_workforce_delivery_001_test.php:24` checks only that the two retry delays total at least three seconds. It does not enforce the normative upper jitter bound of 250 ms per delay, so arbitrarily longer retry sleeps still satisfy the assertion until the broad worker deadline. The only deadline case at `:28-31` refuses an unfittable retry delay before a second attempt; no test makes the deadline expire during a native attempt or final validation, so omission of the required post-attempt/final-success deadline checks and `limit_exceeded` → `deadline_exceeded` → ordinary-failure precedence is not detected. Add bounded assertions for the exact headers, retry-delay upper bounds, and at least one post-native-attempt deadline outcome. These are central transport behaviors introduced to resolve Gate 1, rather than optional integration coverage.

## Coverage that is sound

The remaining harness uses a real task-owned HTTPS socket and generated CA/leaf certificates. Peer and hostname verification are enabled in the health prerequisite (`BitrixDeliveryFixture.php:23-26`); the untrusted-CA and wrong-hostname scenarios prove failure before HTTP, while the redirect trap proves redirects are not followed. TCP accepts independently back the attempt counter. The driver traces exact selected fields, raw nullable values, strict envelope/row/pagination rejection, later-page fail-closed behavior, retry classes, per-attempt/cumulative/person limits, connect-versus-request timeout behavior, credential reload and metadata rejection, safe JSON output, batch-copy isolation and repeated-fetch stability. Workers and certificate/server operations are bounded, and fixture teardown removes owned keys, tokens, logs and processes.

Expected records are independently constructed by `bwdPerson()` rather than copied from production. Expected counters and reasons are literal contract values. No real Bitrix endpoint, database, remote system or production secret participates.

## RED and identities

Reviewed the external final RED at `/Users/antropophag/.local/state/fmonitor2-verification/bitrix-delivery-20260907/red-v4.log` and independently reran:

```text
PATH=/opt/homebrew/bin:/Applications/Docker.app/Contents/Resources/bin:$PATH php tests/InstallationProcess/bitrix_workforce_delivery_001_test.php
exit 1; 9 SETUP_OK; 9 CLEANUP_OK; 9 intended factory-absence failures
```

The rerun also independently observed PHP Curl 8.22.0 with asynchronous DNS, both millisecond timeout constants, pretransfer-time support and POSIX effective UID available. This establishes healthy setup and an intended RED caused by the absent factory. As stated in the checkpoint, it does not exercise the transport assertions hidden behind that absence and is not GREEN evidence.

```text
6c9788fb2191e7b0d53f5bdfa29c4f42a24fb2f8b63735aa199d3a6ef620c8b1  tests/InstallationProcess/bitrix_workforce_delivery_001_test.php
fb24ff0fc95e8b7a58d7afca93f3708aced32b6061037dae6d43f386f7e7091f  tests/Support/BitrixDeliveryFixture.php
7be7ea4626c4bbb7cff70f01ea65b3db4a5dd408184c99f87758da14f6de80c0  tests/Support/bitrix_delivery_client_worker.php
efcedf91b2c981508c9253d1f92986f4ed61239f2119bc20d4ea9621c9ea83f3  tests/Support/bitrix_delivery_https_server.py
```

Return to Gate 2 for the failure-sensitivity gaps above and obtain a new independent Gate 3 review. Production implementation must not begin on this verdict. Only this review record was added.
