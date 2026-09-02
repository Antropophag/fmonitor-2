# PILOT-PREPARE-RBAC-FIXTURES-001 — Gate 2 RED evidence

Дата: 2026-09-02  
Автор RED: independent agent `/root/prepare_rbac_red`  
Verdict: **QUALIFYING RED v7 / fresh Gate 3 review required**

## Gate 2 replacement RED v7 — owner-approved v3 transport boundary

The verifier now cites the owner-approved v3 package. Fully delivered bounded
`PUT|PATCH|DELETE` requests require exact public 405, exact
`Allow: GET, HEAD, POST`, no payload-derived response bytes and zero state
change. Each request must record exactly one factory-composition
`decorate()` call and zero request-time wrapped-renderer calls. Missing actor
and deliberately invalid DB credentials prove application method admission
precedes authorization, database, domain and form work.

The verifier makes no assertion about whether PHP's built-in transport buffered
or consumed the delivered body before application invocation, and it exposes no
body-observation seam. Earlier contrary statements below are retained only as
explicitly superseded history.

Task 2.1 is complete against v3. Task 2.2 remains open pending a fresh
independent Gate 3 review.

Canonical run reaches the application and reproduces the intended v3 RED after
the exact decoration/render counters and zero-state guards execute:

```text
TestFailure: PUT before authority/DB allow
Expected: 'GET, HEAD, POST'
Actual: 'GET, HEAD'
```

Exact v7 verifier hashes:

```text
edda5307311eb395e104e34f407a02f01f2bbf255d17476a1901b6e99ada2886  tests/InstallationProcess/pilot_prepare_form_001_test.php
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
7bef82320c08b1f21f3316a3f5872f2c74e7cfc8471a0fa9ff95b5329f9521c6  tests/Support/pilot_prepare_renderer_spy_router.php
```

## Gate reset — PHP built-in transport boundary

Gate 3 v6 correctly established that a fully delivered body cannot prove
transport-level no-read: PHP's built-in HTTP server may buffer or consume the
body before invoking PilotHttp. The former application contract therefore made
an impossible observable claim. The v3 Gate 1 amendment removes only that
claim. It preserves public fully-delivered `PUT|PATCH|DELETE` exact 405 with
`Allow: GET, HEAD, POST`, no payload-derived response bytes, exactly one
composition-time `decorate()` call per factory composition/request, zero
request-time wrapped-renderer `render()` or compatibility-render invocations,
zero DB/process/artifact/session/file mutation, and application admission before
authorization/domain/form work. No hidden body-observation seam is introduced.

All evidence below and prior Gate 2/3 reviews remain append-only history. They
do not authorize test or production edits against v3. Task 1.5 requires fresh
independent Gate 1 review and explicit owner approval of new exact hashes before
a replacement Gate 2 run.

## Gate 2 restart v6 — complete unsupported-method requests

Gate 4 discovery proved that the former incomplete-body probe was stopped by
PHP's built-in HTTP server before the router/application ran. It has been
replaced test-only with a fully delivered, bounded 258-byte binary payload for
each of `PUT`, `PATCH` and `DELETE`. The client writes every declared byte,
half-closes its write side, requires a prompt complete response and asserts the
payload sentinel is absent from that response.

The canonical decorator counter proves each complete request reaches the real
factory/router once. The renderer counter remains unchanged, the DB password
is deliberately invalid and actor identity is absent, so exact 405 behavior is
still shown to precede authorization/database/render handling. Database and
filesystem snapshots remain unchanged.

Current partial production now executes the application and yields the
successor-aware contract RED:

```text
TestFailure: PUT before authority/DB allow
Expected: 'GET, HEAD, POST'
Actual: 'GET, HEAD'
```

This is a healthy public application invocation and an intended product RED,
not a server/setup timeout. Earlier incomplete-body evidence is superseded.
Task 2.2 remains open pending a fresh independent Gate 3 rereview.

```text
0be786b4a6c889e9dcc9b0e4e9e36a32360fa2915f86d8206b7673e8abcca094  tests/InstallationProcess/pilot_prepare_form_001_test.php
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
7bef82320c08b1f21f3316a3f5872f2c74e7cfc8471a0fa9ff95b5329f9521c6  tests/Support/pilot_prepare_renderer_spy_router.php
```

## Gate 2 correction v5 — executed positive spy sensitivity

Gate 3 v4 requested an executed positive control and exact counts. The exact
spy/decorator classes used by the HTTP router now live in one test-support file.
Before the HTTP exercise, a bounded instrumentation-only control supplies
a literal renderer to that decorator and proves independently determined exact
bytes, one `decorate()` call, one `render()` call and one compatibility render
call. Counters are then reset to zero. This control proves the observation
mechanism is connected; it is explicitly not canonical-wiring evidence and
does not assemble an application graph.

