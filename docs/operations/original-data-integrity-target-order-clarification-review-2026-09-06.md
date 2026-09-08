# DATA-INTEGRITY v0.4 — target ownership execution-order clarification

Date: 2026-09-06.

Reviewer task: `/root/selection_v04_readiness`.

Reviewed HEAD: `110b05489091b4fb062f6786c77c078e9b0ec42e`.

Verdict: **MATERIAL AMBIGUITY; PIN v0.5 BEFORE TARGET-LINEAGE RED**.

This is a bounded read-only follow-up. It does not edit DATA-INTEGRITY v0.4 or
its approved Gate 1 record. Result, composition and scalar test drafting under
unaffected sections may continue.

## Exact reviewed hashes

```text
e232bbdfb01c1671ff57e6f353c5b5b1b77c2b60680627b9be37299a2ff27e29  specs/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001.md
5274f339b6c73890438297135b739319d30bf787c781c89db7135079df616969  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332  specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001.md
9dbf8f4182d5802c647b6df5421d08ee979806168d52e4a56099833862b2ab86  docs/operations/original-data-integrity-gate1-review-v04-2026-09-06.md
```

## Parent execution order

The parent fixes one literal order:

```text
8  acquire and validate stream
9  inspect PDF
10 accepted fingerprint lookup
11 initial/lineage/current/target/no-change checks
12 private finalize
13 repository commit/CAS
```

COMMAND-LIFECYCLE preserves that order. On a post-stream fingerprint miss, the
application still owns an acquired stage and open stream. Any step-11 terminal or
technical outcome must run normal non-accepted cleanup. No content lease exists
because finalize has not run.

Current production source performs only an early root/composition lineage check
before stream and leaves current/target/no-change checks inside CommitProtocol
after finalize. That is the inherited defect the next implementation must
correct; it is not the oracle for test placement.

## Normal correction path

The exact split should be:

1. Before stream, validate the requested root's complete lineage ownership and
   immutable composition against the command composition. Root absent or valid
   foreign ownership selects SEMANTIC_COLLISION; protocol failure selects
   persistence failure.
2. Acquire/inspect the PDF and perform the accepted fingerprint lookup. FOUND
   replay wins before target/current checks, preserving parent precedence.
3. On fingerprint NOT_FOUND, perform step-11 current/target/no-change checks
   against a fresh complete current-root snapshot before IDs/finalize.

At normal step 11:

- current differs from expected → STALE_REVISION, no target-owner query;
- current equals expected and target is a member but not current →
  TARGET_NOT_CURRENT, no target-owner query;
- current equals expected and target equals current → evaluate NO_CHANGES, then
  continue only for a real correction;
- target absent from the complete current-root list → exactly one revision-owner
  query and the v0.4 NOT_FOUND/foreign/malformed mappings.

TARGET_NOT_FOUND, TARGET_NOT_CURRENT, SEMANTIC_COLLISION, STALE_REVISION and
NO_CHANGES selected here occur after stream/fingerprint but before finalize.
Expected resource observations are:

```text
stage abort once
stage close once
stream close once
finalize zero
lease zero
accepted commit zero
terminal attempt commit once for the selected nonretryable result
```

Cleanup observer/diagnostic details remain governed by COMMAND-LIFECYCLE.

## Post-CAS reclassification reachability

A candidate reaches repository commit only after step 11 proved:

```text
expected current == actual current
target is in the complete root list
target == actual current
requested date/PDF is not NO_CHANGES
```

History is append-only. After repository CONFLICT:

1. Accepted-fingerprint FOUND may prove an identical winner and select replay.
2. Otherwise reread the complete current root.
3. If current changed from expected, select STALE_REVISION.
4. If current remains expected, the previously validated target must still be
   that current member. Its disappearance from the same root is contradictory
   persistence data, not a newly absent or foreign target.

Therefore a normal post-CAS branch cannot reach TARGET_NOT_FOUND or foreign-target
SEMANTIC_COLLISION through `findLineageForRevision`. Forcing it requires a mutable
fixture that removes/moves an immutable revision or returns mutually inconsistent
complete snapshots. Such a fixture must select persistence failure and must not
be used as a business-outcome oracle.

DATA-INTEGRITY v0.4 currently says to apply the identical target-owner helper in
normal and post-CAS paths. That statement conflicts with the candidate admission
invariants and would require impossible or corrupt evidence to cover.

## NO_CHANGES distinction

Public application NO_CHANGES belongs to normal step 11 and occurs before
finalize/commit. A direct MariaDB `AcceptedCommit` whose correction equals current
date/PDF still requires defensive repository rejection with confirmed CONFLICT,
because repository validation must not insert a no-op revision.

That direct repository defense does not imply a normal public application should
reach commit and then use post-CAS reread to discover NO_CHANGES. The v0.5 text
should distinguish:

- public pre-finalize NO_CHANGES selection;
- direct repository no-op DTO returns CONFLICT with zero writes;
- any defensive application reclassification of that repository result, if kept,
  without claiming it is an ordinary CAS-race branch.

## Required reachable matrix

Normal public path after fingerprint miss:

- expected stale: no target-owner call, cleanup, no finalize;
- historical target in current root: TARGET_NOT_CURRENT, no owner call;
- current target with unchanged date/PDF: NO_CHANGES, no owner call;
- target absent globally: one owner NOT_FOUND → TARGET_NOT_FOUND;
- target in a different complete root: one owner FOUND → SEMANTIC_COLLISION;
- owner unavailable/base-only/malformed/same-root contradiction → persistence
  failure;
- valid changed current target → continues to ID/finalize/commit.

Post-CAS path:

- fingerprint winner → replay;
- current changed → stale;
- current unchanged with intact target → resolve only the actual remaining
  root/current/uniqueness conflict specified by the repository contract;
- current unchanged but target missing/moved → persistence failure, zero
  revision-owner query as a semantic resolver.

Direct repository tests separately cover authoritative composition rollback and
no-op correction CONFLICT with zero writes. They must not replace public resource
order assertions.

## Disposition

The issue is technical and follows existing parent precedence and append-only
history. No product decision is required. Amend DATA-INTEGRITY v0.5 to place
normal target-owner resolution at step 11 before finalize and remove the
unreachable identical post-CAS target helper requirement. Obtain fresh Gate 1
before capturing target-lineage RED. Other already authorized result/composition
test work is unaffected.
