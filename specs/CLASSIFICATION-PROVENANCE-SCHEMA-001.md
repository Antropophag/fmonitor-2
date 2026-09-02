# CLASSIFICATION-PROVENANCE-SCHEMA-001 — canonical classification provenance ownership

Статус: **DRAFT / Gate 1**  
Версия: **v2 (GRILL-009 amendment)**  
Дата: **2026-09-02**

## Простыми словами

Canonical migration v11 заранее создаёт и строго проверяет таблицу, которая
связывает результат migration/import с классификацией исходного объекта.
Runtime больше не создаёт её сам: отсутствие или drift останавливают import до
source fetch/output. Уже сохранённые proofs остаются неизменными. Этот slice не
утверждает taxonomy и не исправляет отдельное окно «output создан, provenance
write упал».

## 1. Actor, public seam и scope

Actor — deployment operator. Public migration seam —
`bin/fmonitor2-migrate.php`, exit code и JSON stdout. Runtime-negative seams —
native operational import, historical snapshot import и literal
`active_baseline` provenance reconcile.

Migration единолично владеет
`${prefix}fm2_migration_classification_provenance`. Optional
`${prefix}fm2_legacy_active_baselines` и `${prefix}fm2_active_case_provenance`
не входят в family и MUST NOT создаваться/проверяться этой migration.

Observed output kinds `operational_case`, `historical_snapshot` и
`active_baseline` являются PILOT_ONLY compatibility literals. Slice не меняет
classification taxonomy, categories/reasons/hashes, routing/admission,
transaction ordering, optional legacy-active cutover или domain process facts.

## 2. Ordering, prefix и database defaults

Runner SHALL применить literal v11 после exact landed v1–v10 catalogue.
Validated process prefix MUST соответствовать `[A-Za-z0-9_]{0,25}`; 26-byte,
invalid или non-ASCII input MUST завершиться configuration failure до database
connection/access без раскрытия prefix/derived identifiers.

До mutation migration SHALL валидировать database charset `utf8mb4` и safe
applicable database-default utf8mb4 collation тем же canonical policy, что v10,
и явно применять её к InnoDB table. Unknown/non-applicable/non-utf8mb4 default
даёт exit `70`, stdout `{"ok":false,"reason":"MIGRATION_FAILED"}\n`, empty
stderr и zero mutation.

## 3. Exact table manifest

