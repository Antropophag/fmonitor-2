# INSPECTION-ITEM-COMPLETE-001 — independent HTTP/architecture Gate 3 rereview v2

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, specification, production or RED evidence)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved executable spec: SHA-256
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Approved OpenSpec delta: SHA-256
  `d3d2b90ef251e9fed550d23c666a4dd42b69eefcffc95e9036857bf2f949262c`.
- Owner approval: SHA-256
  `b78cfe90c90826d6185cf41a883213cf0643f9685cb9120bdd6dd82abfe6eb04`.
- Revised HTTP test: SHA-256
  `e364cf5ecb06f9e6b4e126ec215b1f10b96343f6f1c1bda88cdaf92a725e56cc`.
- Unchanged SQL-owner test: SHA-256
  `2d60153605fbd51aebb1ddaa875f1cbce26acaddb45a25ef11bde786c7b240a3`.
- RED evidence v2: SHA-256
  `6d4449c44beb6268cb5e5f9bfc8a526933a95b8607b3906de51c4ba7c82129c1`.
- Current `ChecklistSync`: SHA-256
  `eba45bff34689c54088e89b9d4801c8c16bef2b420b3abdaed7f9f98e8c7bef6`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.

## Independent reproduction

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
php -l tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
php tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
openspec validate migrate-inspection-item-completion --strict
```

Both syntax checks passed. The focused runner reproduced the intended first
missing composition behavior:

```text
PHP Fatal error: Uncaught Error: Unknown named parameter $inspectionRecording
RED_ASSERTION: expected failing behavior observed
```

The SQL-owner policy passed and strict OpenSpec validation passed. The RED is
not an external setup failure.

## Prior finding progress

- Prior `HTTP-02` mapping coverage is materially corrected: accepted,
  duplicate, both conflict classes, every specified grouped deterministic
  rejection, and schema-unavailable exception translation are represented with
  literal status/revision expectations.
- Identity equality is no longer assumed: external object `4512` must resolve
  to canonical case `9512` before the complete command is observed.
- Actor trust remains sensitive: client actor `9999` cannot replace the
  authenticated actor, and the command projection checks every field.
- The non-item probe now has a syntactically valid common envelope. Its unchanged
  recording-spy count detects the relevant wrong implementation that delegates
  valid `photo_uploaded` to `completeItem`. The unconnected DB keeps the probe
  isolated and also confirms it remains on a legacy DB-dependent path. Full
  photo semantics remain owned by the existing characterization/regression,
  not claimed by this focused test.

## Blocking findings

### HTTP2-01 — v2 evidence identifies superseded Gate 1 artifacts

The v2 evidence hash block records spec `839702...`, OpenSpec `bcbe849...`, and
approval `59f0a5...`. Those are not the final owner-approved artifacts reviewed
here; the exact current approved hashes are respectively `c895095...`,
`d3d2b90...`, and `b78cfe...`. Its prose also says the narrower resolver is
required, but the recorded spec hash predates the final identity-resolution
approval.

Required change: append corrected RED evidence against the exact final approved
hashes and reproduce the commands without changing expectations. Gate evidence
must not claim traceability to superseded inputs.

### HTTP2-02 — schema-unavailable does not prove exactly one recording call

Ordinary matrix entries are covered by a total call-count assertion, but the
`INSPECTION_SCHEMA_UNAVAILABLE` call happens after that assertion. The test
catches the expected exception and immediately snapshots the new count without
checking its delta. An adapter that invokes `completeItem` twice for this input
and then throws would pass every later assertion because the inflated value is
used as the new baseline.

Required change: assert that schema-unavailable invokes `completeItem` exactly
once before translating its typed result to
`PilotHttpInfrastructureUnavailable`, and retain the zero-call assertions for
resolution failures.

### HTTP2-03 — resolver invocation/isolation is not observable

The resolver closures do not record their inputs or invocation counts. The
command proves that *some* resolution yielded `9512`, but the test cannot catch
duplicate resolution, resolution with a wrong object id followed by a correct
one, or an accidental call to the new resolver from a non-item branch. The last
case conflicts with “only `item_completed` is delegated/by this slice; every
other branch remains existing.”

Required change: use a small resolver spy/callable with literal input/output
and call history. Assert one resolution of external `4512` per delegated item
operation, one call for zero/ambiguous/failing item resolution as applicable,
and no resolver call for the valid non-item probe. Do not expose production
internals or use DB state as the oracle.

## Gate decision

The revised contour closes the major mapping, actor, unequal-identity and
malformed-non-item gaps through public adapter/application observations. It
remains `CHANGES_REQUESTED` because its evidence points at unapproved hashes
and two plausible duplicate/cross-branch seam regressions can pass. These are
focused Gate 2 corrections; production must not be written against this v2
review as approved.
