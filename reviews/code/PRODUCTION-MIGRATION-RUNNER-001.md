# Code review: PRODUCTION-MIGRATION-RUNNER-001

- Reviewer: `Codex agent /root/migration_code_review` (independent Gate 5 reviewer; did not author specification, approved tests, runner, or migrations)
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Production scope: runner, shared capability-CHECK classifier, and v3/v4 migration integration
- Specification: [`specs/PRODUCTION-MIGRATION-RUNNER-001.md`](../../specs/PRODUCTION-MIGRATION-RUNNER-001.md), version `0.5`, `APPROVED 2026-08-28`
- Approved test review: [`reviews/tests/PRODUCTION-MIGRATION-RUNNER-001.md`](../tests/PRODUCTION-MIGRATION-RUNNER-001.md), final v0.5 verdict `APPROVED`
- Previous Gate 5: `CHANGES_REQUIRED` for non-exact completed-v4 classification
- Superseding Gate 5: `APPROVED`

## Standards

`APPROVED`. `ProcessCapabilityChecksClassifier` is the single classification authority used by both v3 compatibility and v4 inspection. It requires exactly one capability and one engineer CHECK, rejects unknown/extra/duplicate candidates, and enforces the normative completed-v4 capability constraint name.

Capability parsing preserves quoted literal bytes, accepts only identifier backticks, SQL keyword case/whitespace, one balanced whole-expression wrapper, and order-insensitive exact `IN` membership. It rejects malformed, duplicate, missing and extra literals. Engineer parsing accepts exactly the approved `OR(A, AND(B,C))` presentations, preserves both literals and rejects changed precedence/operators/operands/branches. Whole-parenthesis stripping is balanced and quote-aware. The previous weak parallel normalizer no longer exists.

Runner environment presence, explicit empty password/prefix, pre-connection validation, charset-before-inspection, strict v1→v4 order, immediate conflict stop, exact JSON/exit/stderr, redaction and connection cleanup remain correct and maintainable.

## Spec

`APPROVED`. Whole-wrapped and permuted exact completed-v4 schemas are compatible no-ops at v3. Quoted whitespace/case changes, duplicate/extra capability literals and changed engineer grouping conflict at v3 before v4. Exact v3/v4 sets, CHECK cardinality, normative v4 name, clean application, idempotent repeat, partial recovery, charset fault and environment failure behavior match v0.5.

## Verification

```text
production_migration_runner_001: PASS
process_user_directory_001: PASS
process_command_authorization_001: PASS
PHP syntax: PASS
InstallationProcess suite: 37/37 sequential, 37/37 parallel PASS
scoped git diff --check: PASS
.test-artifacts children: none
```

The intentionally dirty handoff has no usable commit fixed point; unrelated changes were excluded.

## Findings

None.

Gate 5 is approved.
