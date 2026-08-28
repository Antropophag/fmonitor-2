# Legacy FMonitor data-estate audit

**Status:** source-code census; read-only; 2026-08-28
**Scope:** the legacy repository `../fmonitor`, excluding bundled third-party libraries and primary `.msg`, `.pdf`, office documents, production databases and network services. No legacy or product code was changed.

## Executive conclusion

`fm_maintable` is only the wide object projection. Material legacy facts also live in checklist definitions and values, installer-attribution rows and logs, checklist photos plus filesystem blobs, field-change history, workforce snapshots and status observations, production-order stages, drawing-folder links, users/roles/rights, organisations, and temporary backfill evidence. Any migration based only on `fm_maintable` would lose evidence needed for progress, attribution, workforce eligibility, audit, documents and calculation reproducibility.

This is an exhaustive inventory of names referenced by the checked-in first-party runtime source, not proof of an exhaustive live schema. Unreferenced/retired concrete business tables, views, triggers and stored routines can only be enumerated from database metadata. Per [ADR 0001](adr/0001-no-generic-legacy-metadata-platform.md), the generic MDM and custom-field/view-builder platforms are deliberately excluded from FMonitor 2.0 migration; their live enumeration is not a migration prerequisite.

Confidence labels: **high** means an active first-party query establishes the table and relation; **medium** means the name is present only in commented/maintenance code or its complete schema is unavailable; **unknown-live** means runtime code constructs the name dynamically or reaches another database.

## Discovered relational estate and proposed 2.0 disposition

