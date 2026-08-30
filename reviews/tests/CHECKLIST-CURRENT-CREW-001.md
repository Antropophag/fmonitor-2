# Test review: CHECKLIST-CURRENT-CREW-001

- Reviewer: `Codex agent /root/test_review` (independent; did not author the specification, verifier, or implementation)
- Test author: `Codex agent /root`, working session `2026-08-30`
- Reviewed commit: working tree at HEAD `513363b02570ee25a5d5c699630803a544f7cced`
- Specification: [`specs/CHECKLIST-CURRENT-CREW-001.md`](../../specs/CHECKLIST-CURRENT-CREW-001.md)
- Public seam: `ChecklistSync::projection(1103)`
- Red command and intended failure: `php rapid-pilot/verify-checklist-current-crew.php` — `RuntimeException: Expected only latest registered order crew, got ["202","101"]`
- Initial verdict: `CHANGES_REQUESTED`
- Current verdict: `APPROVED`

## Re-review after historical-snapshot coverage (2026-08-30)

- Final verdict: `APPROVED`
- The verifier now seeds a completed item operation from before version 2 and an explicit immutable installer snapshot for installer `101` with assignment source `completion`. It independently requires the projected historical item to retain exactly `['101']` while top-level current crew is exactly `['202']`.
- Both expected arrays are fixed literals from the specification example. Neither is computed from current order rows, the workforce catalog, production selection logic, nor another implementation result.
- The captured behavioral RED is exact and sensitive to the reported additive defect:

```text
php rapid-pilot/verify-checklist-current-crew.php
RuntimeException: Expected only latest registered order crew, got ["202","101"]
```

  This fails because the current query unions installers from all registered versions in descending version order; it is not an infrastructure or incidental fixture failure.
- The same projection call observes both required sides without mutating history: the current selection source must use only the greatest registered order version, while the completed item's stored attribution remains `101`. An implementation that globally filters historical snapshots to the latest crew, rewrites old attribution, or backfills it from current crew fails the second assertion.
- Fixture isolation remains sound: unique random table prefix, fixed timestamps and identifiers, no dependency on workforce catalog values, and bounded cleanup of only the prefixed tables in `finally`.

All specification traceability, missing-behavior sensitivity, independent expected-value, determinism, and setup-isolation requirements are satisfied. The verifier is approved for implementation.

## Findings

- **The current-crew assertion matches the reported defect.** Two registered orders are created for the same open case: version 1 contains installer `101`, version 2 contains only `202`. Requiring projection crew to equal exactly `['202']` is sensitive to the current additive/union behavior and to retaining an installer removed by the later registered order.
- **Expected values are independent.** The expected tab ID is a fixed literal from the acceptance example and is not calculated by querying production tables, calling the implementation's crew-selection logic, or transforming its output beyond `array_column` for observation.
- **Latest registered-version selection is represented.** Both orders are registered and have distinct fixed versions. The exact result rejects unioning across registered versions. A higher unregistered order is not exercised, but that is not required to expose the user-reported additive behavior in this focused slice.
- **Blocking specification gap — completed-item history is untested.** The specification also requires an item completed before version 2 to keep displaying installer `101`. The verifier creates no checklist operation and no `fm2_checklist_operation_installers` snapshot, then observes only top-level `crew`. An implementation that fixes current crew by selecting version 2 but rewrites, drops, or backfills historical item attribution with `202` would pass.
- **The public observation for “new work” is reasonable but partial.** `projection()['crew']` is the selection source exposed to checklist clients, so the exact current crew is useful coverage. The verifier does not submit a new completion through `ChecklistSync::accept`, but that extra command assertion is optional if the projection contract is the intended public behavior boundary.
- **Setup isolation is bounded.** A cryptographically random table prefix isolates the fixture, all created schema tables are dropped in `finally`, and fixed object/version/installer/time values make the behavior deterministic. No expected value depends on mutable workforce data because the fixture does not populate the optional catalog table.
- **Local RED execution is currently infrastructure-blocked.** In this review environment the command fails before setup with `mysqli_sql_exception: Connection refused` at line 7 using the default `127.0.0.1:23306`. This is not acceptable behavioral RED evidence by itself; the intended assertion failure must be captured in the configured verifier database environment before approval.

## Required changes

1. Seed a completed checklist item whose immutable operation-installer snapshot is installer `101` from before version 2, then require `projection()['items'][...]` to retain/display exactly that installer while top-level current `crew` remains exactly `['202']`.
2. Ensure the historical assertion observes the stored snapshot rather than deriving its expectation from current crew or implementation output.
3. Run the verifier against the configured test database and record the exact behavioral RED showing the additive current crew (or another failure specifically caused by the missing latest-version selection), not a database connection failure.
4. Request fresh independent test review after correction.
