# Independent Gate 5 review — BITRIX-DOCUMENT-RUNTIME-CONFIG-001

- Date: `2026-09-24`
- Reviewer: `issue252-final-review`
- Implementation author: `issue252-executor`
- Verdict: **CHANGES_REQUESTED**
- Exact candidate source: `e430d82ef0ed7041dd217383b0053364703ab07cf0fda3abc0899b2095036206`
- Executable source: `7cbc3bbe90c5fd8fe8a2500858b67ae454a753e02449110d66af4182899e3bdd`
- Base: `10dcc95f718fbc2e09191f331f0c057799f8675d`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T095245Z-75c5f334aa/package.json`
- Required context: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T095245Z-75c5f334aa/required-context.json`, SHA-256 `6caf4be926351421a987d9b8e2319cb3327dba39ed9bb163fc9d43449cf84ac0`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T095245Z-75c5f334aa/verification-plan.json`, SHA-256 `3f7746a44b50772c548d1e8407215583573b0872eb6df9d72d7f3bfcf9aac1cf` (`CRITICAL`; Gate 3 and final required)
- Contract: `specs/BITRIX-DOCUMENT-RUNTIME-CONFIG-001.md`

## Findings

1. **HIGH — the staged document metadata and token are not consumed by jobs-worker.** `deploy/runtime/compose.yaml:156-162` still injects `FMONITOR_BITRIX_ORIGIN` and `FMONITOR_BITRIX_WEBHOOK_USER_ID` from host interpolation and merely adds `FMONITOR_BITRIX_RUNTIME_CONFIG_FILE`. No production code reads that new variable. Instead, `app/Jobs/YiiJobsRuntimeEnvironment.php:24-37` reparses `FMONITOR_BITRIX_CONFIG`, creates another temporary token with `WorkerConfiguration::stageToken()`, and overwrites `FMONITOR_BITRIX_TOKEN_FILE` with that temporary path. Consequently the published `bitrix-runtime.env` and fixed `/run/fmonitor-secrets/bitrix-token` contract required by lines 40-50 and 95-99 of the specification is decorative rather than the worker's source of truth. The focused test only searches Compose text for the unused variable and does not start the real worker bootstrap or assert its effective configuration.

2. **HIGH — three sequential renames do not provide coherent or failure-atomic rotation.** `bin/fmonitor2-stage-runtime-bitrix-config:56-65` replaces `bitrix-config.json`, then `bitrix-token`, then `bitrix-runtime.env`. A failure or interruption after either of the first two renames leaves a mixed old/new published set and changes prior bytes despite the command failing. This violates specification lines 53-56 and 84-87. The test exercises validation failures only before publication; it injects no failure between publication steps and therefore cannot detect this defect. Publication needs one atomic generation switch (or an equally strong rollback/commit design) and a regression witness at the actual failure boundary.

3. **HIGH — disabled document integration still has an unconditional token subpath mount.** `deploy/runtime/compose.yaml:175-183` and its template always mount volume subpath `bitrix-token`, while disabled staging deliberately creates no such entry. Docker volume subpaths must exist before the container is created, so the workforce-only worker can fail before startup. Even apart from engine-version compatibility, a file-level named-volume subpath is not exercised by the static string test. This contradicts specification lines 89-91 and A6. The disabled case must be proven through rendered Compose/container creation using the supported volume semantics, with no fake token contract.

4. **MEDIUM — the canonical input is not required to be private.** `bin/fmonitor2-stage-runtime-bitrix-config:17` checks regular/non-symlink/readable but accepts a group/world-readable config containing the webhook token. Specification line 32 requires a private input. The acceptance test always sets `0600` and has no permissive-mode rejection case.

5. **MEDIUM — test sensitivity does not establish A2, runtime consumption, or publication failures.** The new test invokes the stager only on already-generated JSON, so its claimed quoting coverage relies on a separately run startup test without connecting each plain/single/double-quoted `.env` input to the complete runtime result. Its Compose assertions are textual and accept an unused configuration-file variable, and its atomicity assertions cover pre-publication validation errors only. The root-owned executable contract must be strengthened and independently reviewed before corrected production can return to Gate 5.

6. **PROCESS — required Gate 3 is absent in the exact binding.** The prepared verification plan selects `CRITICAL` and `required_reviews: ["gate3", "final"]`; `harness.py state` reports both reviews `MISSING`. The lifecycle prose saying the planner did not require Gate 3 conflicts with the bound plan and cannot waive it. Corrected tests require an independent Gate 3 decision before a renewed final review.

## Evidence reviewed

The package contains exact-source GREEN records for all seven bounded local obligations:

- `1790243466854325000-6efb9a2340dd4774ae3a27499458311d` — document runtime config
- `1790243473799420000-baccd88fcb20442ebcc44bd915c94545` — Bitrix startup config
- `1790243480564215000-d711795bdecc499e97f0141e54a8ef66` — document-links delivery
- `1790243486550382000-2f2646a3cb0f4625b74a2e3ab4e43361` — jobs runtime contract
- `1790243492532752000-30045a303aca48c9b5d36a99049b3a2d` — verification governance
- `1790243522574301000-bd6914406bc545ec97ee1e30dad5a449` — runtime storage
- `1790243530085699000-c292264ed81e401e85f3573d0dace985` — architecture guard

Those results are valid for the bound source but do not exercise the blocking runtime/atomicity/disabled-integration boundaries above. Exact-source GitHub CI, PR, production API fetch, job publication, and card display remain `UNKNOWN`; no local full suite was run.

## Decision

`CHANGES_REQUESTED`

Gate 5 does not pass for exact candidate source `e430d82ef0ed7041dd217383b0053364703ab07cf0fda3abc0899b2095036206`. Make the staged contract the actual worker configuration source, provide coherent atomic publication, preserve a working disabled workforce-only topology, reject non-private input, strengthen the root tests through Gate 3, and prepare a fresh exact-source reviewer package.

---

## Corrected Gate 5 rereview — 2026-09-24

- Reviewer: `issue252-final-review`
- Implementation author: `issue252-executor`
- Verdict: **APPROVED**
- Exact candidate source: `d69c7bfe678fa85e82cbec985126898bbf77d0f41ba6825406b82701ad4c7b52`
- Executable source: `68bc0399bad790f95c7bf3b604a568e8c853d1503d8953f25d6c2a096e22101a`
- Base: `10dcc95f718fbc2e09191f331f0c057799f8675d`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T102545Z-e7a0194171/package.json`
- Required context: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T102545Z-e7a0194171/required-context.json`, SHA-256 `a8dfacc25cd2d54436bd4d99f455879d77a6858b5f324be4266fc4b5b43989e1`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T102545Z-e7a0194171/verification-plan.json`, SHA-256 `6037b5da54df0ab76d421196502790d982ddfecba6c324c79c3387275555b5f5` (`CRITICAL`; Gate 3 and final required)
- Gate 3: **APPROVED** in `reviews/tests/BITRIX-DOCUMENT-RUNTIME-CONFIG-001.md`; the root will refresh the final binding after this review file is finalized.

