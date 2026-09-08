# WORKFORCE-ARCHITECTURE-DEDUP-001 — Gate 3 test review

- Reviewer: Codex agent `/root/workforce_dedup_test_review`
- Reviewed commit: `6cc90b81532cad888510f146f18bc8aa27a02d7f`
- Reviewed source: `tests/InstallationProcess/workforce_canonical_runner_001_test.php`
- Source SHA-256 before proposed deletion: `ed86cf88d6bce60c2deeb6c09c35074437c6d84ead96530ba634bff775e9073e`
- Expected source SHA-256 after the exact deletion below: `1d864afb89d9e06762528182073462e15562e247372f104f5ba711c35a1c1be7`
- Verdict: **APPROVED**

## Exact approved deletion scope

Delete only the two statements at current lines 270–271 that invoke `make architecture-check` and assert its exit code, then delete the now-unused complete `wcrRunCommand()` helper at current lines 274–287 and its trailing blank line 288. Keep `wcrAssertRuntimeOwnership()` itself, its complete scan of `app`, `rapid-pilot`, `public`, and `bin`, every workforce direct-apply/DDL exclusion and assertion, its call at line 446, and the complete public CLI/database migration matrix unchanged.

## Assessment

This removes only duplicate orchestration. The test continues to prove the workforce-specific runtime ownership rule required by sections 5–7 of `WORKFORCE-CANONICAL-RUNNER-001`. The repository-wide architecture ratchet remains a distinct required stage in the canonical `make test`/`make verify` aggregate, and `.github/workflows/repository-verification.yml` runs it in the required `fast` job. `HARNESS-FULL-AGGREGATION-001` proves that the full runner executes the architecture stage, preserves its evidence, continues after its failure, exits nonzero, and omits `VERIFY_OK`; `VERIFICATION-PR-CYCLE-001` proves the CI aggregate requires successful fast/full evidence and fails closed for failed, cancelled, skipped, or missing results.

No additional permanent check is necessary. The planned before/after make-spy proof is appropriate transient evidence that this exact deletion reduces one nested invocation while preserving the canonical invocation. The change introduces no specification, runtime, database, CLI, migration-matrix, permissions, or CI-policy semantic change.
