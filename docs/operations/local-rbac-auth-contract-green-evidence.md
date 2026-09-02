# LOCAL-RBAC-AUTH-CONTRACT-001 — Gate 4 GREEN evidence

- Дата: `2026-09-02`
- Scope: OpenSpec tasks `4.1`–`4.4`
- Approved spec SHA-256: `f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392`
- Approved application test SHA-256: `838e6fe4eb4d978cd92385b1615f923c18c763ad858a1b37e2ee1a5e8c386000`
- Approved route test SHA-256: `bfbf9f1a9ced25873dcb189e384829680033ebf571565f7b0c2c80661bbb7c7a`

Approved tests и их expectations в Gate 4 не изменялись.

## Реализация

- Application-owned `IdentityAccess` seam возвращает только `AUTHORIZED`,
  `AUTHENTICATION_REQUIRED`, `ACCESS_DENIED` или
  `AUTHORIZATION_UNAVAILABLE`; application contract не зависит от HTTP,
  rapid-pilot либо MariaDB.
- Production MariaDB adapter выполняет один parameterized statement по одному
  current committed snapshot, проверяет все активные звенья local grant и
  сравнивает permission через `BINARY` exact equality.
- Только `GET /pilot/objects` подключён к literal `objects.read`. Local actor
  берётся из trusted process environment; query, cookies, headers,
  `REMOTE_USER`, legacy identity и имена не участвуют в решении.
- `401/403/503` возвращаются до object-list handler/read. Unavailable response
  содержит opaque 12-hex correlation ID, а internal event — только ID и одну
  safe category из закрытого allowlist.

## GREEN и regression evidence

```text
php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

php tests/Verification/characterize_local_rbac_route_mappings_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 current mapping characterization

php rapid-pilot/verify-auth-hot-path.php
PASS auth hot path is schema-mutation free

php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

make lint
PASS

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

git diff --check
PASS
```

`make unit-test` прошёл все исполненные до infrastructure-dependent calendar
verifier проверки, включая новый contract и соседние application/security
regressions. Затем Docker image build не получил credentials для
`docker.io/alpine/git:2.49.1` (`error getting credentials`, WSL vsock timeout).
Это `BLOCKED_EXTERNAL`/environment failure, а не assertion regression.

Прямые legacy `pilot_http_auth_001_test.php` и `pilot_object_list_001_test.php`
не дошли до route assertions: их fixture всё ещё жёстко ожидает canonical
schema v8, тогда как текущий migration runner корректно сообщает v9. Обновление
этих fixture expectations не входит в Gate 4 и не позволяет менять approved
LOCAL-RBAC tests; оно остаётся отдельной harness/fixture работой.

## Gate state

Gate 4 minimal GREEN завершён. Gate 5 code review, full verification и Done этим
record не утверждаются.