### Resolution of prior findings

1. **Actual worker consumption — CLOSED.** Compose now supplies only the canonical `/run/fmonitor-secrets/bitrix-config.json` plus the non-secret document root. Direct origin/user/token environment, decorative metadata and token mounts are absent. The existing `YiiJobsRuntimeEnvironment` is intentionally the sole translation owner: it parses that config, replaces hostile ambient origin/user/token values, creates a `0600` temporary token, and removes it in `finally`. The acceptance test executes this real bootstrap and observes exact origin `https://tenant.example.invalid`, user `7`, departments `[71]`, token bytes and cleanup.

2. **Coherent atomic publication — CLOSED.** The stager validates the complete private source, copies it to one private temporary file, applies ownership/mode and durability, then performs one rename to `bitrix-config.json`. There is no multi-file generation to mix. The regression rotates to a distinct valid config and then injects an `mv` failure for a third valid config; it proves the rotated bytes remain unchanged and temporary files are removed.

3. **Disabled integration topology — CLOSED.** Both Compose sources remove host, `/dev/null`, named-volume subpath and any other token-file mount. With the document root absent, rendered Compose retains the ordinary jobs worker with only the canonical workforce config, so workforce bootstrap remains available without a fake token contract. The rendered jobs contract independently rejects all duplicate runtime inputs and `bitrix-token` mounts.

