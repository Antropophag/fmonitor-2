# Code review: VERIFICATION-CANONICAL-INVENTORY-001

## Final review — 2026-09-15

- Reviewer: Codex independent reviewer `/root/gate5_final`; not author of the specification, tests, or implementation.
- Specification and test author: root agent.
- Implementation author: `/root/executor` (gpt-5.6-sol/low).
- Gate 3 and test-delta reviewer: `/root/gate3_review` (gpt-5.6-sol/low).
- Reviewed base: `25aee5524f790292d350175ba278bc47e282ed4c`.
- Exact reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T123522Z-d1ea6486aa/package.json`.
- Reconstructible snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T123522Z-d1ea6486aa/snapshot`.
- Candidate source: `919ca110bff8f9e1c2d6787b82c38fc2ba0bfa4c919569ac83501c07e8e70385`.
- Executable source: `dfeb07b8982953693387232a9a64517fc2d108538cf9ff4ca1125756b98eee84`.
- Verification plan SHA-256: `88b08219356945fc7245bb42bf53225117d124361e8540d72ca506270c343559` (`CRITICAL`; required reviews `gate3`, `final`).
- Verdict: `APPROVED`.

### Findings

None.

The candidate conforms to R1–R5. It migrates the exact 427 base entries without changing their suite, runtime, path, or CI category; removes `categories.json` and active references; centralizes parsing, validation, discovery, registration, suite projection, and category projection in the canonical inventory module; preserves the existing category partition and integration sharding; rejects added canonical tests before a review package can be prepared; and keeps the substantive CI governance contract out of the fast node while retaining it exactly once in governance.

Registration requires all four explicit values, validates the existing and candidate inventories before one sibling-file atomic replace, changes only `suites.tsv` on success, and preserves bytes on pre-replace rejection. Inter-process writer coordination remains explicitly outside this bounded contract. Product code, FAST classification, Quality Graph aggregation, test coverage, retired-adapter cleanup, and deployment behavior were not expanded or weakened.

The first Gate 5 review found that normal inventory projections accepted physically noncanonical rows. That finding is closed in this exact candidate: normal `load`, `validate`, `list`, planner, shell, and CI consumers reject noncanonical bytes; only explicit `canonicalize` permits unordered input and emits the normative tuple order. The independently approved test deltas exercise rejection through validation, category listing, and CI/shard projections, while preserving a deterministic normalization seam.

### Verification evidence

- `python3 tests/Verification/verification_inventory_001_test.py`: GREEN, 21 tests, record `1789475638119928000-fcd296b9a6dc436ca4e4f5ffad9a7cb4`, bound to the candidate/executable source above.
- `python3 tests/Verification/change_verification_001_test.py`: GREEN, 18 tests, record `1789475662760365000-d6cd1cdd64274854938dcef99555f8f8`, bound to the candidate/executable source above.
- `python3 tests/Verification/verification_ci_001_test.py`: GREEN, 18 tests, record `1789475690294240000-926796e7bdc84ae78fac39f9b387e8eb`, bound to the candidate/executable source above.
- Direct reviewer checks: canonical inventory validation GREEN; roster GREEN with 427 tests and category counts unit 101, integration 268, e2e 46, governance 12; `verification_ci_001_test.py` appears exactly once in governance; `categories.json` and bounded active references are absent; `git diff --check` GREEN.
- Independently approved RED sensitivity and every subsequent test-delta approval are recorded in `reviews/tests/VERIFICATION-CANONICAL-INVENTORY-001.md`.

The constitutionally prohibited local full suite was not run. The planner-selected exact-source GitHub CI run remains a post-review delivery requirement; its current status is `UNKNOWN`, not GREEN, and this review does not claim publication, merge, or deployment readiness.

### Required changes

None.

## Post-CI correction review — 2026-09-15

- Reviewer: Codex independent reviewer `/root/gate5_final`; not author of the specification, tests, implementation, or post-CI corrections.
- Specification and original test author: root agent. Original implementation and runner correction author: `/root/executor` (gpt-5.6-sol/low). Root authored the bounded direct-consumer fixture migrations. `/root/gate3_review` independently approved the post-CI test delta.
- Reviewed base: `25aee5524f790292d350175ba278bc47e282ed4c`.
- Exact reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T131353Z-2d980d72d4/package.json`.
- Reconstructible snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T131353Z-2d980d72d4/snapshot`.
- Candidate source: `080e1fff89a72ae1d0bbc9ad99b0e2decf31c136218fb6fb526e5f36cf838b6e`.
- Executable source: `85fea4c10acb6ea1cf9c64acd93efd153054fd5ceea36d9b2685b7c69cd4e70d`.
- Verification plan SHA-256: `6aeabc352dcf47ef0c18c6dedfcde24b17c86fad53f5c5431e693a16e57af005` (`CRITICAL`; required reviews `gate3`, `final`).
- Verdict: `APPROVED`.

### Findings

None.

The first exact-source Quality Graph run `34969985820` on commit `0fba8224` was correctly retained as failed evidence. The complete job inventory identified primary failures in fast, governance, unit, and Integration 2/2, with downstream verify failure; E2E and Integration 1/2 were GREEN. The corrections match the three diagnosed migration gaps: the shell runner now validates rather than trying to list the pseudo-suites `lint` and `red`; the isolated Quality Graph fixture copies the shared parser; and the active delivery-harness and FAST fixtures use canonical four-column `suites.tsv` data instead of the deleted JSON registry or three-column rows.

The runner correction preserves fail-closed behavior and returns the shared validator's diagnostic and exit status before executing lint or RED behavior. Valid legacy suites still use the same canonical list seam. The fixture migrations retain their prior behavioral assertions, create required inventory members and files explicitly, and keep canonical ordering. The updated early failure scenario now proves `UNREGISTERED_TEST` before registration, then continues to verify generated-source drift, undeclared dependency rejection, retained evidence, and non-publication after the inventory is corrected.

No product source, server-side admission implementation, publisher behavior, allow-failure path, FAST classifier rule, category assignment, shard behavior, or #107 enforcement behavior changed. Adding `delivery_execution_107_i1_test.py` to the bounded expected verifier list only reflects the entry already present on the reviewed base and does not implement or approve #107.

### Verification evidence

- `python3 tests/Verification/verification_inventory_001_test.py`: GREEN, record `1789477934165951000-5bc7b0d30a174463aef1e7420b10a8e4`, bound to the candidate/executable source above.
- `python3 tests/Verification/change_verification_001_test.py`: GREEN, record `1789477963778452000-f6cd4347665e4f7d8fc49ee7a9861cdb`, bound to the candidate/executable source above.
- `python3 tests/Verification/verification_ci_001_test.py`: GREEN, record `1789477993840876000-bc6531f8bbaf407c8ac423aec4e4cfab`, bound to the candidate/executable source above.
- Independent bounded reviewer checks: `bash tools/verification/run.sh lint` GREEN; `quality_graph_ci_setup_001_test.php` GREEN; `fast_lane_118_classification_test.py` 7/7 GREEN; `delivery_harness_hardening_001_test.py` 9/9 GREEN; `git diff --check` GREEN.
- The post-CI direct-consumer test delta and its preserved assertions are independently approved in `reviews/tests/VERIFICATION-CANONICAL-INVENTORY-001.md`.

The failed first CI run remains failure evidence and is not reclassified. A new exact-source GitHub CI run for this reviewed candidate is still required before publication readiness; this verdict does not claim that run, merge, deployment, or #107 enforcement as GREEN.

### Required changes

None.