The raw-HTTP router remains canonical-factory-only. It enables a narrow type
assertion that the object received by `decorate()` is the factory-created
`ProductionPrepareFormRenderer`; the decorator still receives no environment,
dependencies or graph. The allowed GET and HEAD are now separate requests with
per-request deltas: each must add exactly one decorate call and exactly one
real-renderer invocation. GET retains the full form/body oracle; HEAD must have
the same status and application headers/content length with an empty body.

The full rejection matrix still runs before the selected assertion. On the
predecessor, the executed instrumentation sensitivity passes and cleanup is
successful, after which the unchanged authorization RED remains:

```text
TestFailure: GET 503 safe correlation /pilot/objects/4512/assignment-order/prepare
Expected: 1
Actual: 0
```

Task 2.2 remains open pending fresh independent Gate 3 rereview.

```text
7e9129b218e917f8bebdf945d2b893da2aa38922686c163845dc63a1518e45f9  tests/InstallationProcess/pilot_prepare_form_001_test.php
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
7bef82320c08b1f21f3316a3f5872f2c74e7cfc8471a0fa9ff95b5329f9521c6  tests/Support/pilot_prepare_renderer_spy_router.php
```

## Gate 2 replacement RED v4 — owner-approved canonical factory API

Owner approved the corrected exact public PHP contract recorded in
`docs/operations/pilot-prepare-rbac-v2-api-exact-hash-approval-2026-09-02.md`.
The test-only router no longer reconstructs the application graph. It calls
only:

```php
ProductionPilotHttpEntrypointFactory::create($environment, $decorator);
```

The decorator cannot create, select or replace a renderer. Its `decorate()`
accepts the renderer created by the canonical factory, returns a wrapper that
counts `render()`/`renderCompatibility()` calls, and delegates exact arguments
and response bytes to that renderer. When the approved production interface is
present, PHP additionally requires the test decorator to implement that exact
interface. A narrow temporary predecessor branch lets the same Gate 2 verifier
run before that missing interface lands; it does not provide a renderer or an
alternate composition graph.

The full authority/predecessor matrix executes GET and HEAD through the
canonical factory router before the first expectation is evaluated: one-sided
grants, inactive/near-match chains, committed local/process revokes, isolated
local fault, the then-approved (now superseded) unsupported-method rule, missing/invalid/replacement
actor, unknown object, wrong state and DB failure. Independent database and
filesystem guards remain around every request, and the shared render counter
must remain zero for every rejection. The later allowed route requires the real
renderer result and therefore makes an ignored/missing decorator observable.

