# Independent Gate 3 integration review — INSTALLATION-COMPLETION-SCHEMA-001 card local identity

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/completion_card_local_gate3`
- Reviewed commit: `a7e5a57c1bb05fb52f5977462e46310cfca985cc`
- Executable specification: `specs/INSTALLATION-COMPLETION-SCHEMA-001.md` v1
- Public seam: raw HTTP `GET|HEAD /pilot/objects/{id}` in the unchanged
  completion runtime matrix
- Verdict: **APPROVED**

The reviewer did not author or edit the specification, test, support router,
production implementation, or RED evidence. This append-only review record is
the only change made by the reviewer.

## Reviewed identities

```text
c6f3cf995a81d214559d4078696f82d6d2cfaa1123120cb91775fc5c6b5c5448  specs/INSTALLATION-COMPLETION-SCHEMA-001.md
6185d23e584a892de6ce38046c320e7113c2b755e96b5e458f20a2054b1b62e2  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
1331bc60607cd8a6c7f7e87a0bcb1d837a727c90e00465185dfd789c2ebbb682  tests/Support/installation_completion_runtime_router.php
42cfffe113e783cc94967dde8d90ff03b60422f418fd4f36af698615868fe058  docs/operations/installation-completion-card-local-identity-red-2026-09-04.md
910fce64bfc23ec73a4b813effabe6ab1b1dfb503963eafd6c402dd06ebbb9ba  app/PilotHttp/LocalAuthenticatedHttpUser.php
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
```

## Traceability and seam

Specification scenario E requires the authenticated object card to remain a
successful read on the exact v10 completion family, while scenario F requires
the same card GET/HEAD to fail closed on missing or drifted completion schema.
The reviewed verifier exercises the real production HTTP entrypoint and the
real queue, card, checklist and completion consumers under a DML-only database
principal. It does not replace authorization, card reads, schema readiness or
response mapping.

The fixture supplies one active local user `901`, one active assigned role
`902`, and byte-exact `objects.read` (plus the independently needed checklist
permissions). `FMONITOR_AUTH_USER_ID=901` is therefore the positive admission
and representation identity. `REMOTE_USER=completion-schema@example.invalid`
is descriptive only: no legacy user row is seeded, so it cannot become positive
authority or display identity. The same exact fixture already produces a
successful object queue response before the card call.

The expected exact-family card status `200` is specified independently of the
current implementation. The expected missing/drift `503`, `Retry-After: 60`,
no-store, exact redacted body, GET/HEAD length parity, no redirect and zero
mutation are likewise derived from scenario F rather than production output.

## Independent RED reproduction

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php

Fatal error: Uncaught TestFailure: exact GET card observable status
Expected: 200
Actual: 503
exit 255
```

The run first completed both hostile HTTP matrices. For each of `missing` and
`drift`, queue, card GET/HEAD, checklist GET/HEAD and authorized completion POST
all met the exact fail-closed assertions and the full before/after state stayed
unchanged. It then migrated and seeded the healthy `exact_` family, admitted the
same actor to the queue with status `200`, and stopped on the immediately
following card GET. Cleanup completed, so the failure is neither database setup,
schema migration, server startup nor stale residue.

Current routing explains the sensitive failure without making the expectation
self-confirming: the generic configured card branch resolves the user through
the legacy email directory. Because the fixture deliberately has no matching
legacy user row, the route maps that lookup failure to the approved redacted
`503`. A correction that merely adds a legacy row or treats `REMOTE_USER` as
authority would conceal the defect and is outside the approved GREEN.

Syntax checks for both reviewed executables and `git diff --check` passed.

## Minimal GREEN boundary

Gate 4 may change only the object-card `GET|HEAD` identity/admission branch so
that:

- when a trusted positive local actor ID is present, the route resolves the
  active local profile and requires byte-exact `objects.read` through the
  existing `LocalAuthenticatedHttpUser` seam;
- that local profile supplies the card's display identity; `REMOTE_USER` and
  legacy directory rows do not supplement or override it;
- an absent trusted local actor ID retains the existing legacy compatibility
  lookup; malformed, inactive, missing or unauthorized local identities do not
  fall back to legacy authority;
- the change is route-local to object-card GET/HEAD and does not broaden
  checklist, completion, prepare, list, command or mutation authorization;
- completion readiness remains after successful authentication/authorization,
  so the reviewed missing/drift matrices keep their exact fail-closed outcomes.

The existing `LocalAuthenticatedHttpUser::resolve()` behavior matches this
boundary: it falls back only when the trusted actor field is absent, rejects a
present invalid value, reads an active local profile, checks the requested exact
permission, and returns only that permission in the HTTP user representation.
Reusing it with `objects.read` is sufficient; changing the verifier, adding a
legacy positive fixture, weakening schema failures, or granting a broader
permission would require a fresh Gate 2/3 cycle.

## Gate 3 checks

- Traceability: PASS — exact-family success and missing/drift failure map to
  executable scenarios E and F.
- Public seam: PASS — real raw HTTP GET/HEAD through production composition.
- RED specificity: PASS — healthy queue succeeds and the first exact failure is
  the card's legacy-only identity resolution.
- Expected-value independence: PASS — status and failure responses come from
  the approved specification.
- Rejected cases and fail-closed behavior: PASS — both hostile schema states,
  GET/HEAD parity, POST, headers, redaction and zero mutation execute before RED.
- Determinism and isolation: PASS — unique database/user/prefixes, DML-only
  runtime principal, bounded local fixtures and unconditional cleanup.
- Sensitivity: PASS — absence of a legacy user row makes any continued legacy
  lookup fail while the local actor/grant and preceding queue success prove the
  intended positive path is constructible.

## Verdict

**APPROVED.** Gate 4 may implement the minimal route-local card identity change
above without editing the reviewed test/support artifacts. Any expectation or
fixture change returns this integration correction to Gate 2 and requires a new
independent Gate 3 review.
