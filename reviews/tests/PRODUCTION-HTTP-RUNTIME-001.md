# Independent Gate 3 review — PRODUCTION-HTTP-RUNTIME-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Test author: separately tasked agent `/root/runtime_tests`
- Plan author: separately tasked agent `/root/runtime_plan`
- Verdict: **APPROVED (aggregate rereview; see final section)**

The real Compose harness establishes a valid first RED and useful coverage, but
does not yet prove several material requirements in the proposed runtime slice.
This reviewer did not author the specification, tests, implementation, or RED.

## Reviewed tests

```text
da9f97fc895a52dab0f061e1885abf62be82f94728d361cb311a83522bb5f4a5  tests/Runtime/production_runtime_contract_001_test.php
15120d98acd511aca5c90c05708cf4199f2c46568df19775de5a24e83d91c307  tests/Runtime/production_runtime_compose_001_test.php
```

The lock and v22 tests have separate bounded Gate 3 records and are not rejected
by this verdict.

## Demonstrated baseline RED

```text
$ php -l tests/Runtime/production_runtime_compose_001_test.php
No syntax errors detected in tests/Runtime/production_runtime_compose_001_test.php

$ php tests/Runtime/production_runtime_compose_001_test.php
Fatal error: Uncaught TestFailure: INTENTIONAL_RED: production Compose definition exists
Expected: true
Actual: false
exit 255
```

The failure is the intended missing production deployment artifact. It occurs
before Docker orchestration and is not an environment/setup failure. The static
contract test independently fails on the absent `deploy/runtime/Dockerfile`.

## What the current tests prove after that RED

The static test checks absence of the development server, `socat`, and demo
manifest dependencies; presence of nginx/FPM/front-controller composition; all
explicit configuration keys in configuration and Compose; invalid/missing config
rejection; separate migration service; persistent state declaration; and fixed
UID/GID. The Compose harness uses isolated project/volumes and real nginx/FPM to
check live/ready, login GET, CSS, unknown route, untrusted Host, a DML-only DB
principal, a state-volume sentinel across process restart, and DB outage/recovery.

## Blocking findings

1. The route/auth contract requires cookie, local-auth and CSRF context across GET
   and POST. The harness only GETs login and searches for `csrf`; it never retains
   a cookie, submits login, invokes an authenticated protected mutation, rejects an
   invalid CSRF token, or proves absence of the corresponding success fact/audit.
2. The DML-only requirement explicitly includes reachable checklist sync paths.
   Current probes cover readiness/login/assets but no ordinary authenticated DB
   mutation or checklist path, so transitive DDL or a route requiring DDL can pass.
3. Restart preservation is tested with an arbitrary touched sentinel. It does not
   preserve and reread a MariaDB fact/history event, real authenticated session, or
   private artifact through its authorization seam as required.
4. The separate prepare seam has no first-run/replay or symlink, wrong type, wrong
   owner, permissive mode, and content-preservation barriers. A successful one-shot
   invocation alone cannot prove fail-closed path handling.
5. DB outage is covered, but absent/incompatible schema is not snapshotted before
   and after readiness to prove that readiness performs no repair or other write.
6. The harness contains no `SIGQUIT`/active-request test, no refusal of new traffic
   during drain, no child/orphan check, and no 60-second stop bound.
7. Status is checked for readiness failures, but response bodies and container logs
   are not checked for password/config disclosure.

These are observable acceptance statements in the delta specification and are
also explicitly listed in OpenSpec task 1.2. Static source regexes cannot substitute
for the missing public-seam evidence.

## Gate decision

**CHANGES_REQUESTED.** Keep the existing first RED and useful assertions. Add the
missing public-seam barriers above, or split the specification and Gate 3 records
into honestly bounded slices before implementation. Full Gate 4 for the HTTP
runtime is not approved by this record.

## Aggregate rereview — final #33 test candidate

The initial verdict above remains useful history. Each blocking area was returned
through a bounded RED/review cycle, and the final aggregate test candidate now
closes those findings.

```text
86c01bf40652775f004d52a9162204379ec04c40b77640426984bb0e38d74609  specs/PRODUCTION-HTTP-RUNTIME-001.md
e50faab0f52aabbea1662d5d9f188b1aa25ea279fa561288258a94717c4fbeef  tests/Runtime/production_runtime_contract_001_test.php
1ca9da7c730eabbaa0fea38619de6079bbcd90517331ef2c3cf1162124110955  tests/Runtime/runtime_storage_001_test.php
78628a1a19bc48885debbba5978cdfdbbe4114634c9590b183bd741ea25800e3  tests/Runtime/migration_concurrency_lock_001_test.php
afb272160708221241e88464507fab4a12cf286ec48d0e76564c7404eefd863b  tests/Runtime/migration_parallel_runners_001_test.php
2f3293f3ce4b7b5fbb465b15d7a83bf4d59258775a8fd58322726127ed7dcc2c  tests/Runtime/production_schema_frontier_001_test.php
d92c90f7b64b4980272e5204dec9b080a1dca1002b67416c535f264717cb39f9  tests/Runtime/production_schema_preflight_001_test.php
75ca760da80c5960c93df418cf27b208d4a3bbf22c13cf106d3ce5ea13bfd6be  tests/Runtime/production_readiness_schema_001_test.php
b7c042ec6d787220b6cf049e01968578d23a4cc9e601f0129eb9a9657ce244a2  tests/Runtime/production_process_readiness_001_test.php
799e824a6fee1d3cc1a448892a971a817ac4a17f18f478e8ba11bb18cc882edd  tests/Runtime/session_contention_001_test.php
abd058072f43c1ba25af0ed3f8fd3c84d665f2792571ffb2844d0aa3d9c91efa  tests/Runtime/production_runtime_compose_001_test.php
716300076cfda350c08d52e41c2238eb8b6f95eb82579a50e21483afe5ab8074  tests/Runtime/production_runtime_browser_001_test.php
```

The RED lineage is retained in the bounded Gate 3 records: absent packaging/config,
whole-catalogue lock contention, v22 registration and preflight metadata, missing
route/process readiness families, trusted HTTP scheme, transient native session
contention, and FPM drain. Supplemental tests then prove two actual application
runners, native nginx active-response drain/new-traffic refusal, exact DB/session/
artifact persistence, authenticated cookie reuse, admin HTTP cookie flags, and the
full protected browser journey through nginx/FPM.

The immediately-GREEN persistence/admin/OTIZ tails are supplemental regression and
acceptance evidence, not claimed as new demonstrated REDs. Existing PDF, checklist,
completion and OTIZ behavior already has its own executable specifications and
historical reviews.

The OTIZ browser segment in this #33 candidate uses a separately seeded synthetic
registered order to establish financial eligibility. It proves production transport
and UI parity, but does not prove that a fully native assignment/application journey
feeds OTIZ without that legacy-shaped row. That product integration gap is captured
separately by `NATIVE-OTIZ-INPUTS-001` for the next #24 slice and is not part of the
#33 production runtime implementation.

### Final Gate 3 verdict

**APPROVED.** The aggregate #33 test suite is traceable to the normative runtime
contract, uses public CLI/HTTP/process seams, retains specific RED evidence, and has
sensitive rejection, persistence, concurrency and lifecycle barriers. Gate 4/5 may
integrate the reviewed production runtime candidate. Any expectation changes require
rereview.
