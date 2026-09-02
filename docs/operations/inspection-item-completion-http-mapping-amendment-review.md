# INSPECTION-ITEM-COMPLETE-001 — independent HTTP mapping amendment Gate 1 review

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
amendment, OpenSpec delta, approval record, tests or production)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Current executable spec: SHA-256
  `839702b21ec72ad33974e3dbe6795e60e71dd1a0e4659b90c2a099e459ebdf6b`.
- Current OpenSpec delta: SHA-256
  `bcbe8498d0cbeb55d9cd283dce4a9bcdf45f9dd8879c2c14fe35bd0ccab144af`.
- Owner approval record: SHA-256
  `59f0a5deaff57b94dd758731a3bb41a3fe40e05474c73e1820d7f21316991a6d`.
- Delivery process: SHA-256
  `a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504`.

Read-only validation:

```text
openspec validate migrate-inspection-item-completion --strict
Change 'migrate-inspection-item-completion' is valid
```

No test or production file was edited by this review.

## Coherent parts

- `ACCEPTED -> accepted`, `DUPLICATE -> duplicate`, and
  `STALE_REVISION`/`OPERATION_PAYLOAD_CONFLICT -> conflict` are now stated the
  same way in the executable spec and OpenSpec delta.
- Both artifacts consistently group the remaining deterministic domain
  failures as `rejected` and prohibit a legacy item-completion mutation
  fallback.
- Both artifacts say only `item_completed` delegates; all other checklist
  operation branches remain unchanged. This closes the intended branch scope
  conceptually, subject to a sensitive Gate 2 test.

## Blocking findings

### HM-G1-01 — the approval does not identify the current executable spec

The approval record names approved spec SHA-256
`64acbd76b339ac2795e3e7cf9d2508ac4dabf62027e083d91ab25dacdb75c92a`,
but the current spec is
`839702b21ec72ad33974e3dbe6795e60e71dd1a0e4659b90c2a099e459ebdf6b`.
Thus the durable record does not prove owner approval of the exact artifact
presented for this review. Gate 1 approval is hash-specific in this workflow.

Required change: reconcile the artifacts, independently rereview the final
exact spec/OpenSpec hashes, and record explicit owner approval against those
same hashes. Do not silently rewrite the historical approval assertion.

### HM-G1-02 — HTTP object identity cannot be translated from the contract

`ChecklistSync::accept` receives the route's legacy installation **object id**.
`CompleteInspectionItem` requires the canonical internal
`installationCaseId`. The pilot data model explicitly distinguishes them via
`fm2_installation_cases.legacy_installation_object_id`. The amendment says the
HTTP adapter translates requests, but specifies neither a public lookup seam nor
the uniqueness/not-found/infrastructure outcomes for this translation.

Using the route object id directly as `installationCaseId` is observably wrong
whenever the two numeric ids differ. Performing the existing case lookup as
business SQL inside `ChecklistSync` conflicts with the stated thin-adapter and
SQL-ownership boundary. An HTTP spy example that uses `4512` for both values
would conceal this missing contract rather than resolve it.

Required change: define the exact public translation/composition seam from
legacy route object id to canonical installation-case id (or revise the command
boundary deliberately), including zero/multiple-match and infrastructure
failure behavior. Add an independently determined example with unequal ids,
for example route object `4512` mapping to case `7001`, and require the command
to carry `7001`.

### HM-G1-03 — “retryable infrastructure path” is not an executable outcome

The mapping names `INSPECTION_SCHEMA_UNAVAILABLE -> the existing retryable
infrastructure response path`, but does not state what the adapter does to
enter that path. At the reviewed public levels there are materially different
possibilities: `ChecklistSync::accept` could return a retryable array, throw a
typed `PilotHttpInfrastructureUnavailable`, or leak/translate another
exception. The surrounding HTTP coordinator currently maps infrastructure
exceptions to an exact `503` JSON response, but that behavior is not cited or
spelled out in this acceptance statement. The amendment also separately says
transaction infrastructure failure is “not reported as a business
acceptance,” without fixing whether it follows the same path.

Required change: specify the exact observable behavior at each confirmed seam:
the typed exception/result emitted by `ChecklistSync::accept` for
`INSPECTION_SCHEMA_UNAVAILABLE` and unexpected transaction infrastructure
failure, and the exact outer HTTP status/body (or an explicit cited inherited
contract) that makes it retryable. State that deterministic domain statuses do
not enter this exception path. Mirror the same rule in the OpenSpec scenario.

### HM-G1-04 — deterministic rejection mapping is still underspecified at the public shape

The spec says every other deterministic rejection maps to `rejected` “with the
returned revision,” while the OpenSpec delta only says `rejected`. Neither
artifact explicitly fixes whether the response contains only
`status`/`revision`, carries a message, or preserves any status-specific data.
That permits different Gate 2 expectations and incompatible clients.

Required change: make the exact public response shape (including revision and
message presence/absence) normative for the grouped deterministic rejection,
and make the executable spec and OpenSpec delta identical on that point.

## Gate decision

The high-level class mapping and only-item intent are coherent, but the exact
approved artifact, object-id translation, retryable exception path and grouped
rejection shape are not yet unambiguous and observable. Under Gate 1 of
`docs/development-process.md`, the amendment is not ready for a revised RED.
