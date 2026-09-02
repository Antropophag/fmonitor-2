# Independent rereview v2: object-detail schema ownership evidence

Reviewed: 2026-09-02  
Reviewer: fresh independent evidence reviewer `object_detail_schema_evidence_rereview_0902f`  
Artifact: `docs/operations/object-detail-schema-evidence.md`  
Prior reviews: `docs/operations/object-detail-schema-evidence-review.md`,
`docs/operations/object-detail-schema-evidence-rereview.md`  
Verdict: **READY_FOR_OPENSPEC_UPDATE**

## Scope checked

The corrected evidence was reread against the current importer, initializer,
Makefile and README surfaces, exact DDL literals and consumer queries, the
generation-prefix guard, classification-provenance basename and both prior
reviews. The literal README command was also executed without its missing
arguments to confirm its first observable failure. No OpenSpec artifact,
executable specification, RED or implementation was changed or started.

## Prior findings resolved

1. **Explicit-charset collation semantics are now stated consistently.** Both
   source DDL statements specify `DEFAULT CHARSET=utf8mb4` and omit `COLLATE`.
   The evidence now describes MariaDB choosing the default collation for that
   explicitly selected character set; it does not call this database-default
   inheritance. The observed `utf8mb4_uca1400_ai_ci` value remains correctly
   limited to the recorded MariaDB 11.4.7 environment and is not presented as a
   portable compatibility fingerprint.

2. **The README command is no longer represented as an executable DDL path.**
   The published invocation supplies neither mandatory `--captured-at` nor
   `--apply`. Direct execution reproduced `CAPTURED_AT_INVALID` with exit 255;
   argument validation occurs before manifest lookup or DB construction. The
   evidence correctly retains it as an intended independent operator surface
   and documentation defect, while distinguishing `make import-production`,
   which reaches the importer through `initialize-native-only.php` with both
   arguments.

## Full evidence confirmation

- The family is exactly the two process-prefixed tables and the only production
  DDL owner for either exact name is
  `rapid-pilot/import-production-object-details.php`.
- The source order, column order/types/nullability, PK-only indexes, lack of
  defaults, FKs, CHECKs, generated columns and auto increment, and explicit
  InnoDB/utf8mb4 clauses match the current literals.
- Dry-run exits after source capture without target DDL or data writes. Apply
  performs both CREATEs before the target transaction, then rechecks the active
  generation under lock and uses hash-only repeat/conflict handling as stated.
- Initializer ordering, direct and indirect execution surfaces, detail-table
  consumers, quarantine counting, and reduced consumer fixtures are accurately
  distinguished. The empty family has no FK or row prerequisite, so canonical
  schema availability before initializer/importer/consumers is an ownership
  requirement rather than an imported-case data dependency.
- The isolated MariaDB observation, normalized and physical hashes, populated
  counters, and cleanup claims are bounded to the recorded environment and
  verifier-owned prefix.
- Basename lengths reproduce as 24, 34 and 39 bytes. The local family ceiling
  is 30 ASCII bytes; the independently discovered catalogue ceiling is 25
  bytes, so this family does not narrow it.
- The partial/incompatible-state matrix follows the two independently committed
  `IF NOT EXISTS` statements. Whole-family preflight, zero-mutation conflict,
  compatible partial completion, row preservation and DDL removal from the
  importer are appropriately framed as an ownership-only target, not accepted
  product semantics or a storage redesign.

## Conclusion

The evidence resolves all exit criteria from both prior reviews and is
sufficiently precise and source-bounded to update the existing OpenSpec planning
package. This verdict authorizes planning refinement only; it does not approve
Gate 1, RED or implementation.