4. **Private input — CLOSED.** Before publication the stager requires a regular non-symlink readable file and rejects any group/world permission bits. The focused matrix covers `0644`, unreadable, missing, empty, malformed, symlink and directory inputs with the stable redacted diagnostic and preservation of the prior config.

5. **Test sensitivity — CLOSED.** Plain, single-quoted and double-quoted `.env` inputs now pass through the real host stager, runtime stager and worker bootstrap and must produce equivalent observed contracts. The suite separately exercises replay, changed rotation, publication failure, rejected inputs, worker cleanup, static and rendered Compose, configured-root delivery, documentation safety and marker redaction. The independently approved Gate 3 records the intended REDs for permissive input and decorative runtime metadata.

6. **Required review route — CLOSED.** The corrected verification plan selects Gate 3 and final review. The independent Gate 3 record is explicit and approved; final binding refresh is an administrative next step after this rereview text changes the source digest.

### Standards and scope

No documented-standard violation or material code smell was found in the corrected delta. The implementation reuses the established host parser, `WorkerConfiguration`, worker bootstrap and one-shot staging seam rather than introducing a second credential format or owner. Workforce semantics, scheduler behavior, traversal limits, persistence/schema, UI and production data remain unchanged. Operator documentation describes rotation and validation without commands that print the webhook or token.

### Exact-source evidence

All planner-selected bounded local checks are GREEN for source `d69c7bfe678fa85e82cbec985126898bbf77d0f41ba6825406b82701ad4c7b52`:

- `1790245461016331000-20aec867789949b9bc607c1afae57d95` — document runtime config
- `1790245466878590000-376ce20252aa431e9d1337fcc0bad35a` — Bitrix startup config
- `1790245471742863000-68487126bde84842837c9fc709a6bea8` — configured-root document delivery
- `1790245475648235000-eed633ccad0a4ebdb7c184d313d8c96c` — rendered jobs runtime contract
- `1790245479759647000-7746d2d2d31941449badf91788da7b32` — verification governance
- `1790245506045402000-421ed999618a4f91a0822c4728f7cd30` — runtime storage
- `1790245512020055000-77dea15e2a95484ba9762a6a6369e1fa` — architecture guard

`git diff --check` is clean. No local full suite was run. Exact-source GitHub CI, PR, production Bitrix access, job publication, deployment and card display remain `UNKNOWN` and are not implied by this approval.

### Corrected decision

`APPROVED`

Gate 5 passes for exact candidate source `d69c7bfe678fa85e82cbec985126898bbf77d0f41ba6825406b82701ad4c7b52`. The prior `CHANGES_REQUESTED` remains historical for `e430d82ef0ed7041dd217383b0053364703ab07cf0fda3abc0899b2095036206`; all of its findings are closed in this reviewed source. Final harness recording requires the planned refreshed binding that includes this review-file update, followed by the repository's authoritative exact-source CI step.

---

## Post-CI characterization correction rereview — 2026-09-24

