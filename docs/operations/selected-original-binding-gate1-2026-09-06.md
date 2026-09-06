# Independent Gate 1 review — ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification or OpenSpec artifacts)
- Reviewed commit: `73b05d157f132716553e91f9b6c4feae3e584a91`
- Executable spec SHA-256: `81c1c686d20075345564451626063a60741393ed039592b2aa674cb41d80aaf4`
- OpenSpec design SHA-256: `d0bd417801d0f4a3cdd6b79442e514e907e861566b68a3d17be19681d9b884bd`
- OpenSpec proposal SHA-256: `6b70296bd7e1bc9b42a8ac7bc4db47d42495afcf7c3431edbaf2980251afc431`
- OpenSpec delta-spec SHA-256: `bedb34526047711ede9de9d9e1a6b18a2c5672d15fb0288f5c3d0f9d2d34245f`
- OpenSpec tasks SHA-256: `177ef4e7bf89eb43395d0fe14b0c90355ac8b273fef14939ead1a4b82ef5c449`
- OpenSpec metadata SHA-256: `0d815ffdda0fec4cf9c7b2346a209fdec1bcde4f92cfb10c2075e2e381fd16f8`
- Review date: 2026-09-06

## Findings

No blocking ambiguity or contradiction was found.

The two new constructors are explicit opt-in paths and assemble the existing `submitAssignmentOrderOriginal` owner with one selected-composition policy/repository. Production and verification differ only by the approved clock and persistence observer inputs; authorization, PDF inspection, private storage, original repository, audit, and selection source remain real. Existing physical-only constructors do not switch by schema presence, and the fresh terminal reader remains mandatory.

The lookup protocol is closed and observable. `NOT_CURRENT` has an empty composition payload and maps to the existing nonretryable `conflict/target_not_current`; `NOT_FOUND` remains `rejected/order_not_found`, and malformed or unavailable source/root state remains `failed/persistence_failure`. Matching terminal replay keeps its established precedence before composition lookup. Historical reading remains independent of write-target currentness.

The locked protocol closes the material race. Original initial acceptance locks the exact installation-case row used by selection, rereads registered source and currentness inside the same owned transaction, and compares the locked composition/hash with the preflight commit payload. A replacement that wins first yields confirmed rollback and internal `COMPOSITION_NOT_CURRENT`, then the existing target-not-current terminal request and audit after resource release. An original that wins first causes later replacement to observe the accepted root and return `original_already_accepted`. Unknown commit or rollback acknowledgement never uses the new status as a guess and retains existing fresh recovery.

Correction policy is consistent with append-only history: a non-latest selection is writable only when the exact case/order/composition has a fully validated accepted original lineage. This permits correction of accepted order 81 after a new pending 82 while forbidding an initial upload to replaced unsigned 81. The common source validators, one snapshot, and shared case lock avoid fallback, nested transactions, or crossed histories.

No new persisted status, reason, table, or migration version is required. `target_not_current` already belongs to the original result/audit contract; the two added statuses are internal control values. Selection grants do not confer original upload/correction authority. Original resource, lease, orphan, audit, safe-log-first configuration, and private-file behavior remain inherited.

The executable spec and OpenSpec proposal, design, delta spec, and tasks agree on scope and sequencing. They do not require legacy writer conversion, template storage, HTTP, composition application, or opening, and do not reopen the approved original, native selection, registry, schema, or registered-reader bytes.

## Gate decision

Gate 1 is **APPROVED** for `ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001` at the exact commit and hashes above. Gate 2 may author the public selected-composition original RED and the bounded stale-target/race cases specified in section 5. Production implementation remains unauthorized until demonstrated RED and independent Gate 3 approval.
