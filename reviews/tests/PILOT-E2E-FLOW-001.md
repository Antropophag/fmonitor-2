# Test review: PILOT-E2E-FLOW-001 v0.3

- Gate: 3 — fresh independent review of the unified command-time oracle
- Reviewer: separately tasked agent `/root/e2e_test_review_v4`
- Test author: separately tasked Gate 2 author; reviewer authored neither reviewed input
- Specification commit: `d99df4e0eb8119f39281daa3cb99175c9880ffd4`
- Test commit / reviewed artifact: `0bbc51e56607dea57f81010ead1e0a9d40cdf6f3`
- Specification: `specs/PILOT-E2E-FLOW-001.md`, version `0.3`, `APPROVED`
- Test: `tests/InstallationProcess/pilot_e2e_flow_001_test.php`
- Public seam: configured production raw HTTP under `/pilot`, isolated MariaDB `fm2_*` state, and production artifact store
- Date: `2026-08-29`
- Verdict: `APPROVED`

## Findings

None.

## Review assessment

- **Normative time oracle:** v0.3 correctly makes prepare use `2026-08-27T12:30:00+03:00`, derives `assignmentOrderDate = 2026-08-27` from that exact instant in `Europe/Moscow`, then advances the production clock to the already approved registration and opening instants. This matches `ORDER-PREPARE-002` sections 5 and 7, whose successful command obtains the business date from the server command moment and records that same moment as `occurred_at`, plus `DECISION-004`'s `Europe/Moscow` rule. It removes the forbidden independent `FMONITOR_BUSINESS_DATE` oracle.
- **Traceability and sensitivity:** the test cites approved spec v0.3 at `d99df4e`. Its fixture now exposes only `FMONITOR_NOW` for the prepare instant. The unchanged card/action assertion is deliberately sensitive to production composition still requiring an independent business-date environment value, while the final durable-event assertion pins all three exact command instants.
- **Correction scope:** relative to previously approved test commit `815e4e9`, the executable changes are limited to four time-oracle edits: spec citation, removal of `FMONITOR_BUSINESS_DATE` plus correction of prepare `FMONITOR_NOW`, one server restart advancing the clock before registration, and the expected prepare event instant. No assertion is deleted or weakened.
- **Coverage unchanged:** all prior raw-HTTP journey, routing/method, capability/authentication, CSRF/body, PRG/error, validation, concurrency, queue/card, artifact bytes/integrity, append-only persistence, no-task, and no-legacy-write assertions remain. The literal artifact oracle is correctly unchanged because the derived order date remains `2026-08-27`.
- **Expected-value independence:** the three instants and derived prepare date are literals fixed by approved v0.3 and inherited domain contracts, not read from production output. Artifact lengths/hashes retain their independently derived v0.2 oracle. No implementation detail, mock, private method, or stored output is used to establish an expectation.
- **Determinism and isolation:** the test uses exact clock values, identities, randomized bounded database/user/artifact names, production migrations, real MariaDB and production HTTP composition, with `finally` cleanup. Restarting the server between command phases deterministically changes only the production clock input; persisted process state and client cookie remain stable.

## RED verification

Command run in a clean detached worktree at exact commit `0bbc51e`:

```text
$ php tests/InstallationProcess/pilot_e2e_flow_001_test.php
PHP Fatal error: Uncaught TestFailure: launch action visible Сформировать распоряжение
Expected: true
Actual: false
at tests/InstallationProcess/pilot_e2e_flow_001_test.php:54
exit code: 255
```

The test completes schema creation, all production migrations and fixture setup, starts the production HTTP server, and successfully reads both queue and object card. It first fails because the production card composition still treats the prepare command as unconfigured when the now-forbidden separate `FMONITOR_BUSINESS_DATE` input is absent. This is the intended missing clock-driven UI/composition behavior, not a broken database fixture, server setup, HTTP transport, dependency, or artifact oracle.

## SHA-256 reviewed-input manifest

```text
d3976ec3a49f81763a1c02d7a7fa8bcf532b69b88e240a3549fbac1333821fc2  specs/PILOT-E2E-FLOW-001.md
6df5a950c8cb0e53da96e18bbb71f4d57218c8ec4a1f5abdb2be295c5dd8b2ed  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Git blob identities:

```text
8f384ae1f889b1af544e4e8f40b2f93132c523bf  specs/PILOT-E2E-FLOW-001.md
69af793c19e8c5fb9428cde781d9c98b960b432e  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Any byte change to either reviewed input invalidates this approval. The review record is excluded from the self-referential manifest.

## Required changes

None. Gate 3 is approved for test commit `0bbc51e`; Gate 4 may proceed only against these exact reviewed inputs.
