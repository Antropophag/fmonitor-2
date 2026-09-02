# INSPECTION-ITEM-COMPLETE-001 — independent HTTP/architecture Gate 3 rereview v3

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, specification, production or RED evidence)  
Mission: `TEST-USER-READY`  
Verdict: `APPROVED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved executable spec: SHA-256
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Approved OpenSpec delta: SHA-256
  `d3d2b90ef251e9fed550d23c666a4dd42b69eefcffc95e9036857bf2f949262c`.
- Owner approval: SHA-256
  `b78cfe90c90826d6185cf41a883213cf0643f9685cb9120bdd6dd82abfe6eb04`.
- HTTP wiring test v3: SHA-256
  `f6021844f2ab42b8a09d556b5ef9f043a54cbd4f8e28e4d7c0cae59b72282052`.
- SQL-owner policy test: SHA-256
  `2d60153605fbd51aebb1ddaa875f1cbce26acaddb45a25ef11bde786c7b240a3`.
- HTTP RED evidence v3: SHA-256
  `83ec3e448a5f059511789c73f6658ed8c1412eeaf8bd4359e110be334201365f`.
- Current `ChecklistSync`: SHA-256
  `eba45bff34689c54088e89b9d4801c8c16bef2b420b3abdaed7f9f98e8c7bef6`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.
- Prior v2 review: SHA-256
  `17b6f6f7e55d5f70dbee6a2420fb1472451dad5e1ac505434ec179df7eeb3cba`.

## Independent reproduction

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
php -l tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
php tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
```

Both syntax checks passed. The focused runner reproduced:

```text
PHP Fatal error: Uncaught Error: Unknown named parameter $inspectionRecording
  ... inspection_item_complete_001_http_wiring_test.php:65
RED_ASSERTION: expected failing behavior observed
```

The policy test returned:

```text
PASS: InspectionEvidence SQL owner policy
```

The failure remains the intended missing production composition behavior. PHP,
autoload and test construction start normally; no database service or mutable
fixture is required. This is not setup failure.

## v2 blocker closure

### HTTP2-01 — closed

The v3 evidence names the exact current final spec, OpenSpec and approval
hashes. I independently recomputed every hash in its block, including the
earlier Gate 1/composition records, current production adapter, runner and
architecture checker; all match.

### HTTP2-02 — closed

The schema-unavailable contour snapshots recording calls, makes the public spy
return `INSPECTION_SCHEMA_UNAVAILABLE`, catches only
`PilotHttpInfrastructureUnavailable`, and requires an exact `+1` recording-call
delta. A pre-seam throw, duplicate recording, returned business rejection or
wrong exception cannot pass.

### HTTP2-03 — closed

`InstallationCaseIdResolverSpy` records literal external ids. Across successful
item commands, resolver and recording counts must match and every recorded id
must be exactly `4512`; the first command independently proves the unequal
canonical result `9512` enters the complete command. Missing, ambiguous and raw
failure resolvers each assert the exact single external id, while all three
must leave recording calls unchanged. The syntactically valid non-item probe
must change neither resolver nor recording history, so new item composition
cannot leak into the retained legacy branch.

## Reconfirmed coverage and sensitivity

- The recording spy implements only the public `InspectionRecording` seam;
  behavior is observed through public `ChecklistSync::accept`, not a repository,
  SQL query or private helper.
- Literal command projection checks trusted authenticated actor over malicious
  client attribution, unequal object/case identity, UUIDs, device time,
  revision, section/item and normalized installer ids.
- Literal mapping covers accepted, duplicate, both conflict classes, every
  specified deterministic rejection and schema-unavailable infrastructure
  translation. Exact two-field response shapes and revisions are asserted.
- Every ordinary mapped item result has one recording call; zero/ambiguous/
  failed resolution has none. The result matrix cannot be satisfied by a
  generic rejected mapper.
- The valid photo envelope plus unchanged spy histories distinguishes only-item
  isolation from the former malformed-envelope early return. Its legacy DB
  failure is only a branch-sensitivity probe; this test does not claim to
  replace the existing photo behavior characterization.
- Expected command/results are literals derived from the approved contract.
  Spies provide inputs and observations but no production-derived oracle.
- The SQL filename policy is deterministic supporting architecture evidence;
  it does not replace the behavioral test or repository architecture check.

## Gate decision

All v2 blockers are closed without weakening the approved expectations. The
test is traceable, deterministic, public-seam based, sensitive to the plausible
mapping/identity/actor/branch regressions in scope, and RED for the intended
missing composition behavior. HTTP/architecture Gate 3 v3 is `APPROVED` for
the exact hashes above. Gate 4 may implement minimal production wiring without
changing these tests.