Canonical run:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php
```

Qualifying current-authorization RED after the matrix:

```text
TestFailure: GET 503 safe correlation /pilot/objects/4512/assignment-order/prepare
Expected: 1
Actual: 0
```

The isolated public-API probe also proves that the approved factory seam itself
is absent in the predecessor:

```text
parameters=1 decorator_interface=no identity_decorator=no
```

Thus production alone must add both the canonical decorator API/wiring and the
required local authorization behavior. No production file was changed by this
RED author. Task 2.1 is complete; task 2.2 requires a fresh independent Gate 3
review of the hashes below.

```text
1aad947df36728a9e152dfe21738a2ae2fb620d4b16537efdd939dbbbe58b5b3  tests/InstallationProcess/pilot_prepare_form_001_test.php
e7596aeceb7d0ec70d8c3efefe62c231d9b4f871c1cbaf6d3d4764734f02f128  tests/Support/pilot_prepare_renderer_spy_router.php
```

## Gate 2 correction v3

Independent rereview
`reviews/tests/PILOT-PREPARE-RBAC-FIXTURES-001-v2.md` сохранил один blocker:
rendered-text absence не наблюдала вызов renderer, результат которого мог быть
отброшен.

Test-only raw-HTTP composition router
`tests/Support/pilot_prepare_renderer_spy_router.php` теперь оборачивает реальный
`ProductionPrepareFormRenderer` через существующий public
`PrepareFormRenderer` constructor seam. Каждый вызов `render()` или
`renderCompatibility()` атомарно увеличивает task-owned counter до делегирования
реальному renderer. Production code не менялся.

Полная local/process/revoke/method/identity/object/state/DB denial matrix
запускается через этот HTTP router, после чего exact counter assertion требует
`0`. Поэтому преждевременный вызов renderer обнаруживается даже при отброшенном
HTML. Existing raw response, DB/filesystem snapshots, separate local/process/
object sentinels и historical incomplete-body probe were preserved at that time;
v3 rejects that transport inference.

Canonical v3 rerun проходит renderer counter assertion и сохраняет intended
RED:

```text
TestFailure: GET 503 safe correlation /pilot/objects/4512/assignment-order/prepare
Expected: 1
Actual: 0
```

V3 hashes:

```text
66827a9829011b1c4edaa0b2004d61f89e6656ac22af80b51d4d782238232d1e  tests/InstallationProcess/pilot_prepare_form_001_test.php
69ddd0ff1f88c929ffd9247a77c2e066173a34f57fb41165a500dad8bd8a2d3f  tests/Support/pilot_prepare_renderer_spy_router.php
```

## Gate 2 correction v2

Independent review `reviews/tests/PILOT-PREPARE-RBAC-FIXTURES-001.md` returned
CHANGES_REQUESTED. The verifier now closes every blocking finding:

- local authorization, process capability, object/form reader and renderer have
  separate public-seam sentinels respectively: isolated local-table fault,
  isolated process-table fault, corrupt object fact and unique render-only
  fixture markers;
- local denial cases run while both the process table and object are unusable;
  local-exact/process-missing runs with an unusable object;
- committed deletion/restoration is exercised separately for local permission
  and process capability, through both GET and HEAD;
- every prepare 503 validates safe correlation independently on GET and HEAD;
- missing, invalid (`018`) and explicit replacement actors use isolated env;
- foreign DB, DB user and filesystem-root decoys are verified after exact owned
  cleanup, then removed by their own fixture owner;
- Historical v2 only: PUT/PATCH/DELETE declared an undelivered 1 MiB body.
  v3 supersedes the inference drawn from that probe.

Canonical rerun after the correction executes the complete pre-assert matrix
and fails at the inherited prepare DB-failure correlation contract:

```text
TestFailure: GET 503 safe correlation /pilot/objects/4512/assignment-order/prepare
Expected: 1
Actual: 0
```

MariaDB is healthy; exact fixture setup, raw HTTP invocations, read-only
snapshots, the then-current incomplete-body probes and foreign-decoy preservation all ran
before this assertion. This remains a qualifying public-seam RED, not setup
failure. The helper performs the same safe-correlation assertion for HEAD.

Corrected verifier hash:

```text
3ee482ff2368ff4e2d8118913a2082bfa538ba67aefc22db9e86a6b6705e7c20  tests/InstallationProcess/pilot_prepare_form_001_test.php
```

## Approved contract

Owner-approved executable spec совпал с exact hash:

```text
565804719e95171fa82523f6f883b8abebc9d8f0e36ca9746612fb8f7daab01e  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
```

## Initial v1 verifier evidence (superseded by correction above)

`tests/InstallationProcess/pilot_prepare_form_001_test.php` вызывает только raw
public `GET|HEAD /pilot/objects/{positive-id}/assignment-order/prepare` и
unsupported methods. До первого assertion выполнена полная новая admission
матрица:

- actor с обоими grants;
- `objects.read` only;
- local permission без process capability;
- process capability без local permission;
- inactive local actor и inactive assigned role;
- near-match local permission;
- legacy/process facts без local identity/grant fallback;
- committed local revoke;
- isolated local authorization schema fault;
- unsupported PUT/PATCH/DELETE до authority/DB;
- missing actor, unknown object, wrong state и DB failure precedence.

Corrupt object fact служит downstream handler-read sentinel для local denial
cases. Каждый HTTP invocation также проходит existing full DB snapshot и
filesystem/artifact read-only guard; GET/HEAD parity проверяет empty HEAD body.

## Initial v1 canonical run

```text
make test-env-up
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php
```

Environment setup прошёл: MariaDB container healthy, fixture schema/data и HTTP
servers созданы. Verifier завершился с expected Gate 2 failure:

```text
TestFailure: local authorization schema fault status
Expected: 503
Actual: 403
```

Это qualifying fixture-authority RED: fault затрагивает только
`fm2_pilot_role_permissions`; route отвечает process denial 403 вместо stable
local authorization 503 с safe correlation, следовательно exact prepare GET/HEAD
ещё не владеет обязательным local gate до process/object reads.

Отдельно сохранён predecessor mismatch, который не принят как qualifying RED:
текущий unsupported-method response объявляет `Allow: GET, HEAD`, тогда как
approved successor-aware contract требует `GET, HEAD, POST`. Assertion не
ослаблен; он будет виден после устранения основного authority RED.

## Initial v1 hash

```text
4866119c27596a5450ad442466eb994637d97e1dd9675c62473af5875fbe70ee  tests/InstallationProcess/pilot_prepare_form_001_test.php
```

Task 2.1 остаётся закрыт после correction. Task 2.2 открыт до fresh independent
Gate 3 review; production implementation не выполнялась этим автором.
