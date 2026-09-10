# Independent Gate 5 code review — ACTIVATION-PROXY-LOG-001

- Date: 2026-09-10
- Reviewer: independently tasked agent `/root/review76_proxy`
- Production author: independently tasked agent `/root/implement76_proxy`
- Source base: `70cd32807b0bf598f1ba331000125f4d3985ba61`
- Source snapshot: `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-proxy-gate5`
- Source patch SHA-256: `e71c2d6a421dcfc4e278d43e91620c1485e14e4c0e87a346886f79b5f1f5fd35`
- Verdict: **APPROVED**

The reviewer authored neither the approved specification/test nor the three-file
production implementation. No production, test or specification artifact was
edited during this review. This record is the reviewer's only change.

## Findings

No blocking or non-blocking findings.

## Specification and security conformance

Both nginx configurations define the same named `escape=json` access format with
exactly the required fields: request ID, method, normalized URI, status, request
time, upstream status and upstream response time. The format omits raw request,
request URI, arguments/query, Referer, User-Agent, cookies, Authorization and
body. JSON string fields are escaped, status remains numeric, and `$uri` excludes
the query component covered by the activation-secret contract.

Each configuration adds an exact `location = /pilot/activate` and directs that
location's request-associated error log to `/dev/null`. Ordinary routes retain
the inherited stderr error log and therefore retain upstream/startup diagnostics.
The activation location preserves the prior entrypoint, include, SCRIPT_NAME,
HTTP_HOST, read timeout and FastCGI upstream for its configuration. Default nginx
request handling continues to forward method, query, headers and body. Response
behavior is unchanged: unavailable FPM remains HTTP 502.

The implementation is symmetrical across the Yii and runtime contours, apart
from their pre-existing `yii.php`/`runtime.php` entrypoints and runtime document
root parameter. No application, domain, session, persistence or stand boundary
was changed.

## Build topology and maintainability

`deploy/yii2/Dockerfile` only names the existing PHP/nginx package layer
`runtime-base` and starts the final application stage with `FROM runtime-base`.
The final stage retains the same workdir, Composer binary and dependency install,
application/config/public/deployment copies, environment, numeric user, exposed
ports and command. No package, privilege or runtime file changed. Building the
named target can therefore reuse the real nginx/PHP base without executing the
independent Composer or shlz stages, while the default final image retains the
existing assembly.

The small duplication between the two nginx files follows their existing
separate deployment ownership and keeps the privacy rule explicit at both live
boundaries. It introduces no unjustified abstraction or code smell requiring a
return.

## Verification evidence

The snapshot manifest resolves to the stated base and patch digest. The exact
production hashes reviewed are:

```text
d8943f7efb3ad57e75601ed842540af065548470af4c3a63445f1c8bacbbc670  deploy/yii2/nginx.conf
daa60fe26c77c4572418f7c24fa05be1e357eb733cdc6e1fe2948ee934356b6e  deploy/runtime/nginx.conf
e986bb91b290f3fd4f4f8f5e89051dd77e50a62c3e1cee5c2252f937b4e30585  deploy/yii2/Dockerfile
```

The current bound verification plan matches the handoff digest and is valid:

```text
4797d986fd59ab57fbb2779ba68883e8dc544617df612bf6e33bae064b700d7f  .local/verification/privacy-plan.json
python3 tools/delivery/change-verification.py check \
  --plan .local/verification/privacy-plan.json
CHANGE_VERIFICATION_OK
```

The reviewer independently ran the approved real-nginx acceptance against the
cached exact nginx image, without a build or pull:

```text
FMONITOR_TEST_NGINX_IMAGE=fmonitor2-yii2:auth-pr \
  python3 tests/Runtime/activation_proxy_log_001_test.py
PASS: ACTIVATION-PROXY-LOG-001 both real nginx configurations
# exit 0
```

Full reviewer output is retained at `/tmp/76-proxy-gate5-review-green.log` and
synthetic evidence at
`/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fmonitor-activation-proxy-d4a0afit`.
Container inventories before and after were identical; no ownership-labelled
test fixture remained.

Executor evidence was inspected:

- cached and default runtime-base acceptance paths both pass;
- the explicit runtime-base build contains only base metadata and the two
  runtime-base steps, with no Composer or shlz stage execution;
- production runtime, Yii runtime, Yii dependency, runtime storage, deployment
  composition, verification inventory, change-verification and architecture
  guard obligations pass;
- the serial architecture check passes all seven rules plus the required HTTP
  qualification.

The retained initial architecture failure was a checker overlapping its own
temporary unit fixture: collection observed a fixture after its concurrent test
removed it. Serial execution passed without any source or baseline change. The
failure is fully recorded at `/tmp/76-proxy-architecture_check.log`; the passing
serial evidence is `/tmp/76-proxy-architecture_check-serial.log`. This does not
indicate a candidate regression and is not hidden by a source correction.

`openspec validate activation-proxy-log-privacy --strict` and `git diff --check`
also pass. Full local `make test` and CI were correctly deferred to the single
combined exact-source CI run.

## Gate decision

Gate 5 is **APPROVED** for the exact source snapshot and production hashes above.
The implementation conforms to the approved privacy, diagnostic, parity,
isolation and build-topology contract, and the approved test catches the original
access/error log leaks at both nginx boundaries. This slice may be combined with
the separately approved Yii admin candidate for one authoritative CI run. Any
later production or approved-test change requires review of that delta.

---

## Combined integration Gate 5 disposition — 2026-09-10

- Reviewer: independently tasked agent `/root/review76_proxy`
- Combined committed head: `97ab6cbe1079d7070399e41b57c65ae75d37af3d`
- Integration snapshot patch SHA-256: `8ae7e80bc7a96025558461c8bdd1d542441c13d64863d8a2640357aedd64fc6d`
- Verdict: **APPROVED**

No findings. The merge retained the complete union of both approved registry
groups. The admin candidate has no `app/` or `config/` delta from its approved
`83049f45` source. The proxy production bytes still match the prior Gate 5:

```text
d8943f7efb3ad57e75601ed842540af065548470af4c3a63445f1c8bacbbc670  deploy/yii2/nginx.conf
daa60fe26c77c4572418f7c24fa05be1e357eb733cdc6e1fe2948ee934356b6e  deploy/runtime/nginx.conf
e986bb91b290f3fd4f4f8f5e89051dd77e50a62c3e1cee5c2252f937b4e30585  deploy/yii2/Dockerfile
```

The only post-merge source delta is the reviewed two-line exact E2E expectation
addition. Inventory passes 15/15, the CI-policy suite passes 15/15, both feature
plans check against the combined source, and actual architecture passes all seven
rules plus HTTP qualification. No production, feature-test, specification or
acceptance behavior changed during integration.

The combined integration checkpoint is **APPROVED** at Gate 5. The exact
candidate may proceed to the single authoritative full CI run; this disposition
does not claim that CI or deployment has already completed.
