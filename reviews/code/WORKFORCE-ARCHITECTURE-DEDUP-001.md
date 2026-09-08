# WORKFORCE-ARCHITECTURE-DEDUP-001 — Gate 5 code review

- Reviewer: Codex agent `/root/workforce_dedup_code_review`
- Base commit: `6cc90b81532cad888510f146f18bc8aa27a02d7f`
- Reviewed commit: `8599929dbd76ebfac18fad00ed84b7496ca6e57d`
- Executable source: `tests/InstallationProcess/workforce_canonical_runner_001_test.php`
- Reviewed source SHA-256: `1d864afb89d9e06762528182073462e15562e247372f104f5ba711c35a1c1be7`
- Verdict: **APPROVED**

## Standards

No findings. The code delta is the exact 17-line deletion approved at Gate 3:
the workforce verifier no longer invokes the repository-wide
`make architecture-check`, and the now-unused `wcrRunCommand()` helper is removed.
There is no production, schema, runtime, permission, CI, Makefile, or architecture
checker change. The deletion reduces test orchestration duplication and introduces
no new abstraction or maintainability smell.

`git diff --check` passes, and PHP reports no syntax errors for the changed test.
The reviewed after-source SHA-256 exactly matches the Gate 3 pre-approved deletion
hash. The base source SHA-256 independently matches the recorded before hash
`ed86cf88d6bce60c2deeb6c09c35074437c6d84ead96530ba634bff775e9073e`.

## Specification

No findings. The unchanged `wcrAssertRuntimeOwnership()` still scans `app`,
`rapid-pilot`, `public`, and `bin`, and still rejects workforce v2/v5 direct
`apply()` calls and workforce-targeted `CREATE`, `ALTER`, or `DROP` outside the
approved owners. Its invocation remains in the complete public CLI/database
matrix. All clean, repeat, partial recovery, conflict, failure, prefix, schema,
row-preservation, and ownership assertions required by
`WORKFORCE-CANONICAL-RUNNER-001` §§5–7 remain intact.

The removed child process duplicated a general repository gate rather than a
workforce acceptance example. The canonical `make test` aggregate still runs
`architecture-check` as its own stage, counts a failure, exits nonzero, and emits
`VERIFY_OK` only when no stage failed. The CI `fast` job still runs
`make lint architecture-check`, while its final aggregation still requires the
expected job results. This preserves the baseline and failure guard: removing the
nested invocation does not weaken the mandatory architecture ratchet or permit a
failed architecture stage to produce success.

## Verification evidence

- Gate 3 independently approved the exact before/after hashes before implementation.
- Three ordinary baseline runs passed: `41.640s`, `39.903s`, `39.929s`.
- Three ordinary after runs passed: `3.403s`, `3.366s`, `3.399s`.
- Median runtime changed from `39.929s` to `3.399s`, saving `36.531s` (`91.5%`).
- Dynamic make-spy before the deletion completed the DB/CLI matrix, observed one
  nested `make`, returned sentinel exit `97`, and made the verifier fail with exit
  `255` at the removed general-gate assertion.
- Dynamic make-spy after the deletion passed the full workforce verifier and
  produced no trace, proving that the nested process is absent while the unchanged
  matrix still completes.
- Exact-head no-DB regression checks passed: full-harness aggregation including
  the architecture-failure path, the 9-case CI aggregation matrix, standalone
  architecture checks (`7/7`), lint, and diff check.
- Reviewer checks: exact three-dot diff, source hashes, `git diff --check`, PHP lint,
  workforce spec §§5–7, Makefile aggregate, and CI fast/final aggregation.

## Verdict

**APPROVED.** The change is a bounded test-composition cleanup. It preserves the
workforce-specific ownership and public migration matrix, while the repository's
canonical architecture stage and fail-closed success guard remain independently
enforced.
