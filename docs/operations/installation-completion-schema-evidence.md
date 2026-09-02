# Installation-completion schema ownership evidence

Date: 2026-09-02. This is source/schema evidence, not test approval or
authorization for implementation.

## Current owner and reachability

`RapidPilotCompletionFlow::ensureSchema()` owns one request-reachable statement
for `${prefix}fm2_pilot_completion_facts`. It is called by completion POST,
object-card and checklist enhancement, and by `ensureQueueSchema()` before
ObjectQueue reads. ObjectQueue then also invokes `ChecklistSync::ensureSchema()`;
that second call is already read-only after canonical inspection-evidence v8.
The remaining DML-only 503 is therefore completion-family `CREATE TABLE IF NOT
EXISTS`, not planning v9 or inspection-evidence ownership.

Current root table source shape:

```sql
CREATE TABLE `${prefix}fm2_pilot_completion_facts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `installation_case_id` bigint(20) unsigned NOT NULL,
  `fact_type` enum('pto_act','declaration') NOT NULL,
  `fact_date` date NOT NULL,
  `details` varchar(500) NOT NULL DEFAULT '',
  `recorded_at` varchar(40) NOT NULL,
  `recorded_by_user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_case_fact` (`installation_case_id`,`fact_type`),
  KEY `installation_case_id` (`installation_case_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

The runtime statement has no explicit collation, compatibility preflight,
migration ledger or correction representation. `IF NOT EXISTS` accepts an
occupied incompatible name and still requires DDL authority on runtime paths.

## Approved semantic constraint

`docs/operations/installation-completion-owner-decision.md` requires immutable
original PTO/declaration facts, append-only correction with a mandatory reason,
declaration for terminal completion, and target 85/15 progress. The smallest
lossless upgrade keeps the root table exact and adds one correction ledger;
there is no UPDATE/DELETE/backfill of roots.

Proposed correction stores only the corrected date and mandatory reason. The
owner did not approve correction of declaration details or arbitrary payload;
those fields remain immutable and outside this slice.

Proposed correction basename is
`fm2_pilot_completion_fact_corrections` (37 ASCII bytes). Its exact columns and
indexes are specified in `specs/INSTALLATION-COMPLETION-SCHEMA-001.md`.

## Ordering and prefix

Landed catalogue is v1–v9, with inspection planning literal v9. This family is
candidate v10. Its 37-byte basename allows a family-local 27-byte prefix, but
the composed catalogue already contains the 39-byte classification-provenance
basename, so public runner support remains `[A-Za-z0-9_]{0,25}`; 26 bytes must
still fail before DB access.

The root table is created/validated before corrections. MariaDB DDL commits per
statement, so exact root-only partial state is recoverable; any incompatible
existing member must stop the family before the missing sibling is created.

## Correction-chain constraint evidence

Root is version 0. Correction rows carry `version_no >= 1`,
`previous_correction_id` and `previous_version_no` (both NULL only for version
1). A composite self-FK to `(id,root_fact_id,version_no)` proves same-root
adjacency; CHECK proves `previous_version_no = version_no - 1`; UNIQUE root
ordinal and previous id prevent gaps/branches. These are storage integrity, not
approval of who may correct or whether a command may target a non-current
version. No correction command is implemented by this ownership slice.

## Required verification inventory

Gate 2 must use test-owned literals and prove clean, repeat, populated root-only
upgrade (the only compatible partial), reverse-partial conflict, every other
conflicting member, collation,
prefix 25/26 before access, decoy isolation, row/auto-increment preservation,
reason/version/branch constraints, and DDL-denied ObjectQueue/card/checklist/
completion/bootstrap runtime. Existing completion characterizations remain an
oracle only and do not authorize authenticated-only target commands.
