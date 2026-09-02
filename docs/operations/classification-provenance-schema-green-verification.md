# Classification provenance schema — Gate 4 GREEN evidence

Date: 2026-09-02

Gate 4 implemented only the approved classification-provenance v11 slice.
The canonical migration accepts a mandatory injected before-create callable;
the production catalogue supplies an unconditional no-op, while the separately
reviewed verifier supplies the bounded coordinator. Production contains no
activation switch, lock, sleep, ledger, or other serialization mechanism.

The runtime provenance target now performs the canonical semantic fingerprint
precondition and owns DML only. Native, historical, and active-baseline apply
commands establish the target connection and run that read-only precondition
before opening the source connection. No classification taxonomy or import
transaction behavior was expanded.

Verification:

```text
$ php tests/InstallationProcess/classification_provenance_schema_001_test.php
PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE
PASS CLASSIFICATION-PROVENANCE-SCHEMA-001 deterministic verifier

$ php rapid-pilot/verify-history-batch-import.php
PASS: historical batch import is bounded, resumable and legacy-read-only

$ php rapid-pilot/verify-active-batch-import.php
PASS: active baseline batch is bounded, resumable, template-bound and legacy-read-only

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ openspec validate canonicalize-classification-provenance-schema --strict
Change 'canonicalize-classification-provenance-schema' is valid

$ git diff --check
exit 0, empty output
```

`pilot_case_import_001_test.php` was not classified as a slice regression: its
standalone default password does not match the canonical test environment and
it still pins the superseded v10 terminal catalogue. No test was edited during
this GREEN implementation.
