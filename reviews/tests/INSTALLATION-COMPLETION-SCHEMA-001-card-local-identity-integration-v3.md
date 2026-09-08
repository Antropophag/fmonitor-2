# Independent Gate 3 rereview v3 — INSTALLATION-COMPLETION-SCHEMA-001 readiness ordering

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/completion_card_local_gate3`
- Reviewed test commit: `918a5670a72ec6819db38d544e0cab191e65a1f9`
- Reviewed evidence head: `f24df6631c8be1c25a75e2b6976c7157569a127f`
- Specification: `specs/INSTALLATION-COMPLETION-SCHEMA-001.md` v1
- Public seam: configured production HTTP queue/card/checklist/completion matrix
- Verdict: **APPROVED**

This reviewer did not author or edit the specification, test, support router,
production, or RED evidence. This append-only record is the reviewer's only
change. It supersedes v2 because the task-owned session configuration changes
the reviewed test hash and exposes an earlier ordering defect than the response
mapping reviewed there.

## Exact reviewed artifacts

```text
c6f3cf995a81d214559d4078696f82d6d2cfaa1123120cb91775fc5c6b5c5448  specs/INSTALLATION-COMPLETION-SCHEMA-001.md
93463d5c519d010e5b37d880ecd0fa975ace7db62bb9175acca077f26cd4cf6d  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
1331bc60607cd8a6c7f7e87a0bcb1d837a727c90e00465185dfd789c2ebbb682  tests/Support/installation_completion_runtime_router.php
1a0161495351ae9c42aa10f66894f7959ba5aa9f694c8025bdeb900e2ee69227  docs/operations/installation-completion-card-local-identity-red-v3-2026-09-04.md
2038a353d090b1a109a3c67dd9144eb39fb85e319e8b79c170ce93a617573d45  docs/operations/installation-completion-card-local-identity-red-v4-2026-09-04.md
```

## Corrected session fixture

The production request owner now receives the already private and unique
task-owned root through `FMONITOR_SESSION_STATE_ROOT`, plus a bounded exact
instance `completion_runtime` and trusted HTTPS scheme. This is required for an
exact healthy checklist page to create/read its real session without falling
through the unavailable default root. It does not stub session storage, create
completion readiness, or change an HTTP expectation.

The same outer `finally` removes the shared task root after dropping the random
runtime user/database and closing the admin connection. Per-prefix tables,
bootstrap homes and wrapper roots retain their inner cleanup. An independent
post-failure search found no `icr-artifacts-*` directory in either system temp
location, confirming cleanup also executes on the current assertion failure.

All configured-graph prerequisites approved in v2 remain: real SHLZ and pilot
CSS, explicit process/legacy prefixes, fixed clock, artifact root, active local
actor `901`, active assigned role and the exact route permissions including
`inspection.item.complete`. No legacy positive identity was added.

## Traceability and readiness ordering

Scenario F says every runtime consumer uses the single read-only completion
family readiness seam and that missing or drifted state fails closed before
completion DML/domain facts or partial HTML. The existing approved runtime
matrix strengthens that observable boundary to a full prefixed-family
before/after snapshot: a rejected read may not create projection state,
backfill attribution, alter schemas, advance counters, or mutate unrelated rows.

That expectation is material here. `ChecklistSync::projection()` is not a pure
read in current production: `revision()` can insert the case's revision row and
`backfillLegacyAssignments()` can append installer attribution for previously
seeded checklist operations. Calling completion readiness only later while
enhancing rendered HTML therefore allows durable checklist facts to appear on a
request whose completion prerequisite is known to be unavailable. Readiness
must reject before projection/backfill, not merely rewrite the eventual HTTP
response.

## Independent RED reproduction

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php

ICR_CHANGED ["missing_fm2_checklist_operation_installers",
             "missing_fm2_checklist_revisions"]
Fatal error: Uncaught TestFailure: missing has no completion DML/schema/repair
exit 255
```

Before the snapshot assertion, all six missing-family exchanges completed and
their exact unavailable response assertions passed: queue, card GET/HEAD,
checklist GET/HEAD, and otherwise-valid completion POST. The failure is thus not
session setup, CSS, authorization, route dispatch, database startup or response
mapping. The full state comparison identifies exactly the two changed tables:

- `fm2_checklist_revisions` changed from zero rows to one projection revision
  row;
- `fm2_checklist_operation_installers` changed from zero rows to attribution
  rows for the 41 seeded completed-item operations.

The completion family remains deliberately missing. The current production
sequence constructs `ChecklistSync`, validates inspection schema, then calls
`projection()` before the later rapid-pilot completion enhancement performs its
completion readiness check. This independently explains the observed mutation
without deriving the expected result from implementation.

## Snapshot sensitivity, determinism and downstream barriers

`icrState()` enumerates every table under the unique mode prefix and captures
full `SHOW CREATE TABLE`, serialized row bytes and row count. It therefore
detects newly created/removed tables, schema repair, row append/update/delete
and counter-affecting DDL. The small conditional diagnostic only prints names
whose complete snapshots differ; it neither changes state nor weakens the final
exact equality assertion.

The missing family is derived from a successful canonical migration by one
admin-owned DROP; drift is derived by one hostile ALTER. The runtime principal
has only SELECT/INSERT/UPDATE/DELETE, so runtime schema repair remains
impossible. Random database, user, per-mode prefixes and filesystem roots plus
fixed time make the run isolated and deterministic.

After readiness is moved before projection, the same unchanged test continues
through all previously reviewed barriers:

1. missing response details, HEAD parity and zero full-state mutation;
2. the complete drift response and zero-mutation matrix;
3. exact queue `200`, then the card-local-identity GET/HEAD expectation using
   active local actor `901` with exact `objects.read` and no legacy user row;
4. exact healthy checklist GET/HEAD using the real session owner;
5. valid 85% PTO POST `303`, exactly one root fact with exact values, stable
   completion DDL and no unrelated mutation;
6. missing/drift/exact DML-only bootstrap and publication assertions.

Thus a response-only mapping change cannot make the current RED green, and an
ordering-only correction cannot conceal the later plaintext, identity, healthy
projection, append/history or bootstrap expectations.

## Gate 3 checklist

- Traceability: PASS — single readiness seam and fail-before-effects behavior.
- Public seam: PASS — real configured HTTP entrypoint and production graph.
- RED specificity: PASS — exact forbidden projection/backfill mutations after
  successful unavailable responses.
- Independent expected values: PASS — readiness and no-side-effect outcomes
  follow the approved spec and prior reviewed matrix.
- Rejected cases: PASS — both missing and drift plus GET/HEAD/POST and bootstrap.
- Sensitivity: PASS — complete schemas, rows, bytes and counts are compared.
- Determinism/isolation: PASS — canonical setup, DML-only runtime, fixed clock,
  unique namespaces and attempt-all cleanup.

## Verdict and Gate 4 boundary

**APPROVED.** Gate 4 may minimally ensure completion-family readiness for the
configured checklist page before any checklist projection/backfill or other
DML. It must preserve the exact plaintext unavailable response for page
GET/HEAD, leave JSON operation/photo/sync protocols unchanged, and not broaden
authorization or add runtime DDL.

After this ordering correction, implementation must continue against the same
test through the response mapping, object-card local identity, exact healthy
checklist/PTO and bootstrap barriers. Any test, fixture, support-router or
expectation edit requires another fresh Gate 2/3 review.
