# V12 consumer fixtures — corrective GREEN, parent blocker retained

Дата: 2026-09-05. Initial patch author `/root/importer_authority_review`;
corrective patch author/implementer `/root`.
Independent corrective Gate 3 commit `53a7c2e`, verdict APPROVED.
Применён только approved corrective patch SHA-256
`a06f772d4dae6d36006df830f3bf6848753fa6b588c55e97d98e6360dc9080f3`.

Все 11 allowed affected commands последовательно повторены с documented
test-only admin password, PHP child timeout180s; все дали exit0:
checklist_template_schema, classification_provenance_schema,
identity_access_schema, inspection_evidence_schema,
inspection_item_complete_001_mariadb, inspection_planning_schema,
installation_completion_schema, pilot_case_import, pilot_http_auth,
workforce_canonical_runner и verify-calendar-projections.

Дополнительный unchanged pilot_demo_bootstrap, как и требовал review, повторён
и дал exit255 только на inherited protected pilot_e2e_flow child. Общий batch
exit1 сохраняет этот failure; он не объявлен skip/allowed outcome/интеграционным
GREEN. Bounded amended-input result — 11/11 PASS; parent integration остаётся RED.

Полный raw log вне repository `/tmp/fmonitor2-v12-consumer-green-v2.log`, SHA-256
`9b53d950598dba65d0e9bb8c8e4e831c49c23c45f30de4364a55bf7447c4ee83`.
Все процессы завершились; timeout не сработал. PHP lint 11 affected files — PASS,
git diff-check — PASS; `make architecture-check` — PASS (7 rules).

Final corrected file hashes:

```text
e73dbd76fa7533bcca59c31a333f2c75e9439c27b56cafc84557e4b0033ec521  tests/InstallationProcess/classification_provenance_schema_001_test.php
942c8293b669a08697f6b1f860bc070e068e46c159fb8b76c8a299560c82e324  tests/InstallationProcess/workforce_canonical_runner_001_test.php
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php (unchanged)
dddec91ba654b1503e4051cd732325a9fed7ff166a1d3ff2cb101b9593f6c0b3  tests/Support/ProductionMigrationRunnerCatalogContract.php (unchanged)
```

Нужен fresh independent Gate 5 всего final fixture diff. OTIZ имеет отдельный
APPROVED Gate5, но broader characterization/full make verify ещё следует
повторить на exact final SHA. Parent tasks4.1/4.2, importer no-DDL,
safe-log/original workflow и launch goal этим результатом не завершаются.
