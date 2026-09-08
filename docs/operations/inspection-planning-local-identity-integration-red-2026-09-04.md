# INSPECTION-PLANNING-SCHEMA-001 — construction-control local identity RED

- Date: `2026-09-04`
- Gate: `2`, unchanged approved runtime verifier
- Production changes: none

Fresh healthy-mode responses after canonical migration and fixture setup:

```text
schedule 303
calendar 200
object queue 200
construction control 503 (expected 200)
```

The router supplies trusted local actor `901`, an active local user/role and
exact `construction_control.read`. Its `REMOTE_USER` is a descriptive decoy and
no legacy user row exists. The construction-control branch still resolves
display/admission through the legacy email directory, raises on the missing
legacy table row and maps the result to 503. Other three public seams prove the
DML-only schema, CSS, schedule data and local object authority are healthy.

Minimal GREEN must select active local profile plus exact
`construction_control.read` when a trusted local ID is present, while retaining
the legacy predecessor only when local identity is absent. Missing/incompatible
planning schema failures and zero-DML/schema snapshots remain unchanged.

Fresh independent Gate 3 integration review is required before production edit.
