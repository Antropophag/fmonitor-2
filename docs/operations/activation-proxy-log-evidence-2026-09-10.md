# Activation proxy log privacy — #76

Root authored spec/test and dependency analysis on base8c446873 in isolated
`fmonitor-2-nginx-privacy-76`. This supplements the ongoing Yii user-access slice.
Both will be integrated before one authoritative full CI; no stand switch.

## RED evidence

Required plan: `openspec/changes/activation-proxy-log-privacy/verification-input.json`
→ `.local/verification/privacy-plan.json`, generated/read before tests and refreshed
for final candidate. Local image override selects the already-built runtime nginx:

`FMONITOR_TEST_NGINX_IMAGE=fmonitor2-yii2:auth-pr python3 tests/Runtime/activation_proxy_log_001_test.py`

Exit1, full inventory `/tmp/76-proxy-privacy-final-red.log`: both configs pass nginx
syntax, intentional invalid-config diagnostics remain visible, all HTTP requests
reach the intended502. Both configurations leak query token A/Referer B to access
and error logs; User-Agent C also reaches access logs. Structured access records and
exact activation error boundary are absent. These are intended missing behavior,
not setup failures. All uniquely owned temporary containers removed successfully.
Raw synthetic evidence outside repo:
`/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fmonitor-activation-proxy-85ot78uo`.

Inventory15/15 GREEN; historical digest retained with one explicit E2E addition.
No new package or schema. CI default path will build the new lightweight
runtime-base target; local RED uses existing nginx with candidate configs mounted
readonly so an absent build target cannot masquerade as a privacy RED.

Independent Gate3/implementation/Gate5/full CI pending. The nginx stdout/error
finding is independent of the PHP application-harness log finding withdrawn in
user-access Gate3: these are different logging boundaries.
