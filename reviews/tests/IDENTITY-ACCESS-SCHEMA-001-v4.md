# Test rereview: IDENTITY-ACCESS-SCHEMA-001 v0.1

- Date: `2026-09-01`
- Reviewer: fresh independent agent `identity_access_test_rereview_20260901n`
- Test authors: separately tasked Gate 2 agents; this reviewer authored no tests, specification or production code
- Supersedes: `reviews/tests/IDENTITY-ACCESS-SCHEMA-001-v3.md` after latent fixture corrections
- Reviewed state: authoritative dirty worktree based on `79658fa`
- Specification: `specs/IDENTITY-ACCESS-SCHEMA-001.md`, owner-approved version `0.1`
- Approved amendment: `docs/operations/identity-access-gate1-diagnostic-seam-amendment.md`
- Superseding evidence: `docs/operations/identity-access-schema-red-evidence-v9.md`
- Verdict: `APPROVED`

## Independence and scope

I reviewed the approved executable specification and diagnostic-seam amendment,
all four `canonicalize-identity-access-schema` OpenSpec artifacts, historical
Gate 3 reviews through v3, RED evidence v8/v9, the current schema/application
contract suite, immutable first-GREEN helper, runtime SQL observer and isolated
MariaDB runner. Production draft code was inspected only to establish that the
fixtures reach the approved public seams and that production behavior does not
mask setup. I changed no test, specification, production source or OpenSpec task.

## Findings

1. The engine defect remains legal and sensitive. It converts the unreferenced
   `fm2_pilot_user_status_events` table to MyISAM, avoiding MyISAM's key-length
   setup failure while independently violating the required InnoDB fingerprint.
   The fixture executes and the full conflict matrix remains green.
2. The runtime observer performs the explicit bootstrap once, then clears the
   MariaDB general log before exercising migrated login, invitation, role and
   block/unblock behavior. It does not issue a duplicate setup CREATE for the
   status-event table. The missing branch explicitly drops that member; the
   incompatible branch creates a test-owned one-column member only after the
   missing fixture is cleaned up. Both reach the HTTP runtime seam and require
   exact safe status 400, unchanged user state and zero runtime DDL. Migrated
   block/unblock requires success and zero DDL.
3. Full-family diagnostic lists are asserted at the approved application result
   seam in normative family order: conflicts `users, roles`, then missing
   `auth_attempts, user_status_events`. Production preserves traversal order;
   no lexical sort or test-side normalization masks the assertion. Created-table
   lists are likewise asserted in family order.
4. The unexpected-v6-failure/post-v6-short-circuit helper remains byte-identical
   to its Gate 2 approved SHA-256
   `9a255b2d3d1df6e1a4fb56ab7f63aade58f5dc137637c6ce5525f219cc50919b`.
   The schema suite now invokes it through public `CanonicalMigrationApplication`
   adapters and proves exact exit 70, exact redacted `MIGRATION_FAILED` JSON,
   empty stderr and zero later-migration invocations.
5. Test-owned literal manifests still cover all nine tables, and the independent
   category-complete conflict matrix, clean/repeat/populated/partial recovery,
   prefix isolation/bounds, no-seed behavior and zero-mutation assertions remain
   intact. No expectation was weakened by the v9 corrections.
6. The canonical suite creates a random database and removes it in `finally`.
   The runtime suite uses a randomized tmpfs MariaDB container and random
   database, with trap cleanup. The post-run container query found no
   `fm2-ia-red-*` residue.

## Reproduced verification

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/identity_access_schema_001_test.php
PASS: IDENTITY-ACCESS-SCHEMA-001 canonical runner and runtime ownership

tools/verification/run-identity-access-isolated-red.sh
PASS: IDENTITY-ACCESS-SCHEMA-001 isolated runtime observer
```

Additional results:

- PHP lint passed for the schema suite, runtime observer and first-GREEN helper;
- shell syntax passed for the isolated runner;
- `openspec validate canonicalize-identity-access-schema --strict`: PASS;
- focused `git diff --check`: PASS;
- no randomized runtime-observer container remained after cleanup.

Reviewed artifact SHA-256 values:

- canonical/application suite:
  `84aecfdf7898dbc5b6a825178b9a7f3edcf1daec43902782d6c9dc51b6c8302b`;
- runtime observer:
  `1869c88980c3d9330eba2293844810f2c932496bb449fc4640ba205b43ce10d8`;
- immutable first-GREEN helper:
  `9a255b2d3d1df6e1a4fb56ab7f63aade58f5dc137637c6ce5525f219cc50919b`;
- isolated runner:
  `69839ff70d6096201d34eb2202fbac89586d1df3b4fbe7cc8b09674c63cde936`.

## Gate decision

Gate 3 rereview is `APPROVED`. OpenSpec task `2.4` is explicitly authorized to
be checked. Continuation of Gate 4 minimal GREEN and its required regression,
architecture, strict-validation and independent code-review sequence is
authorized within the approved identity/access schema-ownership scope.
`GRILL-002` remains out of scope: this approval authorizes no RBAC or
authorization behavior change.
