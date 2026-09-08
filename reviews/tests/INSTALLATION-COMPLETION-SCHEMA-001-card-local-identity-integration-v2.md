# Independent Gate 3 rereview v2 — INSTALLATION-COMPLETION-SCHEMA-001 configured runtime

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/completion_card_local_gate3`
- Test author: orchestrating agent; this reviewer did not author the reviewed
  test, support, specification, production or evidence
- Reviewed commit: `99c9b48c690e9577d47f46417e664acbc4d3df21`
- Supersedes: card-local integration Gate 3 v1 at `282941d58b3bcedf33b1930d493b1bf54bd0bf5c`
- Public seam: raw HTTP queue, card, checklist and completion requests through
  the configured production entrypoint
- Verdict: **APPROVED**

This review changes no test or production file. It approves the corrected Gate
2 fixture and its newly exposed first RED; the prior v1 approval is historical
because its fixture did not enter the configured `PilotE2ECoordinator` graph.

## Exact reviewed artifacts

```text
c6f3cf995a81d214559d4078696f82d6d2cfaa1123120cb91775fc5c6b5c5448  specs/INSTALLATION-COMPLETION-SCHEMA-001.md
a536c49611d7d3091ad647283478319f7bfded75ff694207bd9a7b72639ab5dc  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
1331bc60607cd8a6c7f7e87a0bcb1d837a727c90e00465185dfd789c2ebbb682  tests/Support/installation_completion_runtime_router.php
68458e96f1779f4e293edaa88ad33dfb7d00cabb5cfb24b15400cb63c49a0668  docs/operations/installation-completion-card-local-identity-red-v2-2026-09-04.md
32ce40ab118f1c39e90000c26753efd510d9b0cf4e3c6285c2de61eddc8534cb  app/PilotHttp/PilotE2ECoordinator.php
910fce64bfc23ec73a4b813effabe6ab1b1dfb503963eafd6c402dd06ebbb9ba  app/PilotHttp/LocalAuthenticatedHttpUser.php
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
```

## Fixture correction and setup validity

The four-line test correction is necessary and sufficient to exercise the real
configured production graph:

- `FMONITOR_PILOT_CSS_PATH` names the real public pilot CSS, so
  `pilotUiConfigured()` is true and the CSS prerequisite is readable;
- `FMONITOR_ARTIFACT_STORAGE_ROOT` names one random private directory created
  with mode `0700`, so `e2eConfigured()` has a real task-owned artifact root;
- fixed `FMONITOR_NOW=2026-09-02T00:00:00+03:00` supplies the production clock
  input deterministically and completes `e2eConfigured()`;
- the active assigned role receives exact `inspection.item.complete`, allowing
  the configured checklist handler to pass its local authorization before
  exercising completion-family readiness. Existing exact `objects.read`,
  `checklist.read` and `checklist.edit` grants remain.

The process prefix was already explicit and non-empty for every matrix member,
and canonical migration provides the permissions table required by
`prepareCommandConfigured()`. Thus all configured-dispatch conditions are true
without a test-only branch or replacement application graph.

The artifact directory is outside the repository and unique per invocation. It
is passed to every child through the test's explicit environment, creates no
positive evidence itself, and is recursively removed in the outermost
`finally`. Database, runtime user, all per-mode prefixed families, bootstrap
homes and wrapper roots retain their existing unconditional cleanup. Independent
inspection after the failed run found no retained `icr-artifacts-*` directory.

## Traceability and independent expected values

Executable scenario F requires authenticated checklist HTML GET/HEAD on missing
or drifted completion family to return plaintext `503`, `Retry-After: 60`,
no-store and exact `Service unavailable.\n`; HEAD must have the GET declared
length and an empty body. It also requires failure before completion DML or
partial HTML. These expectations are explicit in the approved specification,
not copied from the current handler.

Scenario E independently requires the exact-family queue/card/checklist reads
to return `200`, the valid completion POST to return `303`, and runtime to stay
DML-only. The existing test continues to require one exact PTO root append after
the seeded 85% checklist progress, exact actor/date/details, stable completion
DDL and no unrelated row, schema or counter mutation.

## Independent RED reproduction

```text
$ php -l tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
No syntax errors detected in tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php

