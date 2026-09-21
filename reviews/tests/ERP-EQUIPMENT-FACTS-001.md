# Reviews: ERP-EQUIPMENT-FACTS-001

## Gate 1 — 2026-09-15

- Author: root (normative spec and OpenSpec artifacts).
- Independent reviewer: `/root/gate1_spec_review`, `gpt-5.6-sol/low`.
- Final reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T113921Z-5c58ab2877/package.json`.
- Base: `25aee5524f790292d350175ba278bc47e282ed4c`.
- Snapshot patch SHA-256: `1953e4c53511642c921c55ed67bc22f18b2f92eae3a6145e03a8bc38c39f9326`.
- Verdict: `APPROVED`; complete findings list: none after corrections to the single execute seam, HMAC-only provenance, failed-run replay/retry, freshness domain, whole-batch rollback and #135 boundary.

## Verification mapping review — 2026-09-15

- Independent reviewer: `/root/plan_mapping_review`, `gpt-5.6-sol/low`.
- Package/plan: same package; plan SHA-256 `c60c9118a79815179c1d067b54f8f4cbd76919e179c00db7ea26230de95846ee`.
- Planner decision: `CRITICAL`; required reviews `gate3`, `final`; full `make test` reserved for exact-source CI.
- Verdict: `APPROVED`; 13 distinct public-seam tests map command/owner boundary, A–L, adapter, read/card, schema/recovery, scheduler/worker and console.

## Gate 2 RED

- Author: root; 13 planner-mapped executable specs plus shared disposable-DB fixture.
- Full transcript: `/Users/antropophag/.local/share/fmonitor-2/issue-12/gate2-red.log`, SHA-256 `7d246ac72e6d3a0af275745ee96d315c1707349006495b1f8af0cb4c168b062c`.
- Result: 13/13 commands exited 255 at their explicit `INTENDED_RED` missing owner/delivery/read/schema/scheduler/card/console/boundary seam. PHP syntax checks passed first. The card test was reordered and rerun after its first attempt exposed missing local vendor setup, so retained evidence contains only behavior-sensitive RED.
- Full `make test` / `make verify` was not run locally.

## Gate 3 correction candidate — 2026-09-15

- Root corrected the first reviewer return as one complete acceptance-matrix update: strict owner input versus adapter trimming, exact two-query ERP contract and invalid aggregate/stage cases, command/replay matrix, transactional rollback and concurrent owner writes, snapshots of actual production-process tables, exact HMAC/privacy assertions, recovery v28 inventory, card authorization/freshness/degradation, failed-run CLI composition and concurrent hourly-slot scheduling.
- Recomputed root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T115823Z-8efc1f9765/package.json`; plan SHA-256 `de224711c53b58e250c24c40c6255cc75cd0af674f0a46a33a679249f695611d`.
- Fresh intended RED records: `1789473525147362000-f6957c502845405f8e7eda2c9558b384`, `1789473535367156000-7a75dfbb55ad420394fa94ef11eab9b0`, `1789473538565952000-18db07b21f67428ca0c18848360af863`, `1789473541164094000-1566429765ee4525a259e6922add4832`, `1789473543389558000-72f3d6506aaf4e7abc350bbb8414fd07`, `1789473545483238000-75cdf4cece994f2280b6ce425bdffb78`, `1789473547503997000-00d3886bf8924723be166a7c67c94928`, `1789473549578399000-912bfc5f56a544b1b89eea3df2bf472a`, `1789473551628153000-347a12b922ae40719073bdfd9d365dcf`, `1789473553601452000-4fc801e9278c4aecb4cef2078ec45a47`, `1789473555694213000-3aaacd31c053489fa0053a5252d939c8`, `1789473557656340000-493a0676a00546b884f132c2b0f506eb`, `1789473559702442000-e301c4b4e28441828ccfd0d902d8a6ae`.
- Result: all 13 planner-mapped commands are fresh `INTENDED_RED` at their missing public production seam. PHP syntax and strict OpenSpec validation pass; no full local suite was run.

