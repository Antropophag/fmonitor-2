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

## Gate3 corrected candidate

First Gate3 returned plan traceability/binding and pre-ID cleanup findings.
Root added the ownership context and real deterministic cleanup-failure probe.
Separate per-row mappings were attempted and rejected by the existing planner
(`duplicate test mapping`); its controlling spec line26 forbids one test file in
multiple mappings. The grouped acceptance is now explicit in the normative spec,
with all seven checks mapped in design. No behavior or test is dropped, no dummy
wrapper files or planner-contract changes are introduced.

The new source and plan are retained together outside the repository in
`/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-proxy-gate3-corrected`
and a sibling binding manifest; it records base/patch/plan/RED digests. This avoids
self-referential plan-digest churn in the hashed repository source.

## Gate4 complete / Gate5 candidate

Corrected Gate3 APPROVED. `/root/implement76_proxy` authored only the two nginx
configs and Yii Dockerfile. Named escaped JSON contains exactly request_id,
method, uri, status, request_time, upstream_status, upstream_response_time.
Activation request error sink is suppressed; ordinary/startup diagnostics and
FastCGI handoff remain. runtime-base/app stage split adds no packages.

Real cached-image and default runtime-base paths GREEN:
`/tmp/76-proxy-gate4-cached-green.log`, `/tmp/76-proxy-gate4-default-green.log`.
Explicit lightweight build proof `/tmp/76-proxy-runtime-base-build.log` includes
only PHP base/runtime-base steps, no Composer/shlz stages. Default image:
`fmonitor2-nginx-test-base:e986bb91b290f3fd`,
`sha256:6be4e379353473c6c94ec5394998d152116dc623482eb07dc422f9348038f1b1`.
Cached evidence image `sha256:acd86733b1a6d8cdff08170b743141ec870095c07edaec88121d437cab383e6f`.

All required focused commands GREEN, plus actual architecture PASS7/qualification
(`/tmp/76-proxy-architecture_check-serial.log`). Initial actual checker overlapped
its own unit fixtures and encountered a disappearing test fixture; failure retained
in `/tmp/76-proxy-architecture_check.log`. Corrected by serial execution, no source
or baseline change. This is not hidden or called a code regression. No full local
make test, CI, commits or stand changes were performed by the executor.
No labelled test containers remain. Independent Gate5 pending; combined CI follows.

## Independent approval

Gate5 APPROVED with no findings; independent real-nginx repeat passed both
configurations and preserved container inventory. Review record:
`reviews/code/ACTIVATION-PROXY-LOG-001.md`. Exact reviewed snapshot
`/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-proxy-gate5`,
patch `e71c2d6a421dcfc4e278d43e91620c1485e14e4c0e87a346886f79b5f1f5fd35`, base70cd3280.
Two Gate3 verdicts (return then approval) and one Gate5 approval. The grouped
acceptance follows the unchanged planner contract. Source and metadata will now
join the separately approved user-access implementation before one combined CI.
