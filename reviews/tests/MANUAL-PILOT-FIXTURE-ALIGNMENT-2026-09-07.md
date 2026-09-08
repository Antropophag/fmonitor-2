# Independent test review — manual-pilot fixture alignment — 2026-09-07

Verdict: **APPROVED** for the exact test and evidence bytes recorded below.

Reviewer scope was limited to the working-tree diff of:

- `tests/InstallationProcess/assignment_order_original_attempt_audit_schema_001_test.php`
- `tests/InstallationProcess/pilot_case_import_001_test.php`
- `docs/operations/verification-fixture-alignment-2026-09-07.md`

No production implementation, architecture baseline, stand, persistent pilot data,
or external system was changed by this review.

## Fixed point and reviewed identities

Base `HEAD`: `c0137ff3cfd6b4c543f104085816b1aeb0e2548d`

```text
c44c7fdf763258a843e716d94a88d6e4b5af94d6ee5c4bf752ef098c84069afe  tests/InstallationProcess/assignment_order_original_attempt_audit_schema_001_test.php
219864b0ee31b2f97ae90cd32a93406b9dbea814f505d551bbeb0ce08f12ad17  tests/InstallationProcess/pilot_case_import_001_test.php
611919a92bb888a9bd60212f0dcc22e0de2c0bf7c3a567197a888f6a03c29f77  docs/operations/verification-fixture-alignment-2026-09-07.md
```

The approval is invalidated by a byte change to either reviewed test. The operations
record hash is included as corroborating evidence and is not a production contract.

## Standards

**APPROVED — 0 findings.** The change is test-only, uses existing unique-database and
isolated-prefix cleanup, keeps exact literal outcomes, and does not relax unrelated
negative assertions. `git diff --check` and PHP syntax checks passed for the reviewed
files. The compact fixture style is established in these tests; this bounded alignment
does not add a new code smell or production dependency.

## Spec

**APPROVED — 0 findings.** The terminal canonical schema expectation advances from 15
to 18 while retaining explicit proof that original-attempt audit step 13 is applied,
the complete 1..18 clean migration sequence runs, and repeat migration preserves facts.
This matches the current canonical-18 startup contract recorded in the manual-feedback
handoff.

The importer change matches the owner policy in `PRODUCT.md`,
`docs/fmonitor-2-pilot-spec.md`, and
`docs/operations/pilot-data-transfer-plan-2026-09-07.md`: planned date quality does not
restrict import eligibility. Object 4700 proves a malformed `workdatestart` is admitted
and creates exactly one unopened `needs_assignment_order` case with null opening facts.

The new 4701 and 4702 cases preserve the separate evidence boundary. `workdatefinish`
is an actual completion fact and `ptoactdate` is PTO evidence; malformed nonempty values
must not be silently treated as absence. Each case expects fail-closed
`SCHEMA_UNAVAILABLE` and independently proves that no installation case was written.
The existing valid completion/PTO rejection and all-or-nothing assertions remain intact.

## Executed evidence

With `PATH=/opt/homebrew/bin:$PATH` and the repository-local disposable test DB
administrator credential:

```text
php -l tests/InstallationProcess/assignment_order_original_attempt_audit_schema_001_test.php
No syntax errors detected

php -l tests/InstallationProcess/pilot_case_import_001_test.php
No syntax errors detected

php tests/InstallationProcess/assignment_order_original_attempt_audit_schema_001_test.php
RESULT passed=21 failed=0
ASSIGNMENT_ORDER_ORIGINAL_ATTEMPT_AUDIT_SCHEMA_OK

php tests/InstallationProcess/pilot_case_import_001_test.php
PASS: PILOT-CASE-IMPORT-001 CLI contract
```

The tests completed with exit status 0 and removed their uniquely named disposable
databases through their existing cleanup paths. No global database reset was run.

Summary: Standards 0 findings; Spec 0 findings. No blocking issue on either axis.
