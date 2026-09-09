# PRODUCTION-JOBS-RECOVERY-001 review

## Gate1 — 2026-09-09

Planning author: runtime_review. Independent reviewer: root.
Verdict: **APPROVED (bounded)** for executable RED authoring; Gate3/implementation
are pending. Exact inventories match v22 base63/35 plus six Jobs tables/four
AUTO_INCREMENT families. Restore has no queue/outbox transition authority; stale
operational state cannot defeat valid schema/storage integrity. Ordered quiesce,
explicit fake-only resume and forward-only compatibility preserve existing Jobs
semantics without inventing product triggers or delivery certainty. V22 literal
contract and exact historical image remain intact. Final production integration
still requires independent executable/code review and CI.

```text
11368043aea14cb27e05629b0db9cce1a679c75e19123b7b570cc62427f8b47c  specs/PRODUCTION-JOBS-RECOVERY-001.md
e196c785bb1a15782156a5927ec9a8d2da79392402d4d8e89b4acd083b933314  openspec/changes/recover-durable-background-jobs/proposal.md
d4884efc94657878cae093f3696f279bae9135bcb649bab42d5e5e4fd2a59742  openspec/changes/recover-durable-background-jobs/design.md
aa770e8a20bcee442ba0617dc1eed8336ad42cff3f9f651fc15b7f7cab3e2355  openspec/changes/recover-durable-background-jobs/specs/operations/durable-jobs-recovery/spec.md
```

## Gate3 — forward/cross-version group

Test author: runtime_plan. Independent reviewer: runtime_review.
Verdict: **APPROVED**.

```text
8493212bca5db40f345a571c0cc88ffc05cc7123a9c062d4cb903623d3780d17  tests/Runtime/runtime_recovery_forward_update_001_test.php
```

The test builds and verifies the exact historical v22 source/image OCI revision,
exports its reviewed meaningful v22 bundle, restores it with that same image and
snapshots all 63 table rows, private state and 35 AUTO values including a deleted
high process-event frontier of1602. Migration23 preserves every old value and creates
the six Jobs tables empty.

The demonstrated RED occurs only when the current exact image tries to create the
new v23 bundle: expected `BACKUP_CREATED`, actual `BACKUP_FAILED`/70. Downstream
barriers require v23 23/69/39/empty-deferred metadata and v22-image rejection of the
v23 bundle with zero DB/state mutation. The separate v23 populated recovery/resume
test is not approved by this subsection and remains pending its own corrected Gate3.

## Gate3 — populated v23 recovery/resume group

Test author: runtime_tests. Independent reviewer: runtime_review.
Verdict: **APPROVED**.

```text
181779c898de26ba797df7bcc3f9317a5d5c3ceaf95f4b77acf56085b83c0125  tests/Runtime/runtime_jobs_recovery_001_test.php
11368043aea14cb27e05629b0db9cce1a679c75e19123b7b570cc62427f8b47c  specs/PRODUCTION-JOBS-RECOVERY-001.md
```

The independently literal 69-table and 39-AUTO inventories are exact. The source
contains non-vacuous ready/future, leased attempts1/5, completed and dead jobs;
append-only events; scheduler slot/role heartbeat rows; pending, ambiguous,
delivered and dead intents; and a linked outbox attempt. Attempt5 is built through
the public retry lifecycle and remains unexpired during the zero-mutation probe.

Source and restored snapshots compare all six Jobs tables, private state and all39
exact AUTO next values. A deleted high Jobs identity makes the counter oracle
meaningful. Wrong frontier/table/AUTO manifests reject before target DDL/state.
Resume uses a fresh DML-only account with exact MariaDB1142 CREATE denial and proves
lease reclaim/stale token/attempt5 dead, one pending sweep, delivered/dead exclusion,
fake ambiguous provider identity, authorized linked delivery settlement and
unchanged non-Jobs facts.

The demonstrated RED occurs after valid v23 migration and fixture setup: expected
`BACKUP_CREATED`, observed `BACKUP_FAILED`/70 with empty stderr because current
recovery remains v22-only. No production code was changed by the test author.

The final test-only harness amendment replaced an exhausted finite token list with
deterministic unique counter hashes and moved launcher exit after exact-tag cleanup.
It changed no lease expectation. Final execution is GREEN with exact output
`PASS: PRODUCTION-JOBS-RECOVERY-001 v23 exact backup/restore contract`, exit0 and no
remaining task image tag; evidence is `/tmp/fmonitor-v23-recovery-final-green.log`.