```sql
CREATE TABLE `${prefix}fm2_migration_classification_provenance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `output_kind` varchar(40) NOT NULL,
  `legacy_object_id` bigint(20) unsigned NOT NULL,
  `output_id` bigint(20) unsigned NOT NULL,
  `source_cutoff_at` datetime NOT NULL,
  `classification_version` varchar(80) NOT NULL,
  `category` varchar(40) NOT NULL,
  `reason_codes_json` text NOT NULL,
  `classification_sha256` char(64) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `<presentation-name>` (`output_kind`,`output_id`),
  KEY `<presentation-name>` (`legacy_object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Migration SHALL emit validated explicit database-default collation. All
columns are ordered exactly as above, NOT NULL and have SQL NULL metadata
default; only `id` has `auto_increment`. Character columns use exact utf8mb4/
validated table collation. `reason_codes_json` is plain `TEXT`, not JSON and
has no CHECK. Table has exactly primary `id`, one unique ordered
`(output_kind,output_id)` and one nonunique `(legacy_object_id)` visible
full-column ascending BTREE. Index names are non-normative presentation; extra,
missing, duplicate, prefix/subpart, descending/invisible или changed index
conflict. FK/CHECK/other constraints absent. Engine/collation/charset exact;
AUTO_INCREMENT next value and cardinality are data state, not fingerprint.

## 4. Preflight, migration result и preservation

Before first target mutation migration SHALL classify configured table as:

- absent — create exact empty table;
- exact — no target DDL/DML;
- conflict — stop before any mutation.

Conflict returns exit `2` and
`{"ok":false,"reason":"SCHEMA_MIGRATION_CONFLICT","schemaVersion":11}\n`.
Conflicting table inventory uses exact prefixed name. Decoys in unprefixed/
other prefixes remain byte-equivalent.

Clean success returns terminal `schemaVersion=11` and appends 11 to
`appliedVersions`. Exact repeat returns `appliedVersions=[]`. Populated exact
repeat MUST preserve ordered rows byte-equivalent, ids, Unicode/plain TEXT JSON,
timestamps, hashes and next AUTO_INCREMENT. No backfill, UPDATE, DELETE,
synthetic provenance or taxonomy normalization is permitted.

The exact winner/loser outcome is limited to two verifier-composed real
subprocesses starting from the same exact populated v1–v10 predecessor and
prefix with target table absent. Each injected coordinator barrier SHALL report
arrival only after that process has completed semantic preflight and observed
the v11 target absent, and SHALL block it immediately before its plain
`CREATE TABLE`. The verifier SHALL observe both arrivals before simultaneously
releasing both processes. Exactly one winner SHALL return exit `0`, terminal
`schemaVersion=11` and `appliedVersions=[11]`. The process whose plain `CREATE`
loses SHALL return exit `70`, stdout
`{"ok":false,"reason":"MIGRATION_FAILED"}\n` and empty stderr. Final target
SHALL be one exact empty table; predecessor schema/data, target rows and decoys
remain unchanged.

Production CLI, catalogue and factory SHALL always compose a no-op barrier and
SHALL expose no argv, environment or supported configuration path that can
enable the verifier coordinator. Production remains ordinary semantic
preflight immediately followed by plain `CREATE TABLE`; it MUST NOT use
`GET_LOCK`, `SLEEP`, a durable or ephemeral migration ledger, advisory locking,
artificial delay or other hidden cross-runner serialization.

A production runner that reaches preflight after another runner has already
created the exact table is an ordinary exact repeat: it SHALL return exit `0`,
terminal `schemaVersion=11` and `appliedVersions=[]`, without entering the
verifier loser contract. The repeat after the verifier-controlled race has the
same result. Thus exit `70` is required only after an injected-barrier process
previously observed absent, was released to execute plain `CREATE`, and lost
that exact DDL race. This bounded verifier outcome introduces neither a
migration ledger nor a general cross-runner serialization contract.

## 5. Runtime no-DDL precondition

Each native operational, historical snapshot and active-baseline provenance
adapter MUST perform one exact read-only schema precondition before source fetch
and before output mutation. Runtime MUST NOT execute CREATE/ALTER/DROP/repair.

### 5.1 Exact family

Under a principal with required SELECT/INSERT but no DDL privilege:

- append of each literal output kind writes one immutable row;
- exact replay returns same provenance identity with zero new rows;
- same output identity with changed object/cutoff/hash fails
  `PROVENANCE_CONFLICT` without mutation;
- DDL-denied status alone does not cause failure on exact schema.

Coverage of `active_baseline` is limited to this provenance reconcile seam and
MUST NOT create/approve optional baseline/active-case tables or cutover.

### 5.2 Missing или drifted table

Missing/incompatible exact precondition SHALL fail before source connection/fetch,
canonical case/history/baseline output mutation, provenance DML or partial ready
publication. Exact public CLI outcomes:

- `batch-import-native-candidates.php --apply`: exit `2`, stdout
  `{"ok":false,"reason":"NATIVE_BATCH_UNAVAILABLE"}\n`, empty stderr;
- `batch-import-legacy-history.php --apply`: exit `2`, stdout
  `{"ok":false,"reason":"HISTORY_BATCH_UNAVAILABLE"}\n`, empty stderr;
- `batch-import-legacy-active.php --apply`: exit `2`, stdout
  `{"ok":false,"reason":"ACTIVE_BATCH_UNAVAILABLE"}\n`, empty stderr.

Gate 2 SHALL use independent source connection/query sentinels for each command
and prove zero source access, zero output/provenance DML, zero schema repair and
zero ready publication. Output MUST NOT contain credentials, SQL, prefix/table/
classification literals or source payload. Target schema/data/counters and
ambient decoys remain byte-equivalent.

## 6. Bounded PILOT_ONLY non-atomic contrast

Current native operational orchestration may create one canonical
`operational_case` and then fail provenance DML for a reason other than schema
readiness. Gate 2 SHALL use one literal eligible native object, prove the case
did not exist before the invocation, inject a conflicting pre-existing
`(output_kind='operational_case', output_id=<new case id>)` proof after readiness,
and observe:

```text
PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE
```

The transcript MUST prove exactly one canonical case was newly created in that
run, no matching provenance row for its expected object/cutoff/hash was appended,
the injected conflicting row is unchanged, and the public native CLI returns
exit `2`, stdout `{"ok":false,"reason":"NATIVE_BATCH_UNAVAILABLE"}\n`, empty
stderr. Historical and active-baseline output-without-provenance windows are
outside this required contrast. This is
characterization, not accepted target semantics. Migration/readiness MUST close
only schema-caused pre-output failures; atomic output+provenance redesign
requires a separate approved behavior slice.

## 7. Audit, security, idempotency и Done

Migration creates no product/audit facts or durable migration-ledger row;
`schemaVersion`/`appliedVersions` are per-run observations only;
runtime rows remain immutable provenance history. Diagnostics MUST be redacted.
Migration repeat and runtime replay are deterministic. No new domain logic may
enter `rapid-pilot`; it remains adapter/oracle.

Done requires explicit owner approval of this amended exact Gate 1 hash, demonstrated RED,
independent test approval, minimal GREEN, DDL-denied three-kind runtime proof,
focused/full/fresh verification, architecture ratchet with removed runtime DDL,
and independent code approval. Pre-amendment Gate 3 approval does not apply;
this DRAFT does not authorize replacement Gate 2 before fresh review and owner
approval of the amended exact hash.