$ php -l tests/Support/installation_completion_runtime_router.php
No syntax errors detected in tests/Support/installation_completion_runtime_router.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php

Fatal error: Uncaught TestFailure: checklist content type
Expected: array ('text/plain; charset=UTF-8')
Actual:   array ('application/json; charset=UTF-8')
exit 255
```

The failure occurs in the `missing_` matrix after queue and card GET/HEAD have
already satisfied their exact unavailable assertions. The configured checklist
page reaches `PilotE2ECoordinator::checklist()`, passes CSS and local exact
authorization, reads the card, then hits missing completion readiness. Current
production catches that infrastructure failure in the generic JSON mapping and
returns its retryable JSON envelope. The test instead rejects the content type
before accepting body or HEAD behavior. This is the specified missing response
mapping, not a broken fixture, unavailable asset, missing authority or failed
server/database setup.

The drift branch is structurally identical and remains queued immediately after
the missing branch. Both branches retain full before/after database snapshots.
No schema repair, completion DML or partial-success response can satisfy them.

## Sensitivity and downstream barriers

The RED is sensitive to the minimal intended correction: only checklist **page**
GET/HEAD infrastructure failures should use the existing plaintext response
helper with `Retry-After: 60`; operation/photo JSON endpoints remain outside the
completion-family HTML contract and must retain their JSON protocol. The same
helper already makes HEAD bodyless while preserving declared GET length, which
the test compares after GET passes. A blanket status-only or JSON acceptance
change would fail the unchanged content-type, body, header and length checks.

After the page mapping is corrected, this same executable necessarily runs:

1. remaining missing checklist HEAD and completion POST plus zero-mutation
   snapshot;
2. the complete drift queue/card/checklist GET/HEAD/completion matrix and its
   zero-mutation snapshot;
3. the healthy exact queue, followed by card GET/HEAD. Because actor `901` has
   active local exact `objects.read` and no legacy user row exists, the existing
   card-local-identity `200` expectation still catches legacy-only resolution;
4. exact checklist GET/HEAD and completion POST, the single PTO append, stable
   root schema and no-unrelated-mutation snapshot;
5. missing/drift/exact bootstrap publication and DML-only matrices.

None of those expectations was removed, weakened or bypassed by v2. The added
`inspection.item.complete` grant is exact and needed only to reach the intended
checklist seam; it does not grant completion POST authority, manufacture schema
readiness, or serve as card `objects.read` authority.

## Gate 3 checklist

- Traceability: PASS — response and exact-family outcomes map directly to
  scenarios E/F.
- Seam: PASS — real configured HTTP entrypoint and production dependencies.
- RED specificity: PASS — exact plaintext-vs-JSON checklist page mapping after
  healthy asset, identity, authorization and card prerequisites.
- Expected-value independence: PASS — exact status/headers/body/HEAD semantics
  are stated by the approved spec.
- Rejected cases: PASS — separate missing and drift families, GET/HEAD parity,
  completion POST, zero mutation and bootstrap failures remain.
- Determinism/isolation: PASS — fixed clock, canonical real assets, random
  database/user/prefix/root names and unconditional cleanup.
- Sensitivity: PASS — mapping correction advances to independent local-card,
  exact append/history and bootstrap barriers in the same executable.

## Verdict and Gate 4 boundary

**APPROVED.** Gate 4 may minimally correct configured checklist page GET/HEAD
infrastructure mapping to the specified plaintext unavailable response. JSON
operation/photo/sync contracts must remain JSON. Tests, fixtures and expectations
must not change; any such change requires a fresh Gate 2/3 cycle.

After that mapping is GREEN, the next observed RED in this same approved test is
expected to be the still-unimplemented object-card local identity integration.
Its correction remains separately bounded as approved in v1: card GET/HEAD uses
active local profile plus exact `objects.read`, with legacy fallback only when
the trusted local actor field is absent.
