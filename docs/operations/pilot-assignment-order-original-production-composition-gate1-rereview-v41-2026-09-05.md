# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v41 production composition — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_composition_rereview2`
- Reviewed commit: `b2939ecf9bfbde7bb55c66eeafe59544bcdde784`
- Prior review: `docs/operations/pilot-assignment-order-original-production-composition-gate1-rereview-v40-2026-09-05.md`
- Scope: v41 correction of the production composition date/member-row rules and
  preservation of the v40 identity, mappings, evidence hashes and reopened tasks
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specification, OpenSpec artifacts, tests,
production implementation, prior reviews or historical evidence. This
append-only review is the only authored artifact.

## Independently verified properties

The executable specification now names physical `order_date` as the current
compatibility source of the semantic immutable `template_date`, explicitly
distinguishes it from uploaded `documentDate`, and preserves that semantic
input across a future physical rename. Its exact order-row source is
`id,installation_case_id,version_no,control_engineer_user_id,order_date`; its
exact member-row source remains
`assignment_order_id,installer_tab_id,change_action,valid_from,valid_to` for all
rows of the exact order in one read-only transaction snapshot.

The executable specification also now gives an unambiguous validation order and
universe. Before action-based inclusion/exclusion, every member across all
actions must belong to the order, have a positive installer ID unique across
the full row set, use exact `assign|retain|release`, contain valid ISO dates and
satisfy `valid_from<=valid_to` when `valid_to` exists. Then `assign|retain` must
cover `order_date` and is included; `release` must have a non-null closed
interval ending no later than `order_date` and is excluded. Every violation and
an empty included set map to `REJECTED/INVALID_COMPOSITION`. Missing or
case-mismatched order remains `REJECTED/ORDER_NOT_FOUND`, while unavailable DB
read remains `FAILED/PERSISTENCE_FAILURE`, all before stream/storage/mutation.

The identity and encoding are unchanged: literal
`composition-<assignmentOrderId>-v<versionNo>`, compact JSON key order
`caseId,compositionIdentity,engineerUserId,installers,orderId`, JSON integer IDs,
and numeric-ascending included installer IDs. The exact 115-byte Example A
preimage independently hashes to:

```text
388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5
```

Caller, legacy-slot and fixed-hash fallback remain forbidden. The same derived
identity/hash still feed fingerprint, immutable root evidence and evidence
reader. Tasks `1.27`, `2.1`, and `2.4` remain open; `2.3` remains checked only
for its separate database-setup lineage. The active initial test and historical
Gate 3 record retain the v40-recorded hashes, so no stale approval is presented
as authority for the derived-hash correction.

`openspec validate replace-pilot-registration-with-original-upload --strict`
passes, and `git diff --check a1d75c6..b2939ec` passes.

## Blocking finding

### G1-41-1 — the matching OpenSpec delta/design still do not encode the executable algorithm

Both v40 blocking findings required the exact correction to be synchronized
across executable spec, delta and design. V41 changed the executable spec and
the delta only; `design.md` is byte-identical to v40. It still says merely that
the composition derives from order/version and canonical
case/engineer/numeric-sorted installer JSON. It does not name `order_date` as
the `template_date` compatibility source, define the all-action validation
universe/order, specify assign/retain coverage, or define the release interval
and exclusion rule.

The changed delta is also materially looser than the executable specification.
It says “release requires closed interval ending no later than order date” and
“invalid date/ID/duplicate” without stating that all member rows are validated
before exclusion, that `valid_from<=valid_to` applies to every action when an
end exists, that release requires non-null `valid_to`, or that assign/retain
must satisfy both sides of order-date coverage. A Gate 2 or Gate 4 author using
the delta/design can therefore ignore a malformed excluded release row, accept
an open-ended release, or omit one coverage predicate while appearing to
conform to those artifacts. That is exactly the divergent-reader ambiguity
identified by G1-40-2.

Synchronize the delta and design with the executable specification's exact
two-phase algorithm and mappings. No product decision is required: this is a
coherence correction of already stated v41 behavior.

## Verdict and next boundary

Gate 1 v41 is **CHANGES_REQUESTED**. Task `1.27` must remain open, and tasks
`2.1`/`2.4` must not start from this batch. Preserve historical evidence and
the pending active test unchanged. The synchronized exact artifact batch needs
a fresh separately tasked Gate 1 rereview.

## Exact reviewed hashes

```text
e81f01537668bd95ecae874ee8704b6c55ead8b28111daa8df6e095d876e2a0f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
9680374f366e5bb2e509324b2d22a90aa1471e36e8efd5c03f2c5e5877fe9f32  openspec/changes/replace-pilot-registration-with-original-upload/design.md
25b14058143a6bf2e55e2c9fb3d4753123b4506fbf81cbe0d398031094f391a0  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
f54a9b2d10be9f44830c6c90c35f6ff2d9bef2ef53f9d5edc4324b02e9059f2e  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

Relevant deliberately pending active-test and historical-review hashes:

```text
0579c317f073fb303ec6bbe89c11b9f42643989546501ac4f35b2702f3559469  tests/InstallationProcess/assignment_order_original_upload_001_test.php
d8fa4511ab63b95d9032d39fe1895e878fead250e68c97befa81038f2261e609  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-initial-v2.md
```

This record intentionally omits its own circular hash.
