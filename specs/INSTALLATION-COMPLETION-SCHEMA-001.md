# INSTALLATION-COMPLETION-SCHEMA-001 — canonical completion schema ownership

Статус: **APPROVED / Gate 1**  
Версия: **v1**  
Дата: **2026-09-02**

## Простыми словами

Canonical migration v10 создаёт и проверяет таблицы ПТО, декларации и их
исправлений до запуска приложения. Ошибка не стирается: новый факт с причиной
ссылается на предыдущую версию, а исходная запись хранится навсегда. Runtime
перестаёт выполнять DDL, поэтому очередь работает под DML-only пользователем.

## 1. Actor, public seam и scope

Actor — deployment operator. Public seam — `bin/fmonitor2-migrate.php`, exit
code и JSON stdout. Runtime-negative seam — ObjectQueue, object card, checklist,
completion POST и Compose bootstrap.

Migration единолично владеет `${prefix}fm2_pilot_completion_facts` и
`${prefix}fm2_pilot_completion_fact_corrections`. Она не реализует correction
command/UI/authorization, не создаёт completion/progress/premium facts и не
добавляет domain logic в `rapid-pilot`.

## 2. Ordering, prefix и database defaults

Runner SHALL применить literal v10 после exact landed v1–v9 catalogue.
Validated prefix MUST соответствовать `[A-Za-z0-9_]{0,25}`; 26-byte,
non-ASCII/invalid input MUST завершиться configuration failure до DB
connection/access: exit `64`, stdout
`{"ok":false,"reason":"CONFIGURATION_INVALID"}\n`, empty stderr, без
раскрытия prefix. Family-local correction basename имеет
37 bytes, но composed 25/26 contract остаётся строже.

До mutation migration SHALL валидировать exact database charset `utf8mb4` и
safe applicable database-default utf8mb4 collation, затем явно применять её к
обеим InnoDB tables. Unknown/non-applicable/non-utf8mb4 default даёт
zero-mutation public runner outcome: exit `70`, stdout
`{"ok":false,"reason":"MIGRATION_FAILED"}\n`, empty stderr.

## 3. Exact final family

Fingerprint SHALL сравнивать ordinal column metadata, types, nullability,
defaults, generated state, character metadata, exact named indexes/order/type/
visibility, constraints, engine, charset и validated table collation через
`information_schema`. Cardinality, row estimate и next AUTO_INCREMENT не входят
в structural fingerprint и MUST сохраняться.

### 3.1 Root facts

Exact root table совпадает с DDL из
`docs/operations/installation-completion-schema-evidence.md`. Extra/missing/
changed column, index, FK, CHECK, engine, charset или collation несовместимы.

### 3.2 Corrections

```sql
CREATE TABLE `${prefix}fm2_pilot_completion_fact_corrections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `root_fact_id` bigint(20) unsigned NOT NULL,
  `version_no` int(10) unsigned NOT NULL,
  `previous_correction_id` bigint(20) unsigned DEFAULT NULL,
  `previous_version_no` int(10) unsigned DEFAULT NULL,
  `fact_date` date NOT NULL,
  `reason` varchar(1000) NOT NULL,
  `recorded_at` varchar(40) NOT NULL,
  `recorded_by_user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_root_version` (`root_fact_id`,`version_no`),
  UNIQUE KEY `uq_previous_correction` (`previous_correction_id`),
  UNIQUE KEY `uq_correction_identity` (`id`,`root_fact_id`,`version_no`),
  KEY `root_history` (`root_fact_id`,`id`),
  CONSTRAINT `fk_completion_correction_root` FOREIGN KEY (`root_fact_id`)
    REFERENCES `${prefix}fm2_pilot_completion_facts` (`id`),
  CONSTRAINT `fk_completion_correction_previous`
    FOREIGN KEY (`previous_correction_id`,`root_fact_id`,`previous_version_no`)
    REFERENCES `${prefix}fm2_pilot_completion_fact_corrections`
      (`id`,`root_fact_id`,`version_no`),
  CONSTRAINT `<server-generated>` CHECK (`version_no` >= 1),
  CONSTRAINT `<server-generated>` CHECK
    ((`version_no` = 1 AND `previous_correction_id` IS NULL AND
      `previous_version_no` IS NULL) OR
     (`version_no` > 1 AND `previous_correction_id` IS NOT NULL AND
      `previous_version_no` = `version_no` - 1)),
  CONSTRAINT `<server-generated>` CHECK
    (char_length(trim(`reason`)) BETWEEN 1 AND 1000)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Correction rows intentionally contain no replacement `details` or arbitrary