| Dataset | Recoverable shape and implicit relations | Runtime owner / meaning | Confidence | Proposed disposition |
|---|---|---|---|---|
| `fm_maintable` | `id` object key; 116-column census exists; `installator{,2,3,4}` are tabular IDs with parallel `_fio`; `zavnumber` joins production orders/drawing folders | Generic table UI and imports mutate it and append `fm_data_changelog`; object identity and many denormalised states | high | Canonical import only for independently verified object identity/contracts; compatibility projection for unresolved fields; never preserve it as writable aggregate |
| `fm_fields` | `id`, `name`, `sysname`, `description`, `type`, `formula`, creator/time; `sysname` maps metadata to a physical `fm_maintable` column | Runtime schema, formula and UI-field registry | high | Excluded from migration and runtime; may be consulted read-only and transiently to interpret selected physical columns; formulas are never executed |
| `fm_fields_values` | `id`, `field_id`, `name`, `rang`; joins `fm_fields.id` | Enumerated values for dynamic fields | high | Excluded with the generic custom-field platform; no generic reference-value import |
| `fm_data_changelog` | `maintable_id`, `views_id`, `field_id`, `field_sysname`, typed old/new-like values, `ctime`, `user_id`, `temp_mark` are observable | Field-level change trail used by premium export | high | Historical evidence import, immutable and source-labelled; do not infer events blindly where rows are incomplete |
| `fm_install_checklist_parts` | `id`, `rang` and display attributes via `SELECT *`; parent of checklist items and photos by `part_id` | Checklist section catalogue | high | Canonical template migration after live-schema/content census |
| `fm_install_checklist` | `id`, `part_id`, `share` observed; item definition joined by `checklist_id` | Checklist template and progress weights | high | Versioned checklist-template import; retain original IDs and weights |
| `fm_install_checklists_values` | `id`; `value_id → fm_maintable.id`; `checklist_id → fm_install_checklist.id`; `value`, `ctime`, `cuser_id`, `utime`, `upd_user_id` | Mutable current completion state | high | Historical-evidence migration with reconciliation against log; do not treat current row alone as append-only fact |
| `fm_install_checklists_values_log` | `value_id` is **object ID**, plus `checklist_id`, `value`, `ctime`, `cuser_id` | Toggle history; naming differs from current-value PK relation | high | Canonical historical evidence after ordering/deduplication analysis |
| `fm_install_checklists_values_installators` | `id`; `checklist_value_id → values.id`; `tab_id`, `fio`, `ctime`, `cuser_id` | Mutable current attribution of completed item | high | Compatibility/current projection only; reconstruct trusted history from logs and source snapshots |
| `fm_install_checklists_values_installators_log` | same attribution payload; replacement code also records old/new tab/FIO/user fields where schema supports them | Attribution history, but runtime has also rewritten current attribution | high | Historical evidence, quarantining ambiguous/cascade-generated rows |
| `fm_install_checklist_files` | `id`; `dataid → fm_maintable.id`; `part_id → checklist part`; `user_id → users.id`; `name`, `ctime`, `extension`; blob paths derived from ID | Checklist evidence metadata | high | Import immutable metadata and hashes together with separately inventoried blobs; missing/orphan blobs become violations |
| `fm_installators` | `tab_id`, `tab_id_char`, name parts, `status`, `ctime`; refreshed by truncate/reload | Current Bitrix-derived workforce projection | high | Compatibility snapshot only; 2.0 workforce publication becomes canonical |
| `fm_installators_status_log` | `tab_id`, `full_name`, `status` and DB-default time presumed | First observation of inactive installer, not proven termination date | high | Historical observation evidence; never label its timestamp as actual dismissal date |
| `shlz_orders` | at least `number`; selected fields feed table screens | Production order projection | high | Read-only compatibility projection or canonical import only after ERP provenance/cadence is verified |
| `shlz_orders_etaps` | `codenum`, `dt_create`, `order_num`, `etap_num`, `etap_name`, `department`, `date_complete`, `date_push`, `status`; joins `order_num = zavnumber` | Production stages imported by integration | high | Historical/compatibility projection; reconcile with owning production system |
| `installation_drawings_folders` | `b24id`, `name`, `link`; `name = zavnumber` | Bitrix Disk folder external links | high | Document-link compatibility projection; refresh from Bitrix and validate access rather than trust stored public URL |
| `clients` | `id`, `name`, `inn`, `kpp`, `country`, `source`, `ctime`, `chtime`, `status` | ERP-derived counterparty catalogue | high | Reference import only if pilot behavior needs it; ERP remains source of truth |
| `users` | `id`, name/contact, role/status, auth method/client/password fields | Legacy accounts and actors referenced by audit/photo rows | high | Preserve actor identity mapping for evidence; migrate active access through the 2.0 trusted-user contract, not legacy passwords |
| `users_roles` | `id`, `name`, `status`, `starturl`, `startscreen_id` | Legacy UI roles | high | Compatibility mapping/audit context; replace with explicit 2.0 capabilities |
| `departments`, `users_departments` | department `id/name`; link by `department_id/user_id` | User organisation | high | Reference mapping if required; otherwise historical actor context |
| `users_rightsparts`, `users_rights`, `users_rights2roles`, `users_rightelements` | controller/method permission parts; role and element grants; joins by `rigthpart_id`, `right_id`, `role_id`, `user_id`, `elid` | Legacy authorization | high | Do not migrate as authorization truth; archive for audit and build explicit least-privilege capabilities |
| `logs` | `user_id`, controller, method, `ctime`, request `data`, `uri` | Request logging repeated in controllers | high | Security evidence only after redaction/quarantine; never expose or wholesale import request payloads |
| `fm_views`, `fm_view_fields`, `fm_views_prefilter`, `fm_views_fields_colors` | view definitions; field ordering/read/write role strings; role filters; colour rules | Generic Excel-like presentation/configuration | high | Excluded from migration and runtime; do not import view definitions, presentation rules or formulas |
| `bomselements` | `id` joined from per-user element rights | Unknown legacy element catalogue | medium | Quarantine pending live content/ownership census |
| `optionsvalues` | `option_id` and rows selected by `Specs_model` | Generic option values | medium | Quarantine pending usage/content census |
| `mdm_user_tables_fields`, `mdm_user_table_rights` | metadata table/field IDs and role/table/field rights | Separate generic MDM subsystem | high | Excluded from migration and runtime together with MDM metadata and rights |
| `mdm_user_table_<table_id>` | dynamic physical tables with `id` and `field_<field_id>` columns | Generic MDM data | unknown-live | Excluded from migration; enumerate only if a later product-owner decision names a specific domain dataset for its own SSD slice |
| `fm_declaration_checklist_backfill_runs`, `_values`, `_assignments`, `_created` | run status; snapshots/created-row bookkeeping connected by `run_id`, value/checklist IDs | Reversible repair-operation evidence | high | Keep as migration provenance/quarantine evidence; not canonical domain facts |
| ERP `[1c-erp].[BI_RКонтрагентыID]`, `[1c-erp].[BI_XСтраныМираID]` | names, INN/KPP/country, country join by link | External SQL Server/BI source for `clients` | high | External source of truth; no direct pilot coupling without an owned integration contract |
| ERP `[1c-erp].[BI_DЗаказКлиентаID]`, `BI_DЗаказНаПроизводство2_2ID`, `BI_Sпроф_СрокиХраненияГотовойПродукцииID`, `BI_DЭтапПроизводства2_2ID`, `BI_RСтруктураПредприятияID`, `BI_Eshlz_ТипЗаказаID` (sometimes under `[SHLZ-STAGE]`) | Production-order and stage sources joined in integration SQL | External source feeding `shlz_orders` / `shlz_orders_etaps` | high | External source of truth; document keys, filters, cadence and snapshot semantics before reuse |

