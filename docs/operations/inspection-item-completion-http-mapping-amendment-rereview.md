# INSPECTION-ITEM-COMPLETE-001 — independent HTTP mapping amendment Gate 1 rereview

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
amendment, OpenSpec delta, approval record, tests or production)  
Mission: `TEST-USER-READY`  
Verdict: `READY_FOR_RED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Final executable spec: SHA-256
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Final OpenSpec delta: SHA-256
  `d3d2b90ef251e9fed550d23c666a4dd42b69eefcffc95e9036857bf2f949262c`.
- Updated owner approval: SHA-256
  `b78cfe90c90826d6185cf41a883213cf0643f9685cb9120bdd6dd82abfe6eb04`.
- Prior review with four findings: SHA-256
  `87fd2fbfd21e418f37f0baa3485113e7957f7753a81d935f2b822ed07517e991`.

`openspec validate migrate-inspection-item-completion --strict` passes.

## Finding closure

- `HM-G1-01` is closed. The approval record now names the exact current
  executable-spec and OpenSpec hashes, and records the owner's explicit
  approval of the additional identity-resolution decision.
- `HM-G1-02` is closed. `ChecklistSync` must use an explicit injected resolver
  from external object id to canonical installation-case id, may not assume
  equality, and has exact zero/multiple/failure classes. The unequal-id
  scenario is mirrored in OpenSpec.
- `HM-G1-03` is closed. Schema unavailability and resolver/infrastructure
  failures throw `PilotHttpInfrastructureUnavailable`; the inherited outer
  adapter renders HTTP 503 with `status=retryable`. Deterministic no-case is
  explicitly kept out of that exception class.
- `HM-G1-04` is closed. Every non-infrastructure adapter response is exactly
  `{status, revision}`; accepted, duplicate, conflict and grouped rejected
  status/revision semantics agree between the executable spec and delta.

## Coherence decision

Only `item_completed` delegates, the resolver precedes command construction,
the application recording seam remains the single business mutation owner,
and other operation branches remain on their existing behavior. The result
classes are mutually exclusive: deterministic domain outcomes return exact
two-field values, while resolver ambiguity/failure and schema/infrastructure
failure take the typed retryable exception path. No legacy item-completion SQL
fallback is permitted.

The exact approved artifacts are unambiguous and observable enough for a
public-seam RED. Gate 1 amendment rereview is `READY_FOR_RED`; this verdict does
not approve any test or implementation.
