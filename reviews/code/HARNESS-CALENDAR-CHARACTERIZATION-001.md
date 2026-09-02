# HARNESS-CALENDAR-CHARACTERIZATION-001 — independent code review

- **Verdict:** `APPROVED`
- **Reviewer:** separately tasked Codex agent `/root/calendar_characterization_review` (independent; did not author the harness change)
- **Scope:** working-tree diff limited to `rapid-pilot/verify-calendar-projections.php`
- **Behavioral source:** `rapid-pilot/AGENTS.md`, `docs/operations/status.md`, and the existing `RapidPilotCalendar::read()` / `render()` behavior

## Standards

No blocking finding. The change stays within rapid-pilot's allowed characterization/wiring boundary and adds no domain command, persistence rule, or scheduling policy.

The added `require_once` restores the verifier bootstrap required by the existing `RapidPilotCalendar::read()` call to `RapidPilotInspectionSchedule::ensureSchema()`. Pre-registering `fm2_pilot_inspection_schedule_events` and `fm2_pilot_inspection_schedules` in the reverse-order `finally` cleanup covers tables that the existing runtime bootstrap may create before the harness-owned `$create` helper sees them. This is harness isolation, not new production behavior.

No actionable Fowler-baseline smell was introduced: the three edits are local to one verifier and reuse the runtime class and table names already consumed by the unchanged calendar implementation.

## Spec / scope

No blocking finding. The implementation is exactly the named `HARNESS-CALENDAR-CHARACTERIZATION-001` repair from `docs/operations/status.md`: it restores the missing `RapidPilotInspectionSchedule` bootstrap and cleans up its runtime-created tables.

Changing the row-header assertion from `2` to `3` characterizes the already-existing third `inspection` row in `RapidPilotCalendar::render()`. It does **not** add an inspection fixture, assert an inspection event, change the expected event types, or bless scheduling semantics. The stronger existing event assertions remain unchanged and still require exactly six deterministic events with the exact distribution `planned_start => 5` and `planned_end => 1`; therefore an unexpected inspection event still fails both count and type checks. The exact grid/disclosure assertions and fail-closed source-overflow check are also unchanged.

Consequently this diff does not promote the inventory's `UNKNOWN` inspection-scheduling behavior into a requirement and does not weaken any assertion. The row count becomes accurate for existing renderer structure while event semantics remain constrained to planned dates.

## Verification evidence

- `make test-db-reset` — PASS (`TEST_DB_RESET_OK`) against the disposable MariaDB harness.
- Focused `php rapid-pilot/verify-calendar-projections.php` with the Makefile harness database variables — PASS: `PASS calendar bounded projections, deterministic DOM and fail-closed overflow`.
- Post-run `SHOW TABLES` leak probe — PASS: no `cal_*` verifier tables remained.
- `make architecture-check` — PASS: `ARCHITECTURE CHECK PASSED (6 rules)`.
- `git diff --check -- rapid-pilot/verify-calendar-projections.php` — PASS.
- The broader `bash tools/verification/run.sh characterization` also passed this calendar verifier, then stopped later in unrelated `verify-otiz-workflow.php` because it resolved database host `mariadb` outside the Compose application network. That later setup failure does not contradict the focused calendar result or exercise this diff.

**Summary:** Standards: 0 findings. Spec/scope: 0 findings. Worst issue on either axis: none.