Evidence for the central object/checklist joins and mutations: [`../fmonitor/application/controllers/Checklists.php:48`](../../fmonitor/application/controllers/Checklists.php#L48), [`Checklists.php:240`](../../fmonitor/application/controllers/Checklists.php#L240), [`Checklists.php:248`](../../fmonitor/application/controllers/Checklists.php#L248), [`Checklists.php:262`](../../fmonitor/application/controllers/Checklists.php#L262), [`Checklists.php:279`](../../fmonitor/application/controllers/Checklists.php#L279). Installer attribution and its log are written together at [`Checklists.php:269`](../../fmonitor/application/controllers/Checklists.php#L269) and can later be replaced transactionally at [`Checklists.php:348`](../../fmonitor/application/controllers/Checklists.php#L348). Premium calculation explicitly traverses object → checklist values → attribution → checklist shares at [`Integration.php:2186`](../../fmonitor/application/controllers/Integration.php#L2186), [`Integration.php:2287`](../../fmonitor/application/controllers/Integration.php#L2287), and [`Integration.php:2327`](../../fmonitor/application/controllers/Integration.php#L2327).

Evidence for generic metadata and object audit: [`../fmonitor/application/controllers/Fields.php:114`](../../fmonitor/application/controllers/Fields.php#L114), [`Fields.php:134`](../../fmonitor/application/controllers/Fields.php#L134), [`../fmonitor/application/controllers/Tables.php:1045`](../../fmonitor/application/controllers/Tables.php#L1045), [`Tables.php:1126`](../../fmonitor/application/controllers/Tables.php#L1126), [`Tables.php:1135`](../../fmonitor/application/controllers/Tables.php#L1135). View/prefilter/color relations are explicit at [`../fmonitor/application/controllers/Views.php:138`](../../fmonitor/application/controllers/Views.php#L138), [`Views.php:170`](../../fmonitor/application/controllers/Views.php#L170), and [`Views.php:207`](../../fmonitor/application/controllers/Views.php#L207).

Evidence for non-main production data: table screens join `shlz_orders`, `shlz_orders_etaps`, drawing folders and checklist evidence at [`Tables.php:321`](../../fmonitor/application/controllers/Tables.php#L321)–[`Tables.php:340`](../../fmonitor/application/controllers/Tables.php#L340). ERP counterparties are read and copied locally at [`../fmonitor/application/controllers/Integration.php:684`](../../fmonitor/application/controllers/Integration.php#L684). Bitrix folder metadata is persisted at [`Integration.php:599`](../../fmonitor/application/controllers/Integration.php#L599). Production stages are inserted at [`Integration.php:1355`](../../fmonitor/application/controllers/Integration.php#L1355).

The source census establishes **38 fixed local table names plus the dynamic `mdm_user_table_<id>` family**. No SQL view name was found in first-party queries. `users_settings` and the configured CodeIgniter migration-state table are plausible live objects but are not counted because the reviewed runtime query corpus does not directly establish them; the live metadata census must confirm or reject concrete business tables of this kind. The dynamic MDM family and its possible metadata table are recorded as evidence of the excluded platform, not as migration candidates. The backfill tables have checked-in DDL (including primary keys but no declared foreign keys) at [`../fmonitor/application/controllers/Checklist_declaration_backfill.php:402`](../../fmonitor/application/controllers/Checklist_declaration_backfill.php#L402).

Evidence for identity/access tables: [`../fmonitor/application/controllers/Users.php:61`](../../fmonitor/application/controllers/Users.php#L61), [`Users.php:219`](../../fmonitor/application/controllers/Users.php#L219), [`Users.php:251`](../../fmonitor/application/controllers/Users.php#L251), [`Users.php:272`](../../fmonitor/application/controllers/Users.php#L272), and [`../fmonitor/application/libraries/Userman.php:125`](../../fmonitor/application/libraries/Userman.php#L125). Dynamic MDM tables are constructed at [`../fmonitor/application/libraries/Htmlform.php:42`](../../fmonitor/application/libraries/Htmlform.php#L42) and [`../fmonitor/application/libraries/Apicore.php:39`](../../fmonitor/application/libraries/Apicore.php#L39).

## Files, artifacts and integration-only facts

- Checklist photo metadata is relational, but original and 500px derivatives are stored under `/upload/checklistfiles/`; deletion copies only the original to `/upload/checklistfilesdeleted/`, unlinks both active files, then deletes metadata. Therefore neither DB-only nor active-directory-only backup is complete evidence ([`Checklists.php:123`](../../fmonitor/application/controllers/Checklists.php#L123), [`Checklists.php:399`](../../fmonitor/application/controllers/Checklists.php#L399)).
- CSV files are operational inputs outside the DB: `ordermontandstat.csv` updates dates/status/installers, while `installatorsupdate.csv` replaces crews. Their paths and direct ingestion are visible at [`Integration.php:710`](../../fmonitor/application/controllers/Integration.php#L710) and [`Integration.php:992`](../../fmonitor/application/controllers/Integration.php#L992). Treat surviving CSVs as provenance evidence, not canonical current state.
- Bitrix supplies workforce records and drawing-folder links. The workforce refresh truncates the current catalogue before repopulation, so absence after a failed/partial refresh is not a dismissal fact ([`Integration.php:1383`](../../fmonitor/application/controllers/Integration.php#L1383)).
- The legacy source contains embedded Bitrix/Dadata webhook/API credentials and disables TLS peer verification in one Bitrix call. Values are deliberately not reproduced here; rotate/revoke them and move replacements to secret storage ([`../fmonitor/application/controllers/Integration.php:33`](../../fmonitor/application/controllers/Integration.php#L33), [`Integration.php:1362`](../../fmonitor/application/controllers/Integration.php#L1362), [`../fmonitor/application/libraries/Dadata.php:22`](../../fmonitor/application/libraries/Dadata.php#L22)).
- Request logs serialize request data and URI, potentially containing credentials, personal data or imported payloads ([`../fmonitor/application/controllers/Users.php:26`](../../fmonitor/application/controllers/Users.php#L26)). They require restricted access, retention rules and redaction before any transfer.

## Data-quality and semantic hazards

1. **No declared FK evidence.** Application joins establish relationships, but checked-in source does not prove DB constraints, uniqueness, engines or cascades. Orphans and duplicates must be measured live.
2. **Current state overwrites history.** Checklist values toggle in place while a separate log is appended; installer attribution rows are deleted/reinserted. The two streams can disagree ([`Checklists.php:261`](../../fmonitor/application/controllers/Checklists.php#L261), [`Checklists.php:367`](../../fmonitor/application/controllers/Checklists.php#L367)).
3. **Crew cascade contamination.** Current object crew is used to filter historical checklist attribution during premium calculation, explicitly discarding rows for people no longer in the current crew ([`Integration.php:2242`](../../fmonitor/application/controllers/Integration.php#L2242), [`Integration.php:2295`](../../fmonitor/application/controllers/Integration.php#L2295)). This is unsuitable as historical truth.
4. **Workforce refresh destroys snapshot continuity.** `fm_installators` is truncated; only inactive observations are conditionally logged, and one pagination path omits explicit `status` in its log insert ([`Integration.php:1383`](../../fmonitor/application/controllers/Integration.php#L1383), [`Integration.php:1464`](../../fmonitor/application/controllers/Integration.php#L1464)).
5. **Sentinel collision.** `999999`/`000000` represents “not assigned” as if it were an installer row ([`Integration.php:1385`](../../fmonitor/application/controllers/Integration.php#L1385)). It must become absence/reason, not a person.
6. **Implicit natural-key joins.** `zavnumber`/folder `name`/order `number` and person FIO are used as joins; formatting changes can split or merge identities ([`Tables.php:332`](../../fmonitor/application/controllers/Tables.php#L332), [`Integration.php:1720`](../../fmonitor/application/controllers/Integration.php#L1720)).
7. **Runtime formulas and dynamic SQL.** `fm_fields.formula`, dynamic physical columns and user-configured filters are code/data hybrids. They are excluded and must not be imported or executed in 2.0.
8. **Audit may contain temporary/orphan rows.** New-object changes are written with a `temp_mark` before the object ID is known and patched later; failed requests may strand evidence ([`Tables.php:1126`](../../fmonitor/application/controllers/Tables.php#L1126), [`Tables.php:1139`](../../fmonitor/application/controllers/Tables.php#L1139)).
9. **Photo deletion is only partly recoverable.** Deleted original may survive, resized derivative and DB metadata do not; filenames are derived from mutable metadata and extension.
10. **SQL/request construction is frequently interpolated.** This raises injection, malformed-data and audit-integrity risk; imported values require validation, not trust.

## Exact live-DB and storage census required

Run these read-only queries using separately scoped credentials against each legacy schema. Export results as redacted evidence; do not run them from the application account. Use the results to identify concrete business datasets outside `fm_maintable`; dynamic MDM tables and generic custom metadata/view definitions are excluded and require no row-level enumeration or classification for migration.

```sql
SELECT TABLE_SCHEMA, TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_ROWS, CREATE_TIME, UPDATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA IN (DATABASE())
ORDER BY TABLE_NAME;

SELECT TABLE_NAME, ORDINAL_POSITION, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE,
       COLUMN_DEFAULT, COLUMN_KEY, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, COLUMN_NAME;

SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING, ACTION_STATEMENT
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE();

SELECT TABLE_NAME, VIEW_DEFINITION
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = DATABASE();
```

Then execute targeted integrity counts (counts only first, sampled rows only under an approved redaction protocol):

```sql
SELECT 'checklist_value_orphan_object', COUNT(*) FROM fm_install_checklists_values v
LEFT JOIN fm_maintable m ON m.id=v.value_id WHERE m.id IS NULL
UNION ALL SELECT 'checklist_value_orphan_item', COUNT(*) FROM fm_install_checklists_values v
LEFT JOIN fm_install_checklist i ON i.id=v.checklist_id WHERE i.id IS NULL
UNION ALL SELECT 'assignment_orphan_value', COUNT(*) FROM fm_install_checklists_values_installators a
LEFT JOIN fm_install_checklists_values v ON v.id=a.checklist_value_id WHERE v.id IS NULL
UNION ALL SELECT 'photo_orphan_object', COUNT(*) FROM fm_install_checklist_files f
LEFT JOIN fm_maintable m ON m.id=f.dataid WHERE m.id IS NULL;

SELECT tab_id, COUNT(*) n FROM fm_installators GROUP BY tab_id HAVING COUNT(*) > 1;
SELECT regnumber, COUNT(*) n FROM fm_maintable GROUP BY regnumber HAVING COUNT(*) > 1;
SELECT zavnumber, COUNT(*) n FROM fm_maintable GROUP BY zavnumber HAVING COUNT(*) > 1;
SELECT status, COUNT(*) FROM fm_installators GROUP BY status;
SELECT value, COUNT(*) FROM fm_install_checklists_values GROUP BY value;
```

Storage census must separately enumerate active/deleted checklist blobs with relative path, byte size, modification time and SHA-256; compare every DB metadata row with expected original/derivative paths and every filesystem blob with a DB row. Also inventory scheduled jobs, web-server routes, database grants, backup retention, and all integration endpoints because none is recoverable reliably from tables alone.

## Migration decision gates

Before declaring the legacy estate understood:

1. Capture the live metadata census above for concrete business tables, including unreferenced/retired tables and relevant views; exclude generic MDM tables and generic custom metadata/view-builder objects from migration analysis.
2. Produce row-count/orphan/duplicate distributions and date ranges for every candidate history table.
3. Reconcile checklist current values, value logs, current attribution, attribution logs and photo blobs per object.
4. Map every legacy user and installer identifier to a stable 2.0 source key without FIO matching.
5. Classify selected physical `fm_maintable` columns needed by approved domain contracts as canonical import, compatibility projection, historical evidence, ignore or quarantine; do not classify or import every `fm_fields` row.
6. Verify ERP/Bitrix ownership, refresh cadence and failure semantics; rotate exposed credentials before any integration reuse.
7. Specify and test each migration as its own SSD + TDD vertical slice; this audit is discovery evidence, not an executable migration specification.

If the product owner later identifies a specific business dataset currently stored in dynamic MDM, that dataset requires a separate SSD decision. Such a decision does not revive the generic MDM platform: it must define an explicit domain contract and migration into explicit FMonitor 2.0 tables.
