# CLASSIFICATION-PROVENANCE-SCHEMA-001 — independent Gate 1 rereview

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or edit the executable specification, OpenSpec
artifacts, evidence, production or tasks. This rereview supersedes the first
review only where closure is explicitly confirmed below. Gate 2 remains
unauthorized.

## Reviewed hashes

```text
a044645fac8c347e98ae876f1dfdb98c12944a1c4fde85a098f99b6a84be71ed  specs/CLASSIFICATION-PROVENANCE-SCHEMA-001.md
aa69137e8cc5a59fe2462df9d751a01b2d487499867258a26ceff00ae2c772e7  docs/operations/classification-provenance-schema-gate1-review.md
2ac5c80b7a10f2ccae1ec7ef7ab2ca438d310ef3933187c6b710fd7faa864436  openspec/changes/canonicalize-classification-provenance-schema/proposal.md
2711fe9998f8e88b6ae2b60b1c838d97296cebced9b23de7d8c0195d6bde3005  openspec/changes/canonicalize-classification-provenance-schema/design.md
e97fc2d539d94495a1ba0240a41f82f7c6c68558f0a23611f65fd82c795e6d41  openspec/changes/canonicalize-classification-provenance-schema/specs/persistence/classification-provenance-schema/spec.md
2e31c24072a9a54e10e08d2d619690237763baced5efe8f34c323d614c31a265  openspec/changes/canonicalize-classification-provenance-schema/tasks.md
7eb5f2c8936fc4a75aa8708130eab7ffccb74fa6a4895802e64bd36d2cf2bfd5  docs/operations/active-baseline-provenance-schema-evidence.md
df1c58fa0437fc51868db2e91bcba35793d07957f6359aa36407552e8a17319d  bin/fmonitor2-migrate.php
```

## Executable specification closure

The corrected executable specification itself closes all four prior findings:

- it now explicitly says no product/audit fact or durable migration-ledger row
  exists and `schemaVersion`/`appliedVersions` are per-run observations;
- the race is bounded to the same exact v1-v10 predecessor with only v11 absent,
  fixes winner exit 0 / `[11]`, loser redacted exit 70, exact final empty table,
  unchanged predecessor/decoys and a successful subsequent repeat;
- native, history and active public CLIs each have exact exit 2, one-line generic
  reason JSON and empty stderr for missing/drift; independent connection/query
  sentinels must prove zero source access, while complete snapshots prove zero
  output/provenance/schema/ambient mutation and redaction;
- the mandatory non-atomic contrast is now exactly native operational import:
  a literal eligible object has no pre-existing case, one case is newly created,
  an injected conflicting operational-case proof remains unchanged, no expected
  provenance is appended, and the exact native CLI failure is classified only
  as `PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE`. Historical/active variants are
  explicitly outside this required contrast.

These outcomes are feasible against the current CLIs/runner. The race loser may
remain a generic migration failure after losing CREATE while final/repeat state
proves compatibility; no unsupported general runner serialization is claimed.
The active missing/drift test can preflight classification provenance at the
public batch boundary without promoting its optional output tables, while exact
DDL-denied storage compatibility remains bounded to reconcile.

## Remaining blocker — OpenSpec artifacts contradict the corrected spec

The OpenSpec hashes are unchanged from the first review and still contain the
requirements that the executable spec corrected:

1. Delta `Clean migration` still says the runner “records exactly one
   migration-ledger version”, contradicting executable §7 and the real runner.
2. Delta `Runtime without schema` still provides no exact three-CLI
   exit/stdout/stderr matrix or source-access sentinel.
3. Delta `PILOT_ONLY output-without-provenance remains observable` still says
   native case, historical snapshot or active baseline, rather than the now
   mandatory native-only contrast with its exact barrier/result facts.
4. Design decision 2 still says concurrency preserves “a single ledger
   version”; design decision 6 and the migration plan do not carry the corrected
   bounded race and exact public failure contracts.

OpenSpec is lifecycle planning rather than a replacement for the executable
spec, but its delta requirements cannot assert behavior the owner is being asked
to reject in the controlling spec. The change package must be internally
coherent before owner approval and before tests derive expectations from it.

Update proposal/design/delta/tasks as needed to mirror the corrected executable
contract: no durable ledger, bounded v1-v10 race with winner/loser observations,
exact three-CLI missing/drift outcomes with source sentinels, and native-only
mandatory PILOT contrast. Then run strict validation and request a fresh Gate 1
rereview at new hashes.

## Reconfirmed unchanged properties

No collateral drift was found in executable §§1–3 or the remaining boundaries:

- literal v11 follows exact landed v1-v10;
- ASCII prefix 25 succeeds and 26/non-ASCII/invalid input fails before DB access;
- database-default utf8mb4 collation policy and exit-70 zero-mutation failure are
  unchanged;
- exact ten-column manifest, SQL NULL metadata defaults, sole auto-increment,
  plain TEXT reasons, no FK/CHECK and exact engine/charset/collation remain;
- index presentation names remain non-normative while compositions,
  uniqueness/order/subparts/direction/type/visibility and multiplicity are exact;
- absent/exact/conflict preflight, populated row/Unicode/hash/timestamp/counter
  preservation, no backfill/normalization and decoy isolation remain;
- all three output-kind literals retain bounded append/replay/conflict
  compatibility without DB taxonomy constraints;
- optional baseline/active-case tables remain excluded and no cutover semantics
  are approved;
- diagnostics, append-only history, rapid-pilot boundary and Gates 1–5 ordering
  remain sound. The DRAFT marker correctly prohibits Gate 2.

## Verification

```text
openspec validate canonicalize-classification-provenance-schema --strict
Change 'canonicalize-classification-provenance-schema' is valid
```

Strict structural validation does not detect the semantic contradiction between
the unchanged delta/design and corrected executable spec.

## Verdict

**CHANGES_REQUIRED.** The executable spec hash `a044645f…` is technically ready,
but the unchanged OpenSpec package still asserts the rejected ledger and older
ambiguous runtime/contrast semantics. Reconcile those artifacts and obtain a
fresh independent review before requesting owner approval.
