# Code review: CONSUMER-OWNERSHIP-FRONTIER-153-B

- Scope/spec/test author: root delivery agent
- Production implementation author: `/root/executor_consumer_frontier` (`gpt-5.6-sol`, low)
- Gate 3 reviewer: `/root/gate3_consumer_frontier` (`gpt-5.6-sol`, low); final corrected test verdict `APPROVED`
- Gate 5 reviewer: pending independent agent
- Base: `b9dfcb4d9d1cdd934f16fa4a9f4910464f1ddfc6`
- Local full suite: not run, owner prohibition preserved

## Focused GREEN

- Acceptance: `python3 tests/Verification/change_verification_consumer_frontier_153_test.py` — 12/12 GREEN; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789589556816627000-3602872b793e482290e39d7919330f5b.json`.
- Existing planner regression: `python3 tests/Verification/change_verification_001_test.py` — 18/18 GREEN; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789589563189603000-38bfb3d52173401c95ce93fece697832.json`.

## BEFORE / AFTER

BEFORE executes the immutable Slice A planner from commit `b9dfcb4d9d1cdd934f16fa4a9f4910464f1ddfc6`, planner blob SHA-256 `788eb80bf11afbd79ed38129555227deaacda7aa1b160cb539c29324bce1395c`. Synthetic #20 selected the broad integration representative but did not select or explain these ownership consumers: `migration_verifier_001_test.py`, `current_schema_recovery_001_test.py`, `runtime_inventory_001_test.py`. Synthetic #148 likewise omitted ownership selection for `current_assignment_001_test.py` and `assignment_runtime_001_test.py`.

AFTER mechanically emits:

- #20: `canonical-schema-frontier → migration_verifier`; `canonical-schema-frontier → current-schema-recovery → current_schema_recovery`; `canonical-schema-frontier → current-schema-recovery → runtime-schema-inventory → runtime_inventory`.
- #148: `current-assignment → current_assignment`; `current-assignment → assignment-runtime → assignment_runtime`.

Each entry binds changed path, root capability, full ordered capability chain, verifier identity and canonical argv. Diamond paths retain separate causal evidence while execution remains one command. Slice A full integration closure and FAST presentation controls remain unchanged.

## Gate 5 decision

Pending independent review of the exact complete candidate.

### Review 2026-09-16 — independent Gate 5

- Reviewer: `/root/gate5_consumer_frontier` (`gpt-5.6-sol`, low), independent of scope/spec/test and production authorship.
- Exact candidate source: `ede84cdefa29acecece262e0fb2936f4986056fb921766f766eafa7a95508a0a`; committed head `c06ba96f73ae7a543703b8d47f2b29dee683e66f` on base `b9dfcb4d9d1cdd934f16fa4a9f4910464f1ddfc6`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T201440Z-31c0132431/package.json`; SHA-256 `e32dc7cf6c73900ded2427a3c33e4981aa0427fbb5100512f4578f72e4a3ae59`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T201440Z-31c0132431/verification-plan.json`; SHA-256 `707ccd37cc0fdb62efadbe6ed4cf6cb40894aff2e4d40b4f5df6a308601ab8c5`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Required context: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T201440Z-31c0132431/required-context.json`; SHA-256 `bf7d086bd0ac499fa9a51f8062cf34c0c5cfadba0d5cd90405019a981df60473`. Task-context manifest SHA-256: `60d9332fda43bd91676fab3b257b7416f9f0c86f4c40390decea78214312b246`.
- Retained exact-source snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T201440Z-31c0132431/snapshot`; base `c06ba96f73ae7a543703b8d47f2b29dee683e66f`; empty committed-source patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.
- Gate 3 reviewed: corrected exact test verdict `APPROVED`, including the immutable Slice A BEFORE oracle, full A–M matrix, diamond/cycle evidence and preservation controls (`reviews/tests/CONSUMER-OWNERSHIP-FRONTIER-153-B.md:93-112`).
- Exact-source focused evidence reviewed: `python3 tests/Verification/change_verification_consumer_frontier_153_test.py` — 12/12 GREEN, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789589642248244000-afefe8649db346b4b71f9aab936fb63c.json`; `python3 tests/Verification/change_verification_001_test.py` — 18/18 GREEN, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789589648617513000-28abdcffd61b43c6832f084524063489.json`. Both bind source `ede84cdefa29acecece262e0fb2936f4986056fb921766f766eafa7a95508a0a` and executable source `3d9930784609da70538effe500936fff20bcacb7c2d8668592b80603529fa411`.
- Local full suite was not run, preserving the owner prohibition. Exact-source GitHub CI remains a later delivery gate and is not claimed by this review.

