# Bitrix delivery — checkpoint before independent Gate3

Source base f1f0b824cba97ff7b95582b98e5284295ce5d479. No production delivery code.
Spec BITRIX-WORKFORCE-DELIVERY-001 v0.2 SHA
136c20f8a3d167cda67414198acaad605ec933f1b4bc76fd6188e723a949e7fc,
Gate1-v2 APPROVED; first CHANGES_REQUESTED retained. OpenSpec strict validation PASS.

Gate1 amendment explicitly requires ASYNCHDNS/millisecond/pretransfer/POSIX support,
checked before credentials/network; deadline after preflight covers attempts/waits/
validation, post-attempt/final checks, limit→deadline→ordinary failure precedence.
Origin slash joining, overfull-page pagination reason and string UF_XING are closed.
Local PHP8.5.10/Curl8.22 and old preview both have required native capabilities;
these were inspected without any API call or runtime change.

Command: with PATH=/opt/homebrew/bin:/Applications/Docker.app/Contents/Resources/bin:$PATH,
`php tests/InstallationProcess/bitrix_workforce_delivery_001_test.php`.
Final external bitrix-delivery-20260907/red-v4.log: exit1,9 SETUP_OK,9 CLEANUP_OK,
9 intended factory-absence failures AFTER independently healthy native verified TLS.
Wrong-hostname fixture health uses its matching name with native Curl RESOLVE to
loopback, still with peer and hostname validation; no insecure TLS setup shortcut.
Actual target request assertions remain behind missing factory and are not GREEN.

Tests comprise PHP driver, fixture, bounded PHP client worker and native Python
HTTPS server. Each fixture has generated synthetic CA/server keys/token; no real
portal/webhook/DB. Fixtures now use canonical OS temp root for Linux/Mac portability.
Certificate commands, TLS server and PHP workers are bounded and cleaned. Every
fetch is in a bounded worker, including connect-timeout and infinite-loop sensitivity.

Candidate coverage includes exact2page/zero/50, raw nullable strings, request params,
JSON key/type/shape/scope/pagination failures, retry classes, real peer/hostname TLS,
redirect trap, native TCP attempt counting, exact1MiB and16MiB plus overflow limits,
real connect-vs-request timeout, impossible retry delay, rotated/missing/readable-but-
invalid secret metadata. Worker checks actual batch copy isolation and prior batch
stability, not merely JSON-copy isolation. All stdout is restricted to fixture
protocol plus safe summary, stderr empty, destructor output forbidden.

Final hashes for independent Gate3:
- driver6c9788fb2191e7b0d53f5bdfa29c4f42a24fb2f8b63735aa199d3a6ef620c8b1
- fixturefb24ff0fc95e8b7a58d7afca93f3708aced32b6061037dae6d43f386f7e7091f
- PHPworker7be7ea4626c4bbb7cff70f01ea65b3db4a5dd408184c99f87758da14f6de80c0
- Pythonserverefcedf91b2c981508c9253d1f92986f4ed61239f2119bc20d4ea9621c9ea83f3

PHP lint3/Python compile syntax PASS, diff-check PASS. Earlier RED captures retained;
final changes strengthen bounds, TLS wire evidence, raw types and safe output.
Gate3 NOT requested yet; do not implement before independent APPROVED review.
All four RED run handles are terminal. All review agents are idle/completed.

Primary logs only external:
/Users/antropophag/.local/state/fmonitor2-verification/bitrix-delivery-20260907.
No new migration/publication/normalization, cron wiring, credentials, real Bitrix call,
preview change or remote mutation. User requested a session-restart checkpoint here.
