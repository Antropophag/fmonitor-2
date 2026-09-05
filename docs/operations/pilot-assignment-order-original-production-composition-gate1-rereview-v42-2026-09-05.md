# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v42 production composition — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_composition_rereview3`
- Reviewed commit: `21745d4658c3af3c562f2f10ec33e41abe2f54c6`
- Prior review: `docs/operations/pilot-assignment-order-original-production-composition-gate1-rereview-v41-2026-09-05.md`
- Scope: v42 exact synchronization of production composition source/date,
  all-row validation and inclusion/exclusion across executable specification,
  OpenSpec delta and design, while preserving all v40 semantics and task lineage
- Verdict: **APPROVED**

The reviewer authored none of the specification, OpenSpec artifacts, tests,
production implementation, prior reviews or historical evidence. This
append-only review is the only authored artifact.

## Independent review

The executable specification, OpenSpec delta and design now select the same
production evidence. In one read-only transaction snapshot the reader selects
exact order columns `id,installation_case_id,version_no,
control_engineer_user_id,order_date` and every exact-order member's
`assignment_order_id,installer_tab_id,change_action,valid_from,valid_to`.
Physical `order_date` is explicitly the current compatibility column for the
semantic immutable `template_date`, not uploaded `documentDate`; a future
physical rename must preserve that semantic input.

All three artifacts define the same two-phase member algorithm. Before any row
is excluded, every `assign`, `retain` and `release` row must belong to the exact
order, have a positive installer identity unique across the full row set, have
an exact recognized action and valid ordered ISO dates. Then `assign|retain`
must cover `order_date` and is included, while `release` must have a non-null
end no later than `order_date` and is excluded. Any invalid row or an empty
included set fails the composition. The delta and design therefore no longer
permit an implementation to ignore malformed excluded rows, accept an
open-ended release or omit either side of active interval coverage. This closes
G1-41-1 and both G1-40 findings without adding a product decision.

The v40 identity and deterministic encoding remain exact:
`composition-<assignmentOrderId>-v<versionNo>`, compact JSON key order
`caseId,compositionIdentity,engineerUserId,installers,orderId`, JSON integer
IDs, and numeric-ascending included installer IDs. The 115-byte Example A
preimage independently hashes with both `shasum` and PHP to:

```text
388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5
```

Missing or case-mismatched order remains `REJECTED/ORDER_NOT_FOUND`; invalid
version, engineer or member composition remains
`REJECTED/INVALID_COMPOSITION`; unavailable reads remain
`FAILED/PERSISTENCE_FAILURE`. These checks remain before stream/storage and do
not mutate facts. Caller, legacy-slot, current-workforce and fixed-hash
fallbacks remain forbidden. The same derived identity/hash still feed the
accepted-operation fingerprint, immutable root evidence and evidence reader.

Task lineage is honest and unchanged: `1.27`, `2.1` and `2.4` are open at the
reviewed commit; `2.3` remains checked only for its separate database-setup
lineage. The pending active initial test and its historical Gate 3 record are
byte-identical to the v40-reviewed hashes, so neither is represented as current
approval of the derived-hash oracle. Historical reviews remain append-only.

No blocking ambiguity, product-contract divergence or stale gate claim was
found in the reviewed amendment.

## Verification

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check 10e8dd8..21745d4
(no output; exit 0)
```

## Verdict and next boundary

Gate 1 v42 is **APPROVED** at exact commit
`21745d4658c3af3c562f2f10ec33e41abe2f54c6`. Task `1.27` may be closed by the
integrator. Task `2.4` may now correct the active initial RED from the stale
fixed `111...` oracle to the independently derived digest, after which that
changed test requires a fresh separately tasked Gate 3 review before Gate 4.
This approval does not approve tests or production implementation and does not
close `2.1`, `2.4`, or any later gate.

## Exact reviewed hashes

```text
e6141aa2df6d9e457a9f3ebd593a9defdbd68b2a233d8091a238afc404b317aa  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
a81b54645f0ab8e320663d9c01bbdfa4f1f0520fc93a3f4ce321afdd95399ace  openspec/changes/replace-pilot-registration-with-original-upload/design.md
a80d44ea94c9a9e503b116e7100dfd260d0290d0bed07d6403f2a56b09472fb9  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
f54a9b2d10be9f44830c6c90c35f6ff2d9bef2ef53f9d5edc4324b02e9059f2e  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

Relevant deliberately pending active-test and historical-review hashes:

```text
0579c317f073fb303ec6bbe89c11b9f42643989546501ac4f35b2702f3559469  tests/InstallationProcess/assignment_order_original_upload_001_test.php
d8fa4511ab63b95d9032d39fe1895e878fead250e68c97befa81038f2261e609  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-initial-v2.md
```

This record intentionally omits its own circular hash.