#### Findings

1. **HIGH — The production policy collapses every protected semantic surface into one giant, inaccurate ownership graph.** `.quality-graph/verification-policy.json:271-325` duplicates the complete Slice A protected-pattern inventory into the single root `protected-semantic-contract`, then assigns every matching schema, persistence, domain, job, UI-application, recovery and runtime change the same two root verifiers and the same recovery/runtime chain. Consequently, for example, an `app/Otiz/**` or `app/InspectionEvidence/**` change is reported as causing `production_schema_frontier_001_test.php`, `selection_native_authority_001_test.php`, both recovery verifiers and `canonical_integration_runtime_001_test.py`, even though the policy records no capability-specific ownership relationship proving those consumers. This violates the issue requirement for the smallest maintainable repository-native capability-to-consumer representation and its explicit direction to avoid a giant manually maintained global dependency graph; it also defeats the contract's bounded-capability semantics (`specs/CONSUMER-OWNERSHIP-FRONTIER-153-B.md:9,13-17`). The implementation makes this duplication especially costly because `tools/delivery/change-verification.py:378-389` treats the coarse node as authoritative causal ownership for every protected path. The synthetic tests only replace policy with small accurate fixtures (`tests/Verification/change_verification_consumer_frontier_153_test.py:57-65,86-93`), so they cannot catch this production-policy defect. **Required correction:** replace the catch-all node with bounded repository-owned capabilities whose owner patterns, direct verifiers and downstream edges correspond to actual production ownership (at minimum the Slice B schema/migration and current-assignment frontiers), colocating/reusing existing contracts or inventory where practical rather than copying the entire semantic-surface list. Leave protected surfaces without demonstrated ownership fail-closed instead of manufacturing a universal chain. Add a production-policy regression that proves representative capabilities select their own causal chains and that an unrelated protected surface does not inherit an unrelated chain merely because it is protected; recompute the plan and restart at Gate 2/Gate 3 because this changes policy and tests.

The graph traversal implementation otherwise preserves one evidence item per unique simple chain, finite cycle traversal, deterministic ordering and command deduplication; validation fails closed for unknown capability targets and missing/unregistered verifier files. Slice A integration closure and presentation-only behavior are covered by the reviewed focused evidence. These strengths do not make the production ownership claims accurate.

#### Verdict

`CHANGES_REQUESTED`

Gate 5 does not approve exact source `ede84cdefa29acecece262e0fb2936f4986056fb921766f766eafa7a95508a0a`. Correct the production ownership model and add the missing production-policy sensitivity before requesting a new final review. Because the correction necessarily changes policy/tests, recompute the verification plan and obtain renewed Gate 3 approval as required by the resulting plan.

### Rereview 2026-09-16 — bounded shipped ownership correction

