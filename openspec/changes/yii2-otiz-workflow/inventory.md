# Yii2 OTIZ route and owner inventory

Source checkpoint: `origin/main` `adf30398c62ca4d9fc77b1d3ecdaed68a5a228bc`.

| Method/path | Current production owner | Target | Classification |
|---|---|---|---|
| GET `/pilot/otiz`, `/pilot/otiz/`, `/pilot/otiz/objects` | Yii2 for exact non-slash routes; `RapidPilotOtiz::objects` remains fallback/oracle | Yii2 object register/read owner | migrate/close fallback |
| GET `/pilot/otiz/payments`, `/history`, `/snapshots/{id}` | Yii2 `OtizSettlementController` | retained Yii2 + existing read owners | retained/adjacent |
| GET `/pilot/otiz/snapshots/{id}/export.xlsx` | Yii2 `OtizSettlementController` | retained Yii2 exporter/read seam | retained/adjacent |
| POST `/pilot/otiz/calculate` | `RapidPilotOtiz::command` → `SnapshotPublication::buildAndPublish` | Yii2 adapter → same owner | migrate |
| POST `/pilot/otiz/snapshots/{id}/accept` | `RapidPilotOtiz::command` → `SnapshotPublication::accept` | Yii2 adapter → same owner | migrate |
| POST closures/payment-complete/reverse | Yii2 `OtizSettlementController` → `OtizSettlement` | retained Yii2/application owner | retained/adjacent |
| GET `/pilot/otiz/reconciliation` | `RapidPilotOtiz` read adapter | Yii2 adapter over existing evidence projections | migrate |
| POST `/pilot/otiz/reconciliation/decisions` | `RapidPilotOtiz` → `MigratedEvidenceDecisionLedger` | Yii2 adapter → same owner | migrate |
| GET `/pilot/otiz/reconciliation/quarantine` | `RapidPilotOtiz` read adapter | Yii2 adapter over existing quarantine projections | migrate |
| POST `/pilot/otiz/reconciliation/quarantine/decisions` | `RapidPilotOtiz` → `MigrationQuarantineDecisionLedger` | Yii2 adapter → same owner | migrate |
| GET `/pilot/otiz/active-baselines` | `RapidPilotOtiz` read adapter | Yii2 adapter over existing read model | migrate |
| GET `/pilot/otiz/historical-replay` | `RapidPilotOtiz` read adapter | Yii2 adapter over existing read model | migrate |
| GET `/pilot/assets/otiz.js` | `rapid-pilot/router.php` static handler | Yii2 asset bundle/controller only if still consumed | inventory then migrate/remove |

Forms use legacy `csrfToken` in `RapidPilotOtiz`; established Yii2 forms use `_csrf`. Public URL/method and outcomes remain stable, while Yii2 owns the framework token representation. Existing `rapid-pilot/otiz.js` contains presentation enhancement only and MUST NOT become a domain owner.

Runtime frontier consumers: `rapid-pilot/router.php` require/matches/handle/navigation calls, `PilotRouteCsp`, `public/runtime.php` fallback, Yii2 URL rules, views/assets, `rapid-pilot/verify-otiz-workflow.php`, all `verify-*otiz*`, `tests/Otiz/*http*`, `tests/Otiz/*browser*`, `tests/Yii2/yii2_otiz_settlement_001_test.php`, `tests/Runtime/runtime_settlement_compatibility_001_test.php`, verification categories/suites and deployment/runtime dependency checks.

Out of slice: console/import/workforce/jobs routes; schema migration/ledger changes; formula redesign #66; general `public/runtime.php` retirement; deployment. Characterization files remain executable historical evidence even after production dispatch is removed.