## Gate 3 complete matrix rebuild — 2026-09-15

- Second reviewer return was handled as a complete matrix rebuild: public `EquipmentFactsPersistenceFailure` and leakage assertions; seeded production checklist/completion/original families in J; exact five-table columns/indexes/checks plus append-only DB enforcement; full planned writer/consumer inventory with one `MariaDbEquipmentFacts` persistence seam; structural checks over the executed aggregate/stage SQL; `failed_before_success` and exact unavailable card states; independent HMAC expectations for both missing and ambiguous diagnostics.
- Recomputed root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T120916Z-9db01512bf/package.json`; plan SHA-256 `a200dbacd4266a65166d5cd6a07d8d5d1fdeac3e6c9d659c586b51cbc1592f3f`.
- Fresh RED evidence is the 13 consecutive records from `1789474164541369000-7b188e1a1c9943f1b7828c7136555995` through `1789474216707581000-29e132753a9c42ddb1a5f25bdcf63fe5`, each `INTENDED_RED` on the mapped absent public seam. Syntax and OpenSpec strict validation are GREEN; no local full suite was run.
- Independent reviewer: `/root/gate1_spec_review`, `gpt-5.6-sol/low`; reviewed package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T121107Z-b67db3aa91/package.json`.
- Verdict: `APPROVED`; complete findings list: none. All six findings from the second return and all earlier findings were verified corrected. The 13 records share exact executable source `a830e30a9311e9c590735fd7a311f0af35f97e5c9d69b4e4dce864905e044390` with the package.

## Gate 3 post-implementation test-delta review — 2026-09-15

- Root corrected three environment-sensitive expectations without changing product semantics: the real AssignmentOrderOriginal namespace/prerequisite migrations in the process-isolation fixture, MariaDB `information_schema` normalization in the schema contract, and canonical JSON key ordering in the scheduler fixture.
- Fresh pre-production package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T124930Z-2c5a24f3cf/package.json`; plan SHA-256 `c2bee58c089c20ded25dc7554e608dcd785e1d17dc41df2d1e6789f4b9ec940c`.
- All 13 mapped tests are fresh `INTENDED_RED` at the absent public production seam on candidate source `15f4d09c226e9221862d2761a24631122714fe869d051cee65ec93752c34a39a` and executable source `39daaac84e6ad9d4cec78b7388cd239c6a74ab6a18ee0d62ecb3c7bbe07e23b3`.
- Independent reviewer: `/root/gate1_spec_review`, `gpt-5.6-sol/low`.
- Verdict: `APPROVED`; complete findings list: none. The reviewer confirmed that all three deltas retain traceability, public-seam sensitivity, determinism, privacy and the complete A–L matrix.

## Gate 4 focused verification — 2026-09-15

- Final pre-review package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T125058Z-0178b2577c/package.json`; plan SHA-256 `b91259dd51182e668416ba035a81bde1d9079602be3a807f61e20168a2733c61`.
- All 13 mapped public-seam acceptance commands are GREEN on candidate source `8f0bad0b91c463c2b7c051ce7e78b80e5450bd7f4423e2f3e4c71e625b246ba3` and executable source `b4441ed07439c95ae3105ddde31f27684716925bf3257435387263be8ad29203`.
- Planner-selected changed-schema/recovery checks are GREEN: inspection completion, production migration runner, workforce canonical runner, deadline-certificate recovery and jobs recovery.
- Planner-selected category witnesses are GREEN: `change_verification_001_test.py` (16 tests), `runtime_storage_001_test.php` and `architecture_guard_001_test.py` (59 tests).
- `openspec validate sync-erp-equipment-facts --strict` and `git diff --check` are GREEN. The local full suite was not run; `make test` remains reserved for the one exact-source GitHub CI run.
