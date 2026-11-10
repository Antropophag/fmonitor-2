# PR #291 CI repair — independent Gate 3 test/spec review

Reviewer: `/root/reconcile_gate3` (independent; no authorship of the CI-repair tests, specification, or planned implementation)

Verdict: **APPROVED for implementation of the two bounded source repairs**.

## Exact binding

- Base commit: `ed1b6cdb3805920b95ee35787d28fb40862a7997`
- Candidate source: `1f227b01bc45cadb3bd09a1cf732d62f0b587c1e5a560959b578cb41e0a7912f`
- Executable source: `b8ce75b491b498ad170ff85cdb8caf80d2a2239750b3f71c968ce92873b6d4bb`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20261110T090000Z-abd36afd97/package.json`
- Verification plan SHA-256: `b03048eb3894cad131ebe29bdf3801a826a894941f5477e018a3247a230ed0d6`
- Contract SHA-256: `4d651bced18eac03d7152a0d86626ae8c1c8da48ada2393197a56e49d2fe44ab`
- Verification input SHA-256: `77ebd80ef9d8736ca2f9c36ff5685c2f614c99a606d02dc7bcb7166f2c603c79`
- Architecture regression SHA-256: `02912ee297fa5cfff1fd7607838f5ff7922766a76bdf49773bad72a02615b48c`
- Dashboard regression SHA-256: `520cfe33c90d80e47b3c05348e71674286c5a2c4b63d374429b2829323099be5`

## Review

The complete first CI failure inventory was collected before corrections. The test delta separates two demonstrated implementation defects from stale oracles. The proposed implementation scope is limited to `tools/architecture/check.py` and `app/InstallationProcess/MariaDbYiiOperationalDashboard.php`; no financial, authorization, schema-history, or production-data mutation is authorized.

The architecture regression accepts ordinary unquoted JavaScript `select` identifiers/property access and native HTML `<select>` tags. Its negative controls retain rejection of SQL strings, concatenated fragments, multiline SQL, DML, DDL, and forbidden rapid-pilot writes. Existing architecture guard and PR #286 select-token protection remain GREEN. No baseline entry or file exemption is introduced.

The dashboard regression independently constructs the four-object stage boundary and requires total `4`, active `3`, and exact parity with canonical installation plus document-closeout filters. It binds the excluded registered change case by object ID `451204`, case ID `6104`, and displayed stage `Требуется изменение`, then proves queue and dashboard reads leave facts unchanged.

The remaining CI oracle corrections preserve the governing contracts: current schema 38 with 105 tables/59 AUTO tables while historical profiles remain immutable; valid four-group activation fixtures without weakening token, credential, role, or session checks; exact reserved-share object/installer amounts without redistribution; separate dismissed-worker reserves and warnings on both groupings; saved confirmation and no-write preview behavior; authoritative dismissal timing; adjusted-date precedence; and supported 10/25 pagination with complete crews and retained search. The reviewed exact monetary examples remain asserted through persisted obligations, payment, reversal, and reserve state.

## Gate 3 evidence

- GREEN: `tests/Verification/architecture_guard_001_test.py`
- INTENDED_RED: `tests/Verification/architecture_native_select_tokens_291_test.py`
- GREEN: `tests/Verification/architecture_js_select_tokens_286_test.py`
- INTENDED_RED: `tests/Yii2/yii2_dashboard_active_change_parity_291_test.php`

All four records bind the candidate source above. The architecture tests were run sequentially because the guard fixture temporarily mutates source during its self-test. `git diff --check` is clean. No full local suite was run.

The earlier dashboard identity-coverage finding is closed. No unresolved Gate 3 finding remains. A separate author may implement only the two planned source repairs. Independent final review and a new exact-source full GitHub CI run remain mandatory afterward.
