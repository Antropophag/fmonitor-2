# Independent review: object-detail schema ownership evidence

Reviewed: 2026-09-02  
Reviewer: fresh independent evidence reviewer `object_detail_schema_evidence_review_0902d`  
Artifact: `docs/operations/object-detail-schema-evidence.md`  
Verdict: **CHANGES_REQUESTED**

## Scope checked

The evidence was checked against the current top-level importer,
`initialize-native-only.php`, `ObjectDetails.php`, native premium and OTIZ
readers, focused/static and reduced-schema verifiers, the corrected runtime-DDL
plan, and MariaDB semantics for an explicit `DEFAULT CHARSET=utf8mb4` without an
explicit `COLLATE`. No RED or implementation was started.

## Findings requiring correction

1. **The catalogue-wide prefix contract is stale.** The evidence still says
   that the future repository ceiling is 28 bytes and that 28 bytes is
   sufficient. The current runtime plan records
   `fm2_migration_classification_provenance` (39-byte basename) as the catalogue
   maximum and therefore requires a **25-byte ASCII prefix ceiling**. Keep the
   object-detail-local calculations (40/30 bytes and family ceiling 30), but
   replace every composed-runner recommendation with 25 bytes and explain that
   this family does not itself cause the narrowing.

2. **Collation provenance is conflated.** The source statements explicitly name
   `utf8mb4`; when `COLLATE` is omitted MariaDB chooses the default collation for
   that explicitly selected character set. That is distinct from inheriting the
   database default used by DDL that omits both charset and collation. The one
   11.4.7 observation happened to produce
   `utf8mb4_uca1400_ai_ci`, but because the recorded database collation had the
   same value it does not distinguish those two mechanisms. Replace “inherited
   table collation” and the target instruction to validate the
   “database-default `utf8mb4` collation” with the explicit-utf8mb4 default
   semantics; retain the observed value as environment-derived evidence, not a
   portable fingerprint.

3. **Schema availability timing and the proposed dependency are inaccurate.**
   The current initializer creates checklist-template evidence and imports all
   native cases first, then invokes the detail importer. That importer completes
   target-case paging and the entire external-source snapshot before issuing
   either CREATE; only afterwards does initialization link cases to the
   checklist template. Thus the family is absent through earlier initialization
   steps and becomes available late, after external-source access. However, the
   schema has no FK or data dependency on imported case rows. A canonical
   data-free migration must be available before the initializer/importer and
   consumers; it should be ordered after landed *schema prerequisites*, not
   described as waiting until imported installation-case identity is available.
   State both current timing and target timing explicitly.

4. **The execution-surface wording is too narrow.** It is accurate that
   `initialize-native-only.php` is the only PHP call site invoking the importer,
   but the deployed `make native-only-init` path invokes that initializer and
   `rapid-pilot/README.md` documents direct operator execution of the importer.
   Record these as indirect/deployed and direct/manual execution surfaces while
   preserving the conclusion that neither is a second DDL owner.

## Confirmed evidence

- The family is exactly the two named process-prefixed tables, and the only
  production DDL literals for them are the two consecutive top-level CREATEs in
  `import-production-object-details.php`.
- Column order/types/nullability, PK-only indexes, absence of defaults,
  generated columns, FKs, CHECKs and auto increment, and the 34-byte longest
  family basename match the current importer.
- DDL precedes the target DML transaction; exact-hash replay/conflict behavior,
  lack of database-enforced cross-table exclusivity, and preservation risks for
  partial/incompatible `IF NOT EXISTS` states are accurately described.
- `ObjectDetails`, `NativeOperationalPremiumInputs`, and `Otiz` read only the
  detail table. Quarantine has no product read consumer; the generation verifier
  only counts it. Reduced consumer fixtures do not prove exact importer-schema
  compatibility.
- The isolated MariaDB values and hashes are appropriately labelled as an
  observation, and cleanup evidence is scoped to the verifier-owned prefix.

## Exit criterion

After the four corrections above, obtain a new independent evidence rereview.
If it confirms the 25-byte catalogue contract, split collation semantics,
current/target availability timing and complete execution surfaces, the
evidence is suitable for updating the existing OpenSpec package. Until then it
is not `READY_FOR_OPENSPEC_UPDATE`.
