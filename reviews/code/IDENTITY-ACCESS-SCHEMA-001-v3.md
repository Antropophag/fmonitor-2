# Code rereview: IDENTITY-ACCESS-SCHEMA-001 v0.1

- Date: `2026-09-01`
- Reviewer: fresh independent agent `identity_access_code_rereview_20260901ab`
- Independence: reviewer authored neither the implementation nor any Gate 2 test
- Supersedes: `reviews/code/IDENTITY-ACCESS-SCHEMA-001-v2.md`
- Reviewed state: authoritative dirty worktree at
  `79658fa1e12e9d5fe4b795b628de3d4f9ccf23af` plus the scoped uncommitted
  identity/access slice
- Specification: `specs/IDENTITY-ACCESS-SCHEMA-001.md`, owner-approved `v0.1`
- Amendments: `docs/operations/identity-access-gate1-diagnostic-seam-amendment.md`
  and `docs/operations/identity-access-gate1-uca-alias-amendment.md`
- Gate 3 authority: `reviews/tests/IDENTITY-ACCESS-SCHEMA-001-v7.md`, verdict
  `APPROVED`
- Gate 4 evidence: `docs/operations/identity-access-schema-green-verification.md`
- Verdict: `APPROVED`

## Historical finding closure

All findings from both prior Gate 5 records are closed without changing the
approved behavioral expectations.

1. The canonical database/default-collation preflight now executes inside
   `CanonicalMigrationApplication::run()` before any migration. A typed
   `DatabaseUnavailable` is redacted to exact exit `69`; every unexpected
   `Throwable` is redacted to exact exit `70`; neither path invokes a migration
   or records a mutation. The CLI has no earlier unredacted metadata branch.
2. `bin/fmonitor2-migrate.php` consumes
   `IdentityAccessDefinitionSchemaMigration::tables()` and no longer duplicates
   the nine-table catalogue used to choose the reporting boundary.
3. `MariaDbIdentityBootstrapApplication` and `MariaDbUserStatusApplication` are
   formatted as named, reviewable application owners. Their extraction keeps
   the characterized bootstrap, role, credential, transaction, block/unblock
   and audit behavior exact; no new policy decision or measured hotspot is
   hidden by physical-line minification.
4. Semantic fingerprints ignore only presentation names. Index category,
   uniqueness, type and ordered columns plus FK source/target columns, exact
   target table and delete/update rules remain significant. Database collation
   preflight retains exact utf8mb4, safe-name, exact-or-approved-UCA-alias and
   safe trial-application checks before target DDL.
5. Request paths contain no identity `CREATE`, `ALTER`, `DROP` or repair. The
   status transition is behind one application seam, checks the complete
   compatible family before mutation and preserves existing policy/audit
   outcomes. Explicit destructive rebuild remains a separately invoked
   bootstrap operation. No RBAC authority, permission meaning, catalogue,
   fallback or authorization outcome was promoted by this slice.

## Independent verification

The reviewer ran the current worktree against MariaDB 11.4.7:

- canonical preflight application contract: PASS, exact typed `69` and
  unexpected `70`, zero migration invocations/mutations;
- full identity/access canonical runner suite: PASS;
- isolated runtime DDL observer: PASS;
- auth hot-path verifier: PASS;
- immutable v6 failure/post-v6 short-circuit application contract: PASS;
- generated-name, exact fingerprint, latin1 rejection and UCA-alias/default
  collation cases (within the canonical suite): PASS;
- relevant PHP lint: PASS;
- `make architecture-check`: PASS, 7 rules, baseline unchanged;
- `openspec validate canonicalize-identity-access-schema --strict`: PASS;
- `git diff --check`: PASS;
- full `make verify`: all setup, migration, architecture, lint, unit,
  characterization and diff stages passed; DB reported exactly the known eight
  CSP/local-RBAC/assignment-artifact failures and E2E reported the same known
  assignment-artifact failure, ending with exact
  `FULL_VERIFICATION_FAILURE count=2 stages=db-test,e2e-test`. No identity/access
  regression appeared.

An attempted `npx @fission-ai/openspec@1.0.10` invocation failed because that
package version does not exist in the registry; this is not project evidence.
The repository-installed `openspec` executable performed the required strict
validation successfully.

## Findings

None.

## Gate decision and scoped commit authority

Gate 5 is approved. OpenSpec tasks `4.3` and `5.1` are authorized for completion
after recording this review/status closure and re-running the cheap staged
checks. A dedicated local commit is authorized; no push is authorized.

Because the authoritative worktree contains many other accepted and in-flight
changes, the committer must stage only the identity/access-owned paths below,
after confirming each path's current diff still belongs to this slice:

- production: `bin/fmonitor2-migrate.php`,
  `app/InstallationProcess/CanonicalMigrationApplication.php`,
  `app/InstallationProcess/DatabaseUnavailable.php`,
  `app/InstallationProcess/IdentityAccessDefinitionSchemaMigration.php`,
  `app/InstallationProcess/IdentityAccessSchemaMigration.php`,
  `app/InstallationProcess/IdentityAccessSemanticFingerprintSchemaMigration.php`,
  `app/PilotHttp/MariaDbIdentityBootstrapApplication.php`,
  `app/PilotHttp/MariaDbUserStatusApplication.php`,
  `rapid-pilot/IdentityBootstrap.php`, `rapid-pilot/UserAccessView.php` and
  `rapid-pilot/verify-auth-hot-path.php`;
- executable/integration tests: the four
  `tests/InstallationProcess/identity_access_schema_001*` / runtime observer
  files, `tests/InstallationProcess/production_migration_runner_001_test.php`,
  `tests/InstallationProcess/workforce_canonical_runner_001_test.php`, and
  `tests/Support/ProductionMigrationRunnerCatalogContract.php`;
- contract/planning: `specs/IDENTITY-ACCESS-SCHEMA-001.md` and the complete
  `openspec/changes/canonicalize-identity-access-schema/` tree;
- append-only evidence/reviews: every
  `docs/operations/identity-access-*`, every
  `reviews/tests/IDENTITY-ACCESS-SCHEMA-001*`, and every
  `reviews/code/IDENTITY-ACCESS-SCHEMA-001*`, including this record;
- closure only: the identity/access paragraphs/tasks in
  `docs/operations/status.md` after verifying that no unrelated status edits are
  accidentally staged.

Other modified/untracked paths are not implicitly authorized by this approval.
In particular, broad PilotHttp/global-call formatting, unrelated harness,
Compose, architecture, photo, generation and future-schema changes must remain
outside this commit unless their own completed review record explicitly owns
them. Before commit, inspect `git diff --cached --name-only`, run
`git diff --cached --check`, and rerun focused/strict/architecture checks if the
staged content differs from the reviewed state.