payload: only an erroneous date is corrected; root details remain immutable.
CHECK presentation names/whitespace/backticks/outer parentheses are
server-generated and normalized semantically; exactly these three expressions
are allowed, duplicates/extras are conflict. Exactly the two named FKs above
are required; extra/changed FK is conflict. Future command still must
validate/lock admission and stale state at its one public application seam.

## 4. Allowed predecessor and partial states

Before first DDL migration SHALL inspect the whole family. Allowed states:

- both absent: create roots, then corrections;
- exact populated root and corrections absent: preserve roots/rows/next id,
  create empty corrections;
- root absent and corrections present: conflict, because the required root FK
  dependency is broken and reverse partial state is not a valid interrupted
  outcome of deterministic root-first creation;
- both exact: deterministic repeat with no target DDL/DML.

Exact populated corrections with root absent are conflict because referential
meaning cannot be proven. Any incompatible member is conflict before mutation;
missing sibling is not created. Conflicts are binary-sorted exact table names.
No roots/corrections are synthesized, updated, deleted or backfilled.

Conflict runner result: exit `2`, JSON reason `SCHEMA_MIGRATION_CONFLICT`,
`schemaVersion:10`. Success: exit `0`, terminal `schemaVersion:10`, and v10 in
`appliedVersions` only when at least one member was created.

## 5. Acceptance scenarios

### A — clean and repeat

Clean compatible v1–v9 creates exact empty family and reports v10. Exact repeat
returns no applied version and preserves schema, rows and counters byte-exact.

### B — populated predecessor upgrade

Exact root-only predecessor with PTO/declaration Unicode details and next id 3
keeps both rows/bytes/ids/counter and gains an empty corrections table.

### C — correction history constraints

For one root, version 1 requires both previous fields NULL and a bounded
non-blank reason. Version 2 requires the exact same-root version 1 identity. A
second version 1, duplicate direct successor, cross-root predecessor, skipped
ordinal, absent predecessor, zero version, mismatched NULL/version shape or
blank reason is rejected by DB constraints without changing accepted history.

### D — conflict and decoys

Changed/extra/missing metadata on either configured member returns deterministic
conflict with zero configured/decoy mutation. Unprefixed and other-prefix
decoys remain byte-exact.

### E — DML-only runtime

On exact v10 family, ObjectQueue/card/checklist/completion/bootstrap do not issue
CREATE/ALTER/DROP/RENAME/TRUNCATE/repair. Existing observable behavior remains;
ObjectQueue successful read no longer fails because of completion DDL.

### F — missing/drift runtime

Every runtime consumer MUST call one read-only family readiness seam. Missing or
drifted member fails closed before completion DML/domain facts/partial HTML.
Exact outcomes:

- ObjectQueue returns `503`, `Content-Type: text/plain; charset=UTF-8`, no-store
  and body matching `\AService unavailable\. Reference: [0-9a-f]{12}\n\z`;
- authenticated `GET` object card and both checklist HTML routes return `503`,
  `Content-Type: text/plain; charset=UTF-8`, `Retry-After: 60`, no-store and
  exact body `Service unavailable.\n`; equivalent `HEAD` has the same declared
  Content-Length and empty body;
- authorized completion POST returns the same `503` text/Retry-After/no-store
  outcome before completion DML and without redirect;
- JSON checklist operation/sync endpoints do not consume completion schema and
  are outside this family readiness contract;
- bootstrap exits `70` with exact stdout
  `{"ok":false,"reason":"MIGRATION_FAILED"}\n` before ready publication,
  fixture/product DML or secret/schema disclosure.

## 6. Domain meaning, history и concurrency

Root PTO/declaration facts and every correction are immutable and retained
indefinitely. Effective fact is root followed by its single accepted correction
chain. Declaration is mandatory for terminal completion; checklist contributes
85% and effective document facts close the remaining 15%. Migration itself
MUST NOT calculate or persist that projection.

Schema constraints prohibit branch/cross-root/gap storage. They do not decide
which existing version a user may request to correct. Future correction command
MUST define that admission/stale policy, lock/read the root chain, require exact
capability/current authorization and append reason/actor/time in its own
approved executable spec and Gates 1–5.

## 7. Done

Done requires approved exact Gate 1 hash, demonstrated RED, independent test
approval, minimal GREEN without approved-test edits, focused/fresh/full
verification, `make architecture-check`, independent code approval and removal
of completion runtime DDL. Gate 2 разрешён отдельным owner approval reviewed
hash, записанным в
`docs/operations/installation-completion-schema-owner-approval.md`.
