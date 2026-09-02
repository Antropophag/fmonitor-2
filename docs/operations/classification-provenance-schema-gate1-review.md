# CLASSIFICATION-PROVENANCE-SCHEMA-001 — independent Gate 1 technical review

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or edit the executable specification, OpenSpec
artifacts, evidence, production or tasks. This record is the only edit. Gate 2
is not authorized.

## Reviewed hashes

```text
85c6f51d4fc697e10b8265b1361031dfd5d6f9ab5200e0ab4cf15f83409632af  specs/CLASSIFICATION-PROVENANCE-SCHEMA-001.md
7eb5f2c8936fc4a75aa8708130eab7ffccb74fa6a4895802e64bd36d2cf2bfd5  docs/operations/active-baseline-provenance-schema-evidence.md
ba18b1ddf1f4add42d5ddc42401ff14a6a46044930bcdacbf57ee69670e6c116  docs/operations/active-baseline-provenance-schema-review.md
75fdf6a41af7488b409b9d3ef6249b42a26f44985e4bf52b916102a5af6d0620  docs/operations/classification-provenance-schema-planning-review.md
4f7f1b74026453a510e2a65bb270af63d3ffa0429569468ea878c81584bce533  docs/operations/classification-provenance-schema-planning-rereview.md
2ac5c80b7a10f2ccae1ec7ef7ab2ca438d310ef3933187c6b710fd7faa864436  openspec/changes/canonicalize-classification-provenance-schema/proposal.md
2711fe9998f8e88b6ae2b60b1c838d97296cebced9b23de7d8c0195d6bde3005  openspec/changes/canonicalize-classification-provenance-schema/design.md
e97fc2d539d94495a1ba0240a41f82f7c6c68558f0a23611f65fd82c795e6d41  openspec/changes/canonicalize-classification-provenance-schema/specs/persistence/classification-provenance-schema/spec.md
2e31c24072a9a54e10e08d2d619690237763baced5efe8f34c323d614c31a265  openspec/changes/canonicalize-classification-provenance-schema/tasks.md
df1c58fa0437fc51868db2e91bcba35793d07957f6359aa36407552e8a17319d  bin/fmonitor2-migrate.php
a1c1ba68dd36482b316764119d41c0c56843c63815154984bd37233c4596a943  PRODUCT.md
98c10bf12d606580e420587dd389dda0cbbbbf65b8cf196d20aeb60dd2b11e98  CONTEXT.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

## Required findings

### R1 — durable migration-ledger claims contradict the real canonical runner

Executable §4 requires two racing runners to produce “one durable v11 ledger
version”. The OpenSpec clean scenario likewise says the runner records exactly
one migration-ledger version. The current v1-v10 canonical runner deliberately
has no durable migration ledger: `schemaVersion` and `appliedVersions` are
observations of the current invocation. This fact is explicitly documented by
approved planning v9 and completion v10 contracts.

No migration-ledger table, row or transaction is in this change's manifest or
scope. Therefore the claimed durable fact is unobservable and infeasible without
an unplanned cross-cutting runner redesign. Replace every ledger statement with
the actual public observation contract: v11 appears in the successful
invocation's `appliedVersions` only when that invocation created the table;
exact repeat reports no applied version and creates no product/audit row.

If durable migration history is genuinely required, it needs a separate
explicitly designed/approved runner-ledger change rather than being implied by
this one-table slice.

### R2 — the special two-runner race is not executable precisely enough

The draft says “two public runners may race on a clean table name”, but does not
fix the predecessor state or exact winner/loser outcomes. Racing two completely
clean v1-v11 runners also races every preceding migration, a concurrency mode
the current canonical contracts explicitly do not support. Racing only v11 on
an exact v1-v10 predecessor is a different, bounded case and is feasible if the
loser handles MariaDB table-exists by re-inspecting the exact table.

Specify:

- exact v1-v10 predecessor already present and only the configured v11 table
  absent before release of a test barrier;
- two real runner processes/connections, with no test-only migration method;
- accepted exact exit/stdout alternatives for winner and loser, including which
  invocation may report `appliedVersions:[11]` and which must report no-op;
- final exact table, zero rows, no incompatible/partial state, no leaked SQL or
  table/prefix diagnostic, and repeat behavior after both processes exit;
- how a non-table-exists failure remains a redacted migration failure rather
  than being normalized into success.

Remove the unsupported general cross-runner implication and the nonexistent
ledger requirement. The scope may still explicitly say other migrations and
arbitrary concurrent runner catalogues remain unsupported.

### R3 — missing/drift runtime failures lack exact public outcomes and fetch probes

Section 5.2 requires a “stable generic infrastructure failure” for three
different seams but gives no exact exit/status, stdout/stderr or response body
for native import, historical import and active-baseline reconcile. It also does
not define how “before source fetch” is observed. Gate 2 cannot independently
distinguish a correct fail-closed adapter from an early unrelated failure or a
consumer that fetched source and merely avoided output mutation.

For each public consumer, define the exact external contract already owned by
that seam: command/HTTP entrypoint, exit/status, exact redacted stdout/body,
empty or bounded stderr/headers. Define a test-owned source sentinel/counter or
unreachable source fixture proving zero source access, then separately snapshot
canonical output tables, provenance table, optional ambient tables and decoys
to prove zero mutation.

For literal `active_baseline`, keep the test at the provenance-reconcile seam
unless optional cutover prerequisites have separately landed. State explicitly
that no source/baseline output pipeline is implied there; its precondition must
run before provenance DML and must not create/check the two excluded optional
tables. Do not use a vague common outcome to hide three different public seams.

### R4 — pin one bounded non-atomic contrast instead of an ambiguous “or”

Section 6 asks Gate 2 to inject one post-output conflict, while the OpenSpec
scenario says native case, historical snapshot “or” active baseline. It is not
clear whether all three are required or which single representative is the
normative contrast. The active-baseline path also depends on optional storage
that this slice deliberately excludes.

Choose an exact mandatory case—recommend native operational import because it
is release-supporting and does not require optional cutover ownership. Define
the test barrier/injection point after proof that this invocation newly created
the output and before provenance INSERT, the exact public result, the exact new
output row identity, absence of its matching provenance row, and replay state.
If historical/active variants are also required, specify each independently and
provide their prerequisites without canonicalizing optional tables.

Keep `PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE` explicitly a characterization label,
not an accepted target invariant or a new production error contract.

## Confirmed properties

- Literal v11 correctly follows the now-landed v1-v10 catalogue. The 39-byte
  basename plus 25-byte ASCII prefix reaches the 64-byte identifier boundary;
  26-byte/non-ASCII/invalid pre-access rejection remains consistent with the
  current runner.
- The ten ordered columns, unsigned types, lengths, NOT NULL/SQL NULL metadata
  defaults, sole AUTO_INCREMENT, character metadata, plain TEXT reasons,
  InnoDB/explicit validated database-default collation and absence of FK/CHECK
  match accepted evidence.
- Index semantics are precise and feasible with non-normative presentation
  names: exact primary `id`, unique ordered `(output_kind,output_id)` and one
  nonunique `(legacy_object_id)`, with multiset comparison able to reject
  duplicate/extra/subpart/order/type/visibility drift without comparing names.
- Absent/exact/conflict preflight, deterministic table-name conflict, populated
  Unicode/plain-text row and AUTO_INCREMENT preservation, no backfill and decoy
  isolation are coherent for a one-table additive migration.
- All three PILOT_ONLY output-kind literals are now named. Append/replay/conflict
  storage behavior is bounded and no DB CHECK promotes them into target taxonomy.
- Optional `fm2_legacy_active_baselines` and `fm2_active_case_provenance` are
  explicitly excluded; active-baseline compatibility does not approve cutover.
- Database-default failure mapping, diagnostics redaction, append-only runtime
  rows, rapid-pilot boundary and Gates 1–5 ordering are otherwise sound. The
  DRAFT marker correctly prohibits Gate 2 before owner approval.

## Verification

```text
openspec validate canonicalize-classification-provenance-schema --strict
Change 'canonicalize-classification-provenance-schema' is valid

git diff --check -- <reviewed planning artifacts>
exit 0, empty output

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
```

Strict validation and architecture GREEN prove artifact shape/boundaries, not
the missing executable outcomes above.

## Verdict

**CHANGES_REQUIRED.** The one-table ownership, v11 ordering and manifest are
ready, but the draft must remove the nonexistent durable-ledger semantics and
make race, three runtime-failure seams and the bounded non-atomic contrast
executable before owner approval. Any normative revision requires a new hash
and fresh independent Gate 1 review.
