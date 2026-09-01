# Independent identity/access technical Gate 1 review

- Date: `2026-09-02`
- Reviewer: fresh independent agent `identity_gate1_review_20260902ao`
- Executable specification: `IDENTITY-ACCESS-SCHEMA-001 v0.1`
- OpenSpec change: `canonicalize-identity-access-schema`
- Verdict: `CHANGES_REQUESTED`

## Scope and independence

I reviewed the proposed executable specification without editing the
specification, OpenSpec planning, production code, tests or operations status.
The review covers the current authoritative dirty worktree, the reconciled
four-artifact OpenSpec change, exact identity/access schema evidence, the owner
decision for restartable exact-compatible partial recovery, the accepted
catalogue-wide 25/26 prefix reconciliation, and the landed workforce v5
canonical runner.

This record is a technical Gate 1 review only. It does not grant owner approval,
authorize RED, approve implementation or close `GRILL-002`.

## Findings

### 1. Migration version remains implementation-derived after v5 landed — blocking

The current canonical runner map is now literally versions `1..5`, with
`BitrixWorkforceHistorySchemaMigration` registered as v5 and final successful
`schemaVersion=5` in commit `dcdab2d`. Identity/access is the next ordered slice:
checklist-template and inspection-evidence remain explicitly blocked behind it.

Sections 2, 5, 6, 7 and 9 nevertheless expose only the symbolic
`V_identity = V_predecessor + 1` and expressly prohibit fixing `v6`. That made
sense while the predecessor was unlanded, but it no longer supplies an
independently determined literal expected value for the public CLI seam. A RED
test must not discover its expected version from the implementation map it is
testing.

Before approval, the specification must bind this slice to canonical runner
version `6`, with literal `schemaVersion=6`, conflict version `6`, final runner
version `6` and `appliedVersions` expectations. If another migration is
intentionally inserted first, this Gate 1 artifact and its planning must instead
be freshly reconciled and rereviewed before approval; it cannot delegate that
public result to Gate 4 implementation.

### 2. Prefix-baseline prose is stale after the workforce v5 landing — blocking

Section 2 calls the 25-byte composed ceiling a “superseding future” contract and
says the historical 32-byte runner contract still requires future versioned
supersession before this family lands. That supersession has already landed:
the current public runner accepts `0..25` ASCII bytes, rejects 26 before the DB
connection, and v5 is independently complete.

The 25/26 arithmetic itself is correct: the longest identity basename is 28
bytes, all table and explicit FK/index identifiers remain within 64 bytes at
25, and the 39-byte classification-provenance basename remains the
catalogue-wide authority. The executable spec must describe 25/26 as the
current inherited runner contract and retain the historical 32-byte artifact
only as superseded history.

### 3. Partial-recovery reconciliation statement contradicts current OpenSpec — blocking

Section 5 says its restartable policy “supersedes the current OpenSpec
all-or-none partial-conflict prose” and that OpenSpec still must be reconciled
after owner approval. The owner approved the policy on 2026-09-02, all four
OpenSpec artifacts were already coherently updated, strict validation passed,
and the fresh planning review recorded
`READY_FOR_FRESH_GATE1_REVIEW_WHEN_PREDECESSORS_LAND`.

The normative recovery behavior itself is correct, but the executable spec must
state that it agrees with the now-reconciled OpenSpec. Leaving both assertions
in the approval candidate makes the planning authority self-contradictory.

## Accepted technical content

No correction is requested to the following behavior after the three baseline
issues above are fixed:

- ownership is exactly the literal nine-table family, in the documented order;
- all nine column manifests match the captured MariaDB 11.4.7 evidence,
  including enum/default/nullability/AI semantics;
- compatibility compares semantic PK/unique/secondary BTREE and five FK
  relationships while correctly ignoring generated index/FK names and the
  AUTO_INCREMENT counter;
- clean DDL uses validated exact database-default `utf8mb4` collation rather
  than freezing the evidence environment's collation or silently converting an
  existing table;
- one full-family read-only preflight precedes mutation; exact-compatible
  partial recovery creates only missing members in dependency-safe order, and
  interrupted repeat reclassifies the complete family;
- any incompatible member, extra structure, view substitution, cross-prefix FK
  or collation mismatch produces deterministic zero-mutation conflict and
  prevents later migration invocation;
- clean, complete/populated, representative 8/9 and missing-dependency partial,
  relationship conflict, prefix coexistence and 25/26 boundary examples are
  sufficient to drive the full Gate 2 sensitivity matrix;
- migration never seeds or rebuilds, runtime/request paths perform no DDL, and
  intentional destructive disposable bootstrap remains a separate operator
  seam;
- `GRILL-002` is correctly limited to future RBAC authority, authorization and
  audit behavior decisions. This ownership slice may characterize existing
  behavior but cannot approve or alter it.

## Required next step

Correct the executable specification and coherently reconcile any OpenSpec
wording affected by the now-literal v6/current-25 baseline. Then assign a fresh
independent Gate 1 rereviewer. Even after technical readiness, the owner must
explicitly approve the corrected executable specification and mark Gate 1
`APPROVED` before Gate 2 begins.

**RED remains prohibited until that explicit owner approval.**

## Verification

Current-tree results:

- `openspec validate canonicalize-identity-access-schema --strict` — PASS
  (`Change 'canonicalize-identity-access-schema' is valid`);
- `git diff --check` — PASS;
- `make architecture-check` — PASS (`7 rules`).
