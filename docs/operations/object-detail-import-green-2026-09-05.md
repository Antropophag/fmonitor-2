# Object-detail importer/no-DDL GREEN and full verification

Date: 2026-09-05. Append-only evidence; no launch completion claim.

Implementation exact SHA: `c658ac8a02c2a3de5baac8f7db4c281f47da87fe`.
Tracked working tree was clean before and after the full run.
Owner v0.2 approval: `object-detail-import-v02-owner-approval-2026-09-05.md`.
Independent Gate 3: `reviews/tests/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001-v6.md`.
Ratchet Gate 3: `reviews/tests/OBJECT-DETAIL-NO-DDL-RATCHET-001-v1.md`.

## Implemented boundary

The importer calls public `ObjectDetailSnapshotSchemaMigration::isCompleteCompatible`
after the existing generation guard and before any source environment access or
connection. False returns exact OBJECT_DETAIL_SCHEMA_REQUIRED JSON/LF, exit 2.
Two runtime CREATE statements were removed. Serial source parsing and target DML
are byte-preserved. Four corresponding DDL/rapid-mutation baseline exceptions
were removed; scanner and all other debt entries remain unchanged. The new
verifier is registered exactly once in canonical characterization.

## GREEN and exact identities

Focused direct invocation exited 0 with the exact six-line approved transcript.
Canonical characterization repeated the same verifier successfully. Each invocation
runs two independent synthetic tokens, proves all database outcomes and owned
cleanup. No real data or VPN source was used. Final labeled container inventory
was empty; the common artifact root survived.

Ratchet tests: 3/3 PASS. `make architecture-check`: PASS, 7 rules.

```text
fae2d9a5e3ba184663e7850ca83fdfd14125d71d22b8970b5d95e45572e19bcc  verifier
a7adf735611b2bde644a329b79572fa875eb07248a3c0f32b7a68c53cf69a62c  importer
1d20f4bd867e42144c43de462c413ab5e59effb3a88060c41f65543fa596f836  baseline.json
ee0fb1d2f1ba0fa5586edc78a1c5a6165b81a4a2fe43f9dc36e6ca0cbbed2904  run.sh
668b51891c956202cc4d6fe0d37287fdf615489e56bdfa66eea087d0d899b449  focused.log
74024006639beb32619f7912c83ba18229288b756080d2269f1cba202584adf0  full-verify.log
```

Private primary archive:
`/Users/antropophag/.local/state/fmonitor2-verification/object-detail-green-9iivu2a7`.
`inputs.json` pins source identities. Logs are not copied into this repository.

## Full make verify

Exit 2. PASS: test-db-reset, migrate (versions 1–12), architecture-check, lint,
unit-test, characterization-test, diff-check.
FAIL: db-test and e2e-test.

The only failed test identities are protected `pilot_e2e_flow_001_test.php`
(actor18 admission link expected 1, actual 0 at line 153), and
`pilot_demo_bootstrap_001_test.php`, which invokes that same protected test.
These match the prior exact-SHA failure recorded in
`full-verification-2563590-2026-09-05.md`. No failure was skipped or accepted
as launch-ready. Literal VERIFY_OK is absent; Quality Graph integration and
bootstrap CI publication remain forbidden.

The current list-based UI contract and protected table XPath require their own
owner-approved Gate 1 reconciliation. The importer characterization deliberately
does not approve excluded concurrency, quarantine coexistence/transition semantics,
real-data population, source-free deployment or golden-path readiness.
