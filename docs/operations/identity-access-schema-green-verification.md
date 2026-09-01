# IDENTITY-ACCESS-SCHEMA-001 Gate 4 verification

- Date: `2026-09-01`
- Specification: `IDENTITY-ACCESS-SCHEMA-001 v0.1`
- Gate 3 authority: `reviews/tests/IDENTITY-ACCESS-SCHEMA-001-v4.md`, `APPROVED`
- Outcome: `FOCUSED_GREEN / BASELINE_ONLY_FULL_VERIFY_FAILURE`

## Focused GREEN

- canonical identity/access suite: PASS;
- isolated runtime DDL observer: PASS;
- immutable first-GREEN `MIGRATION_FAILED` / post-v6 short-circuit contract:
  PASS;
- clean runner applies literal `[1,2,3,4,5,6]` and ends at
  `schemaVersion=6`;
- complete repeat returns `[]`; compatible partial returns `[6]`;
- conflict/fingerprint/prefix/runtime-no-DDL matrix: PASS;
- relevant PHP lint and `git diff --check`: PASS;
- strict OpenSpec validation: PASS;
- `make architecture-check`: PASS, 7 rules, baseline unchanged.
- fresh current-tree runtime image build: PASS; image retains non-root
  `fmonitor`, packages `bin/fmonitor2-migrate.php` and returns exact combined
  `CONFIGURATION_INVALID` JSON with exit `64` when configuration is absent;
- historical image harness itself stops before build on the intentionally dirty
  pre-existing entrypoint hash change, so the equivalent current-tree image
  assertions were executed directly without rewriting that unrelated baseline;
- `make fresh-test-verify` preserved the same verification baseline, emitted
  `FRESH_TEST_VERIFY_FAILURE verify_status=2 teardown_status=0` and removed all
  Compose services/volumes.

## Full verification

`make verify` completed every stage and reported:

```text
VERIFY_STAGE test-db-reset PASS
VERIFY_STAGE migrate PASS
VERIFY_STAGE architecture-check PASS
VERIFY_STAGE lint PASS
VERIFY_STAGE unit-test PASS
VERIFY_STAGE db-test FAIL
VERIFY_STAGE characterization-test PASS
VERIFY_STAGE e2e-test FAIL
VERIFY_STAGE diff-check PASS
FULL_VERIFICATION_FAILURE count=2 stages=db-test,e2e-test
```

The DB stage contains exactly the known eight failures: shared CSP, local-RBAC
fixture/contract paths and the existing assignment-order artifact failure. The
E2E stage reports that same artifact failure. No new identity/access failure is
present; runner-dependent v5 contracts were coherently updated to literal v6,
while workforce family-local boundaries remain unchanged.

## Boundary

No architecture baseline or unrelated assertion was changed to obtain GREEN.
`GRILL-002` behavior remains unresolved and outside this schema-ownership slice.
Gate 5 requires a fresh independent code review before completion or commit.

## Superseding Gate 5 correction verification

After the first Gate 5 review found presentation-name sensitivity and missing
database-default validation, fresh RED/review/GREEN cycles completed:

- real noncanonical MariaDB-generated index/FK names are accepted while
  uniqueness, ordered columns, FK target/columns and delete/update rules remain
  semantic;
- real latin1 database default returns redacted `DATABASE_UNAVAILABLE`, exit
  `69`, before identity mutation;
- MariaDB `utf8mb4_uca1400_ai_ci` is validated through the documented
  `uca1400_ai_ci` alias plus safe exact utf8mb4 trial application;
- unrelated unexpected failures remain redacted `MIGRATION_FAILED`, exit `70`;
- both focused cases, full identity suite, isolated runtime observer, strict
  OpenSpec, diff check and architecture 7/7 pass;
- repeated `make fresh-test-verify` again reports only the known DB/E2E baseline
  and `FRESH_TEST_VERIFY_FAILURE verify_status=2 teardown_status=0`, leaving
  Compose empty.

After the second Gate 5 review, preflight moved inside
`CanonicalMigrationApplication`: typed database-unavailable returns redacted
exit `69`, unexpected metadata failure returns redacted exit `70`, and neither
invokes migrations. CLI uses the canonical identity table source instead of a
duplicate catalogue, and bootstrap/status owners are formatted as reviewable
helpers. The new preflight contract, all focused suites, characterization,
strict validation, diff check and architecture 7/7 pass. Final `make verify`
again reports exactly `FULL_VERIFICATION_FAILURE count=2
stages=db-test,e2e-test`; test teardown removed Compose resources.
