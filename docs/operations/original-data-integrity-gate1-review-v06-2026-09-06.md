# ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001 v0.6 — independent Gate 1 rereview

Date: 2026-09-06.

Reviewer task: `/root/selection_v04_readiness`.

Reviewed commit: `0953b6b77f40269916686a7be2770bd314fdd21e`.

Verdict: **APPROVED**.

Reviewer authored neither the specification, parent nor OpenSpec artifacts. All
earlier review records remain immutable. No production implementation or formal
initial/target/fresh RED existed for this branch at review time.

## Exact reviewed hashes

```text
c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd  specs/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001.md
4f5be0695a95fc4d912261647579ad4fb214df5dd0da90edeb6f38fe11f6b3fc  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
fb50243aaa0bda38b53d792cfa623dd8c18d6870e66747a9b22c46c1c3d91af6  docs/operations/original-data-integrity-gate1-review-v05-2026-09-06.md
69567db201cdb258975aff59eba6ac8355ca45784c24319a038e89d04f80d76f  openspec/changes/replace-pilot-registration-with-original-upload/design.md
6f4b33f72f8eec486221c7ef18eebeb54c2a338a7a565ef83a45d2614036e73f  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
8468245129f7305fb50f84177a06c09768ff6d9c7f46f274bdb6c63d0bca800a  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
dee0a7e028235c2c27b58c3c2c3513b501a0f40595dc62fb452bfe40ea1a246a  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

## v0.5 finding disposition

### Unproved post-CAS semantic collision — RESOLVED

After correction AcceptedCommit returns generic CONFLICT, the application keeps
the exact evidence order:

1. validated accepted-fingerprint FOUND selects replay;
2. fingerprint miss proceeds to complete current-lineage reread;
3. changed current selects STALE_REVISION;
4. corrupt, incomplete or contradictory lineage selects persistence failure;
5. unchanged complete valid matching root/current/target with changed date/PDF
   also selects retryable PERSISTENCE_FAILURE.

The last branch no longer invents SEMANTIC_COLLISION when every observable
semantic field matches. A generic uniqueness/adapter collision carries no proven
business cause. Returning persistence failure is exact, non-disclosing and
consistent with the absence of a typed collision owner.

Winner replay and stale revision remain positively proven outcomes rather than
fallbacks. Same-root target disappearance/movement remains corruption and does
not invoke the normal foreign/absent revision-owner query after CAS.

The formal matrix can now distinguish generic unchanged conflict, fingerprint
winner, changed current and malformed lineage without mutable or impossible
fixtures. A nearby direct repository generated-ID/constraint conflict control can
prove zero writes without assigning a business reason.

## Preserved v0.5 conclusions

### Normal parent step 11 — PASS

After stream inspection and fingerprint miss, normal correction resolves
current, target ownership and NO_CHANGES before ID allocation/finalize. Normal
INITIAL performs its explicit assignment-lineage absence check at the same step.
Non-accepted outcomes clean the acquired stage and stream once and create no
finalized content or lease.

Initial NOT_FOUND may proceed and remains distinct from the reachable post-CAS
winner reread. Correction target absent/foreign uses the revision-owner query
only on the normal path after expected-current agreement and current-list miss.

### NO_CHANGES and direct repository defense — PASS

Public NO_CHANGES is selected pre-finalize and writes its required terminal
attempt. A direct no-op correction AcceptedCommit remains repository CONFLICT
with confirmed zero writes as a defense boundary; it is not presented as an
ordinary post-CAS application path.

### Fresh close diagnostic — PASS

The exact event remains
`ASSIGNMENT_ORDER_ORIGINAL_FRESH_READER_CLOSE_FAILED` with phase-only supplied
safeFields. The existing opened owner supplies the single correlationId envelope.
Best-effort logging cannot change the selected recovery result or retry close.

### Cumulative integrity package — PASS

All previously approved cumulative rules remain unchanged:

- stored INVALID_COMMAND is invalid backing;
- denial original-audit presence imposes no upper cardinality or writer policy;
- closed result snapshots and complete lineage validation;
- historical accepted request evidence does not track the later root current;
- exact composition recomputation and consistent MariaDB read snapshots;
- authoritative composition change/disappearance/invalidity yields confirmed
  rollback and persistence failure;
- pre-SQL commit DTO validation and native confirmed/unknown distinctions;
- explicit fresh-reader dependency, one new connection, degraded versus
  recovery-ready factory construction and no writer reuse;
- separate application/storage clocks, authorized lazy epoch handling and worker
  safe-log-first ordering;
- public persistence observer phases and synthetic-only deterministic Gate 2
  ownership.

No deferred denial policy, maintenance behavior, schema version, capability,
selection, renderer, HTTP or launch behavior is selected.

## Parent and OpenSpec coherence

Parent v71 references the same cumulative package and leaves all delivery gates
open. Proposal, design and delta state the exact v0.6 rule: unchanged matching
metadata after a generic conflict is persistence failure, while winner and stale
require positive reread evidence. Tasks remain unchanged because the cumulative
RED/Gate 3/GREEN/Gate 5 sequence was already pending.

Strict OpenSpec validation was reported passing for these exact bytes. Planning
coherence does not substitute for the independent RED and later reviews.

## Findings and disposition

No blocking correctness, precedence, outcome-proof, constructibility, deferred-
policy or test-observability finding remains.

`ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001` v0.6 satisfies Gate 1 at exact
SHA256 `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
and is **APPROVED** for formal cumulative initial/target/fresh and remaining
public-port/real-adapter RED capture. Every test artifact requires independent
Gate 3 before minimal GREEN. This approval does not approve tests, implementation
or combined command readiness by implication.
