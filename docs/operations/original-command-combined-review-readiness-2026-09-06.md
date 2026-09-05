# Original command — combined Gate 5 readiness audit

- Date: `2026-09-06`
- Reviewer: separately tasked read-only agent `/root/registry_engine_gate1`
- Reviewed HEAD: `f0862b08d1922f9e3ab2f31b39ac9c32078579e8`
- Scope: bounded preparation for a future combined `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` Gate 5; not a Gate 5 verdict
- Status: **NOT READY — ONE ACTIVE IMPLEMENTATION/REVIEW BLOCKER; NO OTHER KNOWN COMMAND BLOCKER**

No production source, test or specification was changed. This append-only
readiness record is the only authored artifact. No tests were executed.

## Prioritized blockers

### P0 — diagnostic observer isolation has approved RED but no production GREEN/Gate 5

The current command still invokes request-aware correlation setup before its
main boundary catch and calls `safeLog->record()` directly from cleanup and
lease-release paths. A throwing observer can escape, replace an already selected
or durable Result, repeat cleanup, skip required terminal audit/delivery
sequencing, or leave the input stream unclosed. The exact source sites remain
`AssignmentOrderOriginalRuntime.php:62,91,95–97`.

The bounded audit `original-safe-log-best-effort-audit-2026-09-06.md` identifies
the same gap. The isolation tests now have demonstrated RED at commit
`f0862b08d1922f9e3ab2f31b39ac9c32078579e8`, but there is no isolation production
implementation or independent isolation Gate 5 yet. A combined reviewer cannot
approve the command until that cycle completes on an exact implementation SHA.

Required closure evidence must cover all approved public outcomes:

- cleanup-log Throwable preserves the selected rejection, attempts every
  applicable cleanup primitive exactly once, performs the terminal attempt
  audit once, writes no diagnostic bytes and calls the observer once;
- lease-release diagnostic Throwable preserves accepted/replayed/provisional
  conflict or persistence Result, releases once, continues required delivery or
  conflict audit sequencing, and emits no second log attempt;
- request-correlation setup Throwable does not escape the application seam,
  does not reuse a stale correlation, and still produces the approved typed
  command result with exact stream/resource cleanup.

The isolation implementation must receive its own independent Gate 5 before it
is treated as combined-review input.

### P1 — combined exact-SHA review and regression evidence do not yet exist

The opened-file owner component is independently approved at implementation
`73c3c22999e379d7b250d170ce9a2a262c9ca815`; that approval is scoped and cannot
approve the surrounding command. After isolation GREEN/Gate 5, a new reviewer
must inspect the cumulative command implementation at one frozen SHA against the
then-current original-upload specification and OpenSpec hashes.

No additional unresolved command defect was found beyond diagnostic isolation.
The older command-v1/v2 findings have recorded corrective implementations and
independent reviews: immutable private PDF bytes/restart, bounded PDF grammar,
assignment-scoped initial lineage, lookup/commit/audit failures, all-row
composition validation and pre-stream precedence, real lifecycle barriers,
production factory/filesystem boundary, schema v2, capability publication and
safe-log ownership. This statement is a readiness shortlist, not approval; the
future combined reviewer remains responsible for finding regressions in the
final cumulative bytes.

## Minimum combined-review regression request

On the final isolation implementation SHA, preserve approved test bytes and run
every current original-command script, including the focused isolation and
owner tests:

```sh
for f in tests/InstallationProcess/assignment_order_original_*_test.php; do
  php "$f" || exit $?
done
```

The current manifest contains 20 scripts. The final request must explicitly
identify and retain these high-sensitivity controls:

- diagnostic isolation: cleanup, lease-release and correlation-setup throwing
  observer cases with exact Result/call/audit/delivery/resource assertions;
- `assignment_order_original_safe_log_owner_001_test.php` for stable policy,
  retained-owner behavior, close lifecycle and direct class loading;
- `assignment_order_original_production_boundary_001_test.php` for configured
  production logger binding, fail-before-DB/root ordering, canonical/symlink/
  mode/owner cases and exact real diagnostic append;
- worker transport/protocol and upload remaining-contract tests for cleanup,
  lease, response-loss and serialized-result regressions;
- parser/incremental-grammar, private restart, schema-v2, database setup,
  assignment-scoped lineage/composition, maintenance/orphan and lease-race
  tests that closed the command-v1/v2 findings.

Also run the supporting production boundaries affected by the command's
authorization, migration and composition assumptions:

```sh
FMONITOR_TEST_DB_ADMIN_PASSWORD=<documented local test value> \
  php tests/InstallationProcess/process_command_authorization_001_test.php
FMONITOR_TEST_DB_ADMIN_PASSWORD=<documented local test value> \
  php tests/InstallationProcess/production_migration_runner_001_test.php
php tests/InstallationProcess/production_composition_001_test.php

find app/AssignmentOrderOriginal -maxdepth 1 -type f -name '*.php' -print0 \
  | sort -z | xargs -0 -n1 php -l
openspec validate replace-pilot-registration-with-original-upload --strict
make architecture-check
make unit-test
make lint
git diff --check <last-reviewed-command-base>..<final-combined-sha>
```

The combined review request must include:

1. frozen final SHA plus exact executable-spec/OpenSpec hashes;
2. approved isolation Gate 1, RED, Gate 3, GREEN and Gate 5 records;
3. approved owner Gate 1/test/GREEN/Gate 5 records and its structural proof;
4. prior command-v2 findings and corrective parser/schema/setup/production-safe-
   log reviews;
5. sorted source/test hash manifests and raw bounded regression transcript;
6. the cumulative production diff from the last command review and focused
   isolation diff from its approved Gate 3 base.

After combined approval, integration still requires `make verify` with literal
`VERIFY_OK` on the same exact SHA before any deployment/readiness claim. HTTP,
selection application, opening, protected E2E and launch remain separate scopes.

## Exact reviewed identities

```text
d65470e2e1da510aa1ebe6d9cce6549b8fffcc6bf66035716623eb0cad61f890  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
482b5153e84e4dfe51ff75b526458fdd974e503a2d6f4b31db880a1f15c4ba54  specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001.md
0262cad1d0424e0c5b09d1030bcadf6bf90b253025eaedc3bca6b63593cac673  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001-v1.md
f2af8fc92141a10bd8f0cc82e0c5bc6dbcc0b4949937e55c48970846a80cd675  docs/operations/original-safe-log-best-effort-audit-2026-09-06.md
0ad53f4146b9b18773e189ae93c62a6ebb589420ff479538ad3f97d96554ecdc  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-command-v2.md
ee173ef8f78895871cd2334d094dd73245211fa9d1ae026a19ababd8e4cd7559  docs/operations/assignment-order-original-combined-gate5-readiness-inventory-2026-09-05.md
6fb1bc2ff8fbdc6ff86d93110fcd566079cca6cd27dcfcfdb95ef4403b62a55b  sorted 25-file app/AssignmentOrderOriginal PHP SHA-256 manifest
cc04ecba66133eeed52b5e0647799de6a8171e134410bc0c00c293f7e52b5f52  sorted 20-file assignment_order_original_*_test.php SHA-256 manifest
```

The manifest hashes are SHA-256 of literal sorted `shasum -a 256` output.
This record does not infer combined approval from the owner component and is
separate from the forthcoming isolation Gate 5.
