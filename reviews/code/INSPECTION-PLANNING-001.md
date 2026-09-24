# Code review: INSPECTION-PLANNING-001

- Reviewer: independent `gpt-5.6-sol/low` Gate 5 agent `/root/final_review_issue14`; reviewer authored no reviewed artifact.
- Reviewed source: CI-correction candidate `d2393efd3263d20c64c6aca7f38aeb86ac356188842a1d61a616b15f752d13da`, commit `b2fe19b6`, package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T180622Z-6abac5577c/package.json`, based on `origin/main` `4a85da40`.
- Correction delta: retains eventless-root calendar compatibility while event-backed v34 rows remain event-derived and cancelled rows remain excluded; advances exact current-frontier/schema/index/recovery fixtures to v34; moves the DB-backed planning-schema test from unit to integration; gives the browser fixture the exact manager role; and aligns the production-migration test default with the repository test-root credential. The complete failed-CI inventory was inspected before this correction. No approved authorization, history, replay, concurrency, migration, or planning-only semantic was weakened.
- Scope: narrowed issue #14 owner/schema/current-plan seam and retained Yii calendar compatibility. New presentation in issue #255 remains excluded.
- Gate 3: final migration-preflight test delta `APPROVED` in `reviews/tests/INSPECTION-PLANNING-001.md`.
- Verdict: **APPROVED**.

## Findings

None remain.

All previously returned findings are resolved on this exact source:

1. Global scope requires active exact `manager` or canonical `fkr_operator`, exact `inspection.schedule`, and separate exact `objects.read`; ordinary readers remain native-assignment scoped.
2. Cancelled dates can be reused through a new immutable root while append-only history is retained.
3. Reschedule is event-only and does not rewrite historical root facts.
4. Only the sole current aggregate may be rescheduled or cancelled; a past root cannot be revived beside a newer current plan.
5. Existing Yii calendar compatibility reads the latest non-cancelled effective event date, not stale or synthetic root dates.
6. v34 DDL recognizes and resumes supported durable intermediate frontiers rather than relying on transactional rollback of MariaDB DDL.
7. Ready-schema adoption validates the whole root family and atomically adopts eventless rows; mixed-current and injected-failure cases fail safely.
8. Before any DDL, migration preflight now compares populated migration-frontier receipt fields with their deterministic legacy version/request/fingerprint tuple and rejects named schedule/receipt indexes unless their columns and uniqueness are exact.

The legacy v9 definition remains preserved, v34 is the canonical production/runtime-recovery frontier, object identity excludes engineer, command replay/concurrency remain serialized, and current reads do not create execution/checklist/progress/evidence facts.

## Verification

- Five mapped core commands and all 34 refreshed local planner commands: GREEN on the CI-correction exact source.
- Architecture check, strict OpenSpec validation, PHP syntax checks, and `git diff --check`: GREEN.
- Independent standards and specification rereviews found no remaining or new findings.
- `production_migration_runner_001_test.php` remains local `SETUP_FAILURE` because DB-root authentication failed. This obligation remains **UNKNOWN**, not GREEN, and this review does not waive the required exact-source CI.

Gate 5 is approved for the identified CI-correction exact candidate. The prior failed CI run is not approval; a corrected authoritative exact-source CI run is still required. CI GREEN, merge, deployment, and live behavior are not approved by this record.
