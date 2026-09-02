# Test review: CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001 v4

- Gate: 3 — fresh independent test re-review after UUIDv4 correction
- Reviewer: separately tasked agent `/root/photo_limit_concurrency_test_review_v4`
- Test author: separately tasked Gate 2 correction agent (not this reviewer)
- Reviewed commit: dirty working tree; exact artifacts pinned below
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md`, version `0.1`
- Public seam: `php rapid-pilot/verify-checklist-photo-limit-concurrency.php`
- Red command: `make test-env-up && php tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

No blocking findings.

The Gate 4 UUID blocker is closed. The current test uses these three exact,
pairwise-distinct operation IDs:

```text
0199a100-0000-4000-8000-00000000000a
0199a100-0000-4000-8000-00000000000b
0199a100-0000-4000-8000-00000000000c
```

All three match the exact public-seam validator in
`ChecklistSync::uuid(...)`: lowercase hexadecimal canonical form, `4` in the
version nibble and `8` in the RFC variant nibble. A direct check reported
`match=1 version=4 variant=8` for every literal and `distinct=3`. A and B remain
distinct race operations; C remains a new operation ID for the post-race
same-content call.

No other public-seam envelope incompatibility was introduced. The test-owned
fixtures still provide the required contender-specific values for
`clientOperationId`, `deviceTime`, integer `baseRevision`, integer `sectionId`,
and all required `photo_uploaded` metadata (`sha256`, `mime`, integer `size`, and
`originalName`) plus valid matching PNG bytes. The verifier can and must supply
the fixed valid UUIDv4 `deviceInstallationId`, operation type, synthetic
`HttpUser`, and fixed constructor/server time required by the already approved
specification. Nothing in the corrected literals prevents both calls from
reaching `storePhoto`; Gate 5 must still verify that the implemented verifier
actually constructs this valid envelope and invokes the public seam.

## Prior v3 sensitivity preserved

All v3 assertions remain present: exact literal-fixture consumption; two
distinct child PIDs and MariaDB connection IDs; one shared barrier; exact
winner-neutral accepted/rejected cardinality and aggregate deltas; accepted
contender identity; zero loser mutation; same-content-at-cap duplicate with no
mutation; two-token deterministic transcript; SQL/storage namespace collision
rejection; bounded child-crash, regression and child-timeout classification;
process-group reaping; exact cleanup; and ambient SQL/storage decoy preservation.
The specification hash remains pinned and unchanged. The corrected test still
cannot be satisfied by an echo-only verifier, and expected values remain
independent literals rather than values derived from production behavior.

## Reproduced intended RED

The disposable MariaDB container reached healthy state. The focused test loaded
the pinned specification and fixtures, created its ambient SQL/storage decoys,
and then failed only because the public verifier does not yet exist:

```text
RED_ASSERTION: missing public photo-limit concurrency verifier must become a successful first run; evidence={"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-checklist-photo-limit-concurrency.php\n","timed_out":false,"pid":179310,"process_group_id":null}
Expected: 0
Actual: 1
```

This is the intended missing-behavior RED, not an environment failure. Teardown
removed the disposable container, volume and network. No generated
`.local/test-artifacts/characterize-photo-limit-*` directory remained.

Additional checks:

```text
$ php -l tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php
No syntax errors detected in tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php

$ git diff --check -- specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php
# exit 0, no output
```

## Reviewed hashes

```text
e9481cf5239c407c52383a91289c4d17779ef32b6dd3da82d1aff9a1c6dfd820  specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md
76390192a1b9d8afb7481d31a400284262fb7f718dbcdf05b69c9f94f4cb37cc  tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php
17e0c30cd4fb130148104debba6af78915df2e48f9b28e4ca0e3b07ce038f5b2  reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001-v3.md
```

Gate 3 is approved only for the exact specification and test hashes above. Any
further change to either artifact invalidates this approval. Gate 4 may now
implement only enough verifier behavior to make this reviewed test green.
