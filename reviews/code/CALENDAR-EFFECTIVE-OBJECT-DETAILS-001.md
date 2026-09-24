# CALENDAR-EFFECTIVE-OBJECT-DETAILS-001 — Gate 5 final review

- Reviewer: `/root/final_review` (independent; authored none of the specification, tests, lifecycle artifacts or production implementation).
- Review date: 2026-09-24.
- Audit base: `b1542f92009b8dc4216a36962ff38a51e0b6c388`.
- Exact reviewed candidate source: `bc947d0eaf2b3e3b1c10fa4a569059c89bd4491988bf7ccc99dc4dff9da4ddbd`.
- Reconstructible snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015945Z-bf37b24ec6/snapshot/source.patch` (patch SHA-256 `7aa599cbd5e72232eedc53f079781ed018ec391210073ec52b34360b8c4336b3`).
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015945Z-bf37b24ec6/package.json`.
- Verification-plan SHA-256: `86416d3b7f080c47b8e12a1cd2863cb4f2adfdd22df73b337d22d8bdfb7cb142`; planner decision: `CRITICAL`, required reviews `gate3` and `final`.
- Prior review: corrected Gate 3 test candidate independently **APPROVED** in `reviews/tests/CALENDAR-EFFECTIVE-OBJECT-DETAILS-001.md`; the active harness binding records that approval for this exact source.
- Verdict: **APPROVED**.

## Findings

No blocking or non-blocking code findings.

### Standards axis

The production delta remains inside the existing MariaDB calendar read adapter and reuses `MariaDbEffectiveObjectDetails::sqlValue()` rather than introducing another resolver. Both existing bounded queries join the one-row current edit projection (`PRIMARY KEY(object_id)`) once, so the change neither multiplies events nor adds per-object/N+1 reads. The source `LIMIT 5001` checks, combined 5000-event overflow check, inspection ordering and unchanged final event sort remain intact. The new SQL is read-only; date parameters, validated table prefixes and the resolver's validated SQL fragments preserve the existing safety boundary. Authentication, `objects.read`, GET/HEAD handling, cache and error mapping are outside and untouched by the diff. No documented-standard violation or material code smell was found.

### Specification axis

Both source queries resolve address, entrance and registration number through the existing effective-values helper. Inspection rows use those projections directly, and each planned row supplies both `planned_start` and `planned_end`. The existing helper preserves absent-key legacy fallback, explicit JSON null as SQL null, explicit empty string and non-empty corrected value. No date expression, event identity, count construction, ordering, permission, writer, history, schema, import, UI or deployment behavior changed.

The corrected acceptance test exercises absent, null, empty and later non-empty revisions for all three fields across all three event types. It checks the three-event count, normalized type/object/schedule/date sequence, legacy and schedule immutability, browser reload, read-only facts, and card/registry rendering and search. It would fail plausible partial-field, partial-event, duplicate, reordered or stale-revision implementations. Existing calendar regression retains validation, authorization/HEAD, schema-error, source-bound and combined-overflow coverage.

## Verification evidence reviewed

- Source-bound harness record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790215151252610000-a366568320384736929fc06e55037409.json`: `php tests/Yii2/yii2_calendar_effective_object_details_001_test.php` — **GREEN**, exit 0, exact start/end source `bc947d0e...`; this command includes the authenticated HTTP flow, browser reload and card/registry checks.
- Independent reviewer rerun: `php tests/Yii2/yii2_object_card_001_test.php` — **GREEN**.
- Independent reviewer rerun: `php tests/Yii2/yii2_object_queue_001_test.php` — **GREEN**.
- Independent reviewer rerun: `php tests/Yii2/yii2_calendar_003_test.php` — **GREEN**.
- Independent reviewer rerun: `python3 tests/Verification/change_verification_001_test.py` — **GREEN**, 18 tests.
- Independent reviewer rerun: `python3 tests/Verification/architecture_guard_001_test.py` — **GREEN**, 59 tests.
- PHP lint for the changed production file and acceptance test, plus `git diff --check` — **GREEN**.
- Full local `make test` / `make verify` was not run, as required.

This verdict approves the reviewed code snapshot for publication. The review record itself is the only post-snapshot addition and must be included in the final committed candidate. Exact committed-source GitHub CI is still required and is not implied by this approval; at review time PR/CI remain `UNKNOWN`.