- Reviewer: `issue252-final-review`
- Implementation author: `issue252-executor`; characterization correction author: `root`
- Verdict: **APPROVED**
- Exact candidate source: `cc87dac2ec209ecbb7a34384917a398d1022393e60b65b5bfeb2c03c25134b7d`
- Executable source: `47120c5bee52a9ac9f56d8973ab5f9e2b51d699de24b4b3fbce60116143a244a`
- Base: `10dcc95f718fbc2e09191f331f0c057799f8675d`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T105505Z-916318a012/package.json`
- Required context SHA-256: `a8dfacc25cd2d54436bd4d99f455879d77a6858b5f324be4266fc4b5b43989e1`
- Context manifest SHA-256: `a6628f00a7e692c24156c63fc0e662e6db2b87896c7f880fd267b6111e614945`
- Verification plan SHA-256: `f60c9e08deff07a86e9f0c5e49d94a59747f2d1b19a9cc8cddb0ba2a5505f9b3`
- Supplemental independent test review: **APPROVED** in `reviews/tests/BITRIX-DOCUMENT-RUNTIME-CONFIG-001.md` for source `9cab04a0f6621a255fa9cc7bf29956ad895a64b8cc7e136f8e463b7722ed83ee`.

### Delta review

The only executable delta since the preceding Gate 5 approval is the one-line correction in `tests/Deployment/erp_equipment_facts_runtime_001_test.py`. It replaces the obsolete fixed-path/fixed-identity literals `mktemp /run/fmonitor-secrets/` and `chown 10001:10001` with assertions for the implemented and specified parameterized boundaries `mktemp "$target_directory/` and `chown "$runtime_uid:$runtime_gid"`. The characterization still requires `WorkerConfiguration::fromFile`, `sync -f`, `chmod 0600` and `mv -f`; it therefore continues to detect removal of validation, privacy, ownership, durability or atomic replacement. This is alignment with the approved overridable test seam, not relaxation of the security contract.

`verification-input.json` now declares the changed registered characterization in the planned paths and maps it to A1–A6/A8. The supplemental Gate 3 review independently approved that exact test delta. Production code, Compose, operator documentation, canonical specification and all previously reviewed issue #252 acceptance behavior are unchanged. The six findings closed by the prior rereview remain closed.

### Exact-source focused evidence

All eight selected bounded checks are GREEN for source `cc87dac2ec209ecbb7a34384917a398d1022393e60b65b5bfeb2c03c25134b7d`:

- `1790247206438354000-e127b82adc4647cbb09b5533dcad43ad` — document runtime config
- `1790247213671851000-e2e1258b934048d5be6e4cfe94d1210d` — Bitrix startup config
- `1790247218754299000-e4e8817857ab4cfbb40b8c18f8346aae` — ERP/runtime staging characterization
- `1790247223396199000-c64e58e8eb1544f8a6cdc1610f06e840` — configured-root document delivery
- `1790247227747922000-8facf31feed14ce3afeb7a188d8cb85d` — rendered jobs runtime contract
- `1790247232326691000-8cae44c77658497eb2ccd79d03d68b8b` — verification governance
- `1790247262908371000-53c5e29118f14655818da3077aa60f25` — runtime storage
- `1790247268682896000-b158788a8c53455b8131427f9866cc69` — architecture guard

### CI disposition

GitHub CI run `35987546040` supplied a complete failure inventory. Its unit failure was exactly the stale characterization corrected here. Integration (2/2), e2e, governance and fast passed. Integration (1/2) retains the unrelated `inspection_item_complete` missing-revision `JsonException`/race failure; bounded local reproduction did not reach that case because database migration setup was unavailable. Aggregate verify failed accordingly. This review does not relabel that unresolved external failure as GREEN and does not claim CI admission, merge readiness, deployment, production Bitrix access, job publication or card display.

### Final decision

`APPROVED`

Gate 5 remains approved for exact candidate source `cc87dac2ec209ecbb7a34384917a398d1022393e60b65b5bfeb2c03c25134b7d`. The characterization correction is scoped, independently test-reviewed and exact-source GREEN; no production or contract regression is introduced. Harness recording requires the planned refreshed binding containing this finalized review update. CI admission remains separate and non-green due to the retained unrelated Integration (1/2) failure.
