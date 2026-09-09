# Independent Gate 3 review — PRODUCTION-RUNTIME-FPM-DRAIN-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Test author: separately tasked agent `/root/runtime_tests`
- Verdict: **APPROVED (BOUNDED)**

This approval covers PHP-FPM active-request drain on configured container stop.
It does not approve nginx drain, refusal of new traffic, the full restart contract,
or the complete production HTTP runtime.

## Exact reviewed artifacts

```text
9ed9cf2102e53c93239979c9ac0010d9df2999c5e8ff9e176b9ac6436175559e  specs/PRODUCTION-HTTP-RUNTIME-001.md
e3364113caaf4afa863bfb52a6cfc65793b37224460c53007b362cf72025534a  tests/Runtime/production_runtime_compose_001_test.php
100939f5a6dd077f900fd516af01578aa69fee667357089e3a2c0011da98c7cd  deploy/runtime/php-fpm.conf
7ac5dad073b5eff8bb43400c8e308a03b16b9e747f3ced80d109f13e4850c256  deploy/runtime/compose.yaml
```

## Sensitivity and determinism

The real two-container fixture installs and syntax-checks a test-only FPM handler.
The handler writes a marker after entry, then uses a monotonic deadline loop for
1.5 seconds so signal interruption cannot masquerade as completed work. The test
polls the shared marker before issuing Compose stop with the configured 60-second
timeout. It requires the stop to finish within that bound, the client to receive
exit 0 and a `DRAIN_COMPLETE` elapsed value of at least 1300ms, empty stderr, and
no running PHP container afterward.

Thus a signal delivered before dispatch, an interrupted sleep, a reset FastCGI
connection, a killed worker, or a retained container cannot satisfy the test.
Unique Compose project/volumes and unconditional `down --volumes` isolate cleanup.

## Demonstrated RED

```text
$ php tests/Runtime/production_runtime_compose_001_test.php
# all prior storage, migration, nginx/FPM HTTP and restart checks pass
# handler-entry marker is observed
# docker compose stop --timeout 60 php: exit 0, within bound

SIGQUIT drains and returns the complete active FPM response after sustained work
Expected: [0, true, '']
Actual:   [104, false, '']
exit 255
```

The failure is the intended lifecycle defect, not setup. A separate minimized
reproduction identified PHP-FPM `process_control_timeout=0`; setting a bounded
55-second control timeout makes the active client receive exit 0 and the complete
response.

## Verdict

**APPROVED (BOUNDED).** Gate 4 may add the minimal PHP-FPM process-control timeout
below Compose's 60-second grace period so an entered request completes before
worker termination. Reviewed expectations must not change without rereview.

## Supplemental nginx lifecycle evidence

The final Compose test `abd058072f43c1ba25af0ed3f8fd3c84d665f2792571ffb2844d0aa3d9c91efa`
starts a second real foreground nginx using a fixture copy of production config
with only pid/listen and one slow FastCGI location changed. It proves the request
entered FPM before QUIT, new traffic is refused while the active client remains
running, the complete response arrives, nginx exits zero within 60 seconds, and
its pid file is removed. The production FPM drain assertions pass later in the
same isolated run.

```text
$ php tests/Runtime/production_runtime_compose_001_test.php
PASS: PRODUCTION-HTTP-RUNTIME-001 real nginx/FPM Compose lifecycle
```
