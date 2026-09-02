# Independent rereview: object-detail schema ownership evidence

Reviewed: 2026-09-02  
Reviewer: fresh independent evidence reviewer `object_detail_schema_evidence_rereview_0902e`  
Artifact: `docs/operations/object-detail-schema-evidence.md`  
Prior review: `docs/operations/object-detail-schema-evidence-review.md`  
Verdict: **CHANGES_REQUESTED**

## Scope checked

The corrected evidence was reread against the current importer, initializer,
Makefile and README execution surfaces, exact consumer queries, classification
provenance basename, generation-prefix guard, and the prior review's four exit
criteria. No OpenSpec artifact, executable specification, RED, or implementation
was changed or started.

## Findings requiring correction

1. **The collation correction is incomplete and internally contradictory.** The
   MariaDB observation still says that both tables used an "inherited table
   collation" and that their character columns "inherited"
   `utf8mb4_uca1400_ai_ci`. These source statements explicitly specify
   `DEFAULT CHARSET=utf8mb4`; with no `COLLATE`, MariaDB resolves the default
   collation of that explicitly selected character set. This is not the same
   inheritance mechanism as DDL that omits both charset and collation. The later
   paragraph now states the correct distinction, but it does not repair the two
   earlier claims. Replace those claims with explicit-charset default-resolution
   wording while retaining `utf8mb4_uca1400_ai_ci` only as the observed value in
   this environment.

2. **The README surface is an intended/documented entrypoint, not a currently
   executable DDL path as written.** The README's direct command supplies neither
   the importer's mandatory `--captured-at` option nor `--apply`. The current
   importer evaluates `canonicalCapture('')` before opening either database, so
   that exact documented command fails with `CAPTURED_AT_INVALID` and cannot
   reach dry-run, DDL, or ingestion. Even if a capture time were added, omission
   of `--apply` would make it dry-run only. Keep the README as an independently
   exposed operator surface and documentation defect relevant to target ordering,
   but do not describe its exact published invocation as a current DDL execution
   surface. Distinguish it from `make import-production`, which invokes
   `initialize-native-only.php` and reaches the importer with both required
   arguments.

## Confirmed corrections and evidence

- Basename lengths are 24, 34 and 39 bytes for details, quarantine and
  classification provenance respectively. The object-detail family ceiling is
  30 ASCII bytes; the independently discovered catalogue ceiling is 25 bytes,
  and this family does not cause that narrowing.
- Current ordering is accurate: the initializer captures checklist-template
  evidence and imports native cases before the detail importer; the importer
  completes target-case paging and its external read-only snapshot before the
  two CREATE statements; template links follow it. The empty family has no FK
  or row dependency, so the target schema migration must precede initializer,
  importer and consumers based on schema predecessors rather than imported rows.
- The Makefile `import-production` target is correctly identified as an indirect
  deployed surface through `initialize-native-only.php`; that initializer is the
  only PHP call site of the importer.
- The exact two-table family, sole DDL owner, source declaration order, columns,
  PK-only indexes, absent defaults/FKs/CHECKs/generated columns/auto increment,
  DDL-before-DML transaction gap, hash-only replay/conflict behavior, lack of
  cross-table exclusion, partial/incompatible-state risks and ownership-only
  target remain consistent with current sources.
- The isolated MariaDB values and hashes remain scoped as environment evidence,
  and the cleanup claim is limited to the verifier-owned prefix.

## Exit criterion

Correct both residual wording/surface issues, then obtain another fresh
independent rereview. The evidence is not yet `READY_FOR_OPENSPEC_UPDATE`.
