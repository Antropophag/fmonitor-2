# Test review: PILOT-E2E-FLOW-001 v0.1

- Gate: 3 — final fresh independent review
- Reviewer: separately tasked agent `/root/e2e_test_review_final`
- Test author: separately tasked Gate 2 author; reviewer authored neither reviewed input
- Independence: expected values were checked against the approved specification and its inherited approved examples, not against production implementation output
- Specification commit: `bc5947f8825855a6421c44e54879ea6b7199d0f9`
- Test commit / reviewed artifact: `4a9a8f90c4468ec213e72ff758cdebc6be8f8b0b`
- Specification: `specs/PILOT-E2E-FLOW-001.md`, version `0.1`, `APPROVED`
- Test: `tests/InstallationProcess/pilot_e2e_flow_001_test.php`
- Public seam: configured production raw HTTP under `/pilot`, isolated MariaDB `fm2_*` state, and production artifact store
- Date: `2026-08-29`
- Verdict: `APPROVED`

## Findings

None.

## Review assessment

- **Traceability:** the test cites `PILOT-E2E-FLOW-001 v0.1` and exercises A–G through the configured production HTTP seam. It follows the complete queue → card → composition selection → prepare → artifact GET/HEAD → manual 1C registration → open → refreshed card/queue journey.
- **Seam choice:** commands and downloads cross the real HTTP entry point into production composition. The fixture creates real legacy identity/object rows and production `fm2_*` schema in an isolated MariaDB database; it does not use the in-memory process environment or replace command results.
- **Sensitivity:** assertions pin canonical routing and methods, capabilities and identity, CSRF/body handling, PRG and user-visible validation, stale revisions, all four queue projections, immutable artifact bytes and metadata, registration/open transitions, audit order, absence of a registration task, final process state, and absence of legacy writes. Plausible omissions or bypasses in the requested orchestration are observable.
- **Expected-value independence:** object/person/date/number literals come from fixed example G and inherited approved contracts. Artifact lengths and SHA-256 values match the approved `DOCUMENT-RENDER-HTML-001` example and are literal expectations rather than values derived from downloaded bytes. The final engineer assertion independently requires the sole durable `control_engineer_user_id = 73`.
- **Rejected cases:** representative groups cover form validation, stale prepare/registration/open attempts, invalid open date, wrong methods with exact `Allow`, noncanonical routes, Origin/CSRF rejection, wrong media type as exact `400 Bad request.\n` with zero domain events, unavailable artifact/integrity, authorization/authentication, and redacted infrastructure failure. Inherited command specifications remain responsible for exhaustive domain matrices.
- **Determinism and isolation:** business/audit clocks, identifiers, render literals, database name, credentials, and artifact root are controlled. Cleanup removes the isolated database/user and task-owned artifact root. Fresh HTTP and MariaDB connections prove durable state rather than relying on an in-process cache.

## Prior blocker verification

1. The appendix oracle is corrected to approved SHA-256 `da33d58efd35c6211d850446ee9f159526c9ba779fbdd9355b68ac35806ee3ac` for exactly 1262 bytes.
2. An authenticated same-origin request carrying the current session/CSRF material now independently asserts exact `400 Bad request.\n` for disallowed media type and immediately asserts zero `fm2_process_events`; it is distinct from the later `403` Origin/CSRF precedence probe.
3. The final fresh durable-state read now asserts the sole assignment order has exact `control_engineer_user_id = 73`, in addition to the exact installer and artifact counts.

## RED verification

Command run against the exact reviewed inputs:

```text
$ php tests/InstallationProcess/pilot_e2e_flow_001_test.php
PHP Fatal error: Uncaught TestFailure: production migration permits exact capability assignment_order.confirm_registration
Expected: true
Actual: false
at tests/InstallationProcess/pilot_e2e_flow_001_test.php:43
exit code: 255
```

The test successfully creates its isolated database, applies the current production migrations, and inspects the resulting production constraint. It then fails because current production behavior does not yet permit the approved `assignment_order.confirm_registration` capability. This is the first missing behavior exercised by the slice, not a fixture, dependency, or harness failure; the RED is valid and appropriately sensitive.

## SHA-256 reviewed-input manifest

```text
b1352c442f4de25adc6099cb29b9627e69c2754aa9a0e1466a763b208d6ef349  specs/PILOT-E2E-FLOW-001.md
a04e3ffc69579efd9c688f3cb82c6701160b427e78d50e05c93aadb8c617f311  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Git blob identities at review time:

```text
f701fcd97f80a603b3e3dad90b248eb4caf6fecf  specs/PILOT-E2E-FLOW-001.md
4ac59c881127c100ab30db37ba5187aef5b52e92  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Any byte change to either reviewed input invalidates this approval. The review record is excluded from the self-referential manifest.

## Required changes

None. Gate 3 is approved for test commit `4a9a8f90c4468ec213e72ff758cdebc6be8f8b0b`; Gate 4 may begin against exactly this reviewed test.
