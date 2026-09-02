# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v6

Date: 2026-09-02  
Reviewer: fresh independent agent `/root/prepare_test_rereview_v6`  
Test author: `/root/prepare_rbac_red`  
Gate: corrected Gate 2 RED v6 after the incomplete-body probe was found not to
reach the application  
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the reviewed tests nor production code and did
not edit either during this review.

## Reproduction

The owner-approved executable/OpenSpec hashes remain unchanged from the v5
approval except for the expected task-state checkbox. OpenSpec is strict-valid,
all three reviewed PHP files are syntactically valid, and diff hygiene passes.

```text
openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_prepare_form_001_test.php

php -l tests/Support/PrepareRendererInvocationSpy.php
No syntax errors detected in tests/Support/PrepareRendererInvocationSpy.php

php -l tests/Support/pilot_prepare_renderer_spy_router.php
No syntax errors detected in tests/Support/pilot_prepare_renderer_spy_router.php

git diff --check
# no output
```

The canonical raw-HTTP run reaches the application for all three complete
unsupported-method requests and reproduces the intended successor-aware RED:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php

TestFailure: PUT before authority/DB allow
Expected: 'GET, HEAD, POST'
Actual: 'GET, HEAD'
```

No `t_ppf_*`/`foreign_ppf_*` database, `ppf_*`/`foreign_*` user, or matching
task/foreign temporary root remained after the failure.

## Blocking finding

### R1 — the completed-body replacement is not sensitive to a body read

The v6 helper fully writes the 258-byte payload, half-closes the client write
side, waits for a prompt response, and proves that the literal payload sentinel
is absent from the response. The decorate counter additionally proves that each
request reaches the canonical factory/router, while the invalid DB credentials,
missing identity, zero render delta, exact 405 assertion and per-request
DB/filesystem guards prove the remaining method-precedence and no-mutation/no-
render properties.

But these observations are identical for two materially different
implementations:

1. reject the method before reading the request body;
2. read all 258 bytes, discard them, then return the same 405 response.

Because every declared byte is already available to the server, neither the
prompt-response condition nor absence of the sentinel from the response makes
the forbidden read observable. The v6 verifier therefore does not prove the
normative section 3 requirement that unsupported `PUT|PATCH|DELETE` return 405
“without body read.” This is a sensitivity blocker under Gate 3.

The correction must retain a request that actually reaches the canonical
factory/router while making an application body read independently observable.
If PHP's built-in server cannot provide that observation at this public seam,
the executable contract must be amended and owner-approved rather than silently
treating no-echo as no-read. The exact 405 `Allow: GET, HEAD, POST`, decorate and
render counts, snapshots, cleanup, positive spy control, allowed GET/HEAD
counts, and the full v5-approved authority/predecessor matrix must remain.

## Reviewed hashes

```text
d591fd30f356ac59cfea34623a8311d07eb39cf41442892bbe42ef7d9d2e6062  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
d791104bf14b17911b4e23e90d0eef7a3e0f7f41cb12960c4ca4e9eec3fc9e97  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
eb386f4b40c976dcd9d371eda5f081763404ce8193017ff004fb147a825c9b60  openspec/changes/pilot-prepare-rbac-fixtures/design.md
6829cb04ccf50a03cd68f3bbd3ce09aa9a8208185a74a906f55e7c913fe3b1d5  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
494e3b3cb77e20c5448fd0f3265c4dcf9420da72316f126bd59b92509c4a1c39  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
2657f1f760e9bef056e8b462b5f313087569889fecdab6a9d01e006c4671c2c5  docs/operations/pilot-prepare-rbac-v2-api-exact-hash-approval-2026-09-02.md
0be786b4a6c889e9dcc9b0e4e9e36a32360fa2915f86d8206b7673e8abcca094  tests/InstallationProcess/pilot_prepare_form_001_test.php
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
7bef82320c08b1f21f3316a3f5872f2c74e7cfc8471a0fa9ff95b5329f9521c6  tests/Support/pilot_prepare_renderer_spy_router.php
0b7bc15dd3e777ff8dec94fdbbb5504d30aab3866111fc885a78a3e24b97885e  docs/operations/pilot-prepare-rbac-fixtures-red-evidence.md
```

Any test or support change restarts Gate 2 and requires a fresh independent
Gate 3 review.
