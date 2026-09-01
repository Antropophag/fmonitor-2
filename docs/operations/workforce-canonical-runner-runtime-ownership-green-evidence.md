# WORKFORCE-CANONICAL-RUNNER-001 — runtime ownership GREEN evidence

- Дата: `2026-09-02`
- Reviewed RED: `workforce-canonical-runner-runtime-v2-apply-red-evidence-v4.md`
- Test review: `reviews/tests/WORKFORCE-CANONICAL-RUNNER-001-runtime-v2-apply-v4.md`
- Supersedes code-review state: `reviews/code/WORKFORCE-CANONICAL-RUNNER-001-v2.md`
- Verdict: `GREEN — AWAITING_FRESH_CODE_REREVIEW`

Minimal production correction removed the bootstrap import, existence probe and
fallback call to `WorkforceCatalogSchemaMigration::apply`. Compose entrypoint
runs the canonical v1–v5 command before bootstrap; bootstrap and importer then
perform only `WorkforceHistorySchemaReadiness::assertReady` checks.

The absolute `workforce_migration_ownership` architecture rule is green and is
not baselineable. Its final 18-test suite covers exact canonical/migration-owner
allowlists, arbitrary v2/v5 aliases, and direct or variable-target
one-line/multiline workforce CREATE/ALTER/DROP, including path-specific fixture
exceptions.

Verification:

- full public-runner matrix — PASS;
- architecture unit suite — 18/18 PASS;
- `make architecture-check` — PASS (7 rules);
- strict OpenSpec validation and `git diff --check` — PASS;
- `make verify` — setup/migrate/architecture/lint/unit/characterization/diff
  PASS; only the established eight DB regressions and duplicated E2E artifact
  failure remain.