- Reviewer: `/root/gate5_consumer_frontier` (`gpt-5.6-sol`, low), still independent of scope/spec/test and production authorship.
- Full corrected exact source: `85933cb375a4c538f1a1c752252a9cc4945556863d5c5dac6dd28a1190a17952`; committed head `795884dfca75eb99742870940efbc9fe146ab9a8` on base `b9dfcb4d9d1cdd934f16fa4a9f4910464f1ddfc6`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T202925Z-83b11f769f/package.json`; SHA-256 `3e6d8e01b117338e46ca80059d8af40a62e8168ed2cd69b3b633de38afb042bb`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T202925Z-83b11f769f/verification-plan.json`; SHA-256 `a03504f8be79a262ee628c4db39d78a53352d7e4711e2a905569d7efc6601835`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Required context SHA-256: `bf7d086bd0ac499fa9a51f8062cf34c0c5cfadba0d5cd90405019a981df60473`; task-context manifest SHA-256: `98501eab0471ee59cabf4f400fb728fe220b571e72188b3e94bfd9377cd3e6bd`.
- Retained exact-source snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T202925Z-83b11f769f/snapshot`; base `795884dfca75eb99742870940efbc9fe146ab9a8`; empty committed-source patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.
- Renewed Gate 3 evidence reviewed: Case N exact shipped-chain rereview `APPROVED` (`reviews/tests/CONSUMER-OWNERSHIP-FRONTIER-153-B.md:139-164`) and additional legacy-regression audit `APPROVED` (`:166-191`).
- Exact-source focused evidence reviewed: `python3 tests/Verification/change_verification_consumer_frontier_153_test.py` — 13/13 GREEN, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789590518124624000-8b6d5d91fd244f4097bb51d0cd9723ac.json`; `python3 tests/Verification/change_verification_001_test.py` — 18/18 GREEN, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789590525853593000-4a135fc1816347828d22b157fc58b8a0.json`. Both bind source `85933cb375a4c538f1a1c752252a9cc4945556863d5c5dac6dd28a1190a17952` and executable source `2cdbd0f3e0f3dc340ed9d2d7e8a91fdb011fb2b98fcf11665207baa23cc301e3`.
- Local full suite was not run, preserving the owner prohibition. Exact-source GitHub CI remains required after review and is not claimed here.

#### Prior-finding disposition

**Resolved.** `.quality-graph/verification-policy.json:271-343` no longer copies every Slice A semantic pattern into a universal root. It declares two bounded production roots: `canonical-migration-frontier` owns only the three canonical migration entrypoints and reaches the independently specified migration runner/schema, recovery/forward-update and runtime-inventory witnesses; `standalone-current-assignment` owns only the six current-assignment command/read/schema files and reaches native assignment, selection-authority and Yii runtime witnesses. All terminal files exist and resolve through canonical `tools/verification/suites.tsv`. The roots do not share terminals or inherit one another's chain.

Case N loads the shipped policy and canonical inventory through the public planner seam and compares the complete ordered `consumer_expansions` for representative migration and assignment paths, including changed path, root, every capability-chain segment, terminal identity and canonical argv (`tests/Verification/change_verification_consumer_frontier_153_test.py:352-421`). Whole-list equality catches missing, extra, reordered, cross-owned or fabricated verifier evidence. Its unrelated protected Otiz path must fail with `PROTECTED_CAPABILITY_OWNER_MISSING`, proving that policy does not regain a catch-all merely to admit other Slice A surfaces. The normative bounded witnesses are recorded in `specs/CONSUMER-OWNERSHIP-FRONTIER-153-B.md:43-48`.

The accompanying legacy planner-test corrections preserve their prior purposes under the new fail-closed precondition: generic protected Persistence/Otiz cases now assert owner-missing, acceptance-category mapping uses an unprotected subject, and the InspectionEvidence consumer test adds only a fixture-local exact owner with no verifier/consumer claims (`tests/Verification/change_verification_001_test.py:298-346,413-443`). No production catch-all or fail-open bypass was introduced.

#### Remaining findings

None. Full-candidate inspection also reconfirmed deterministic unique-chain evidence, cycle termination, diamond preservation, execution deduplication, missing/stale target and verifier rejection, Slice A integration closure, and unchanged presentation-only behavior.

#### Rereview verdict

`APPROVED`

Gate 5 passes for exact source `85933cb375a4c538f1a1c752252a9cc4945556863d5c5dac6dd28a1190a17952`. This verdict covers the reviewed committed candidate and focused evidence only; exact-source GitHub CI remains required before PR-ready delivery can be claimed.
