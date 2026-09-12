# Independent Gate 5 code review — YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001

- Reviewer: separately tasked agent `issue39_gate5`; authored none of the reviewed specification, OpenSpec, verification input, tests, production implementation, or Gate 3 review.
- Review date: 2026-09-12.
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T162020Z-72ad31a904/package.json`.
- Exact reviewed source: reconstructible dirty snapshot over base `6e4bf6bb3a6e923999fc06216af9a6e2abe697bb`, source digest `4389e19a3392b99b68c88bef984c26827e382768889996f9f5f726817bcde325`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T162020Z-72ad31a904/snapshot/source.patch`, SHA-256 `dc52803fe489806103366b857c3a9d378d786a6f8095984d39526ce86083502b` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T162020Z-72ad31a904/verification-plan.json`, SHA-256 `d1e120639a7e77b018a3e61e677e5c367331b17828b5210124fd886a1eb904c7`.
- Normative contract: `specs/YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001.md`; lifecycle change: `openspec/changes/exclude-documentary-closure-from-construction-control/`.

## Findings

No findings.

## Assessment

The exact-source production delta is limited to `MariaDbYiiChecklistRead::queue()`. It defines one server-side active-queue predicate — `process_state='working'` and no append-only `pto_act` root fact — and reuses that same predicate in both the total `COUNT` and row `SELECT`. Both operations therefore filter before page-count validation and before `LIMIT/OFFSET`; the excluded documentary cases cannot be returned to the client or create a stale tail page. The predicate is independent of checklist activity, while the inherited activity/object ordering and latest-engineer projection remain byte-for-byte unchanged.

The SQL is valid for the existing MariaDB schema and aliases. The correlated `NOT EXISTS` is supported by the existing unique `(installation_case_id, fact_type)` index, introduces no user-controlled SQL fragment, and does not increase selected data or authorization reach. The existing exact `construction_control.read` check still precedes the queue queries. The implementation performs only reads and changes no root facts, corrections, cases, events, assignments, schema, or audit history.

The full snapshot contains no production changes to OTIZ, `rapid-pilot/`, routing/controller/view code, or other adjacent domain owners. Verification inventory additions register the new acceptance test without broadening production scope. The approved Gate 3 test exercises the real Yii2 HTTP seam and is sensitive to PTO-only and PTO-plus-declaration exclusion, checklist-activity independence, exact 50/1 pagination, post-transition tail rejection, repeat composition/count, HEAD, guest redirect, exact-permission 403, no writes, and absence of legacy runtime loading.

## Verification evidence

The package-retained acceptance record `1789229864558141000-873f2438a2ba4b2389cbd46c5590f524` is GREEN, starts and ends at exact source `4389e19a3392b99b68c88bef984c26827e382768889996f9f5f726817bcde325`, and reports `source_drift=false`. Its harness fixture field is `UNKNOWN`; this is not treated as an approval or a general environment claim, while the reviewed acceptance itself constructs and tears down its isolated database fixture.

The following focused records are also GREEN and source-bound without drift to the same digest:

- `1789229871750301000-2f7d1575a24f434894000e584867ef06` — inspection evidence schema.
- `1789229881560715000-690cd66a52724b5f81fc70907af50f1d`, `1789229883754358000-9e5446bbdb1b4a6d9fe39f5ebb488b36`, `1789229886336631000-18e4f571f60a4fffb8e886f0af1b265a`, `1789229888254312000-0793be32dee248be944ad95c0e6c042d` — retained inspection photo controls.
- `1789229890053314000-122c96c369bc4127b8c0b110f629b642`, `1789229925897195000-5269dac6641144b8802a31adc42eb628` — verification CI/inventory controls.
- `1789229947998079000-2f63aa4a01854b79943327deea002ff1`, `1789229960249956000-a41c4f4301c448f39aeca4ef8e1c4c3e` — inherited Yii2 inspection boundary and journey controls.
- `1789229973953046000-dc675e5594cb4f349cb80ed4c12f0275`, `1789229991681580000-c340c1ccd25043f79e20d9dc9e490fe8`, `1789229994196927000-29d1064dbb2c4d3a940d998776820c62` — change-verification, runtime-storage, and architecture controls.

`git diff --check` is clean and the captured patch hash matches its manifest. Record `1789229986208861000-86cee05c047a4fbba080ce6573ba9939` is an unrelated deliberately invalid command with `outcome=UNKNOWN` and source drift; it is not a planned check and is not counted as GREEN evidence. Harness reports CI, GitHub publication state, and deployment as `UNKNOWN`; none is treated as GREEN, approval, merge readiness, or deployment authorization.

## Verdict

`APPROVED`

Gate 5 passes for exact source `4389e19a3392b99b68c88bef984c26827e382768889996f9f5f726817bcde325`. Publication and the mandatory full exact-source CI remain subsequent delivery gates; deployment remains unauthorized/unknown.

---

## Post-main-merge Gate 5 rereview — 2026-09-12

- Integrated reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T162639Z-e57c0a64d1/package.json`.
- Integrated commit: `ff0182df28d06cdc8c7c32c8a22f4fca87add1ed`, merging current `origin/main` `04f9bcb58be4b19c97993666952c3558e8922c6a` into the issue branch.
- Exact candidate source: `25ba903be880fd35b3768cb05319c87dc1995182e72b0b8582eb3856bc5c0bf5`.
- Exact executable source: `f19289742dfaba0493cce48b914328da01fe6f8f6915dcd2536aab89b65bdfe3`.
- Verification plan SHA-256: `446b9ac1cf33b9b9bf8918d8c058d7987c42d75290cd1c49c8de8862f9e32732`.
- The prepared snapshot is the clean integrated commit itself: base commit `ff0182df28d06cdc8c7c32c8a22f4fca87add1ed`, empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.

### Findings

No findings.

### Integration assessment

The diff from current `origin/main` contains exactly the 13 issue #39 paths declared by the package. The merge brought in issue #99 harness/registry changes without a production, specification, or acceptance-test conflict. The production delta remains the previously approved five-line queue change: the same server-side no-`pto_act` predicate is applied to `COUNT` and `SELECT` before page validation and `LIMIT/OFFSET`. Authorization, read-only behavior, append-only completion history, indexed SQL execution, ordering/projection, and exclusion of OTIZ, `rapid-pilot`, route/controller/view, schema, and deployment scope remain unchanged from the initial Gate 5 assessment.

All 13 focused plan commands in the integrated package are GREEN. Every retained record starts and ends at candidate source `25ba903be880fd35b3768cb05319c87dc1995182e72b0b8582eb3856bc5c0bf5`, reports `source_drift=false`, and names executable source `f19289742dfaba0493cce48b914328da01fe6f8f6915dcd2536aab89b65bdfe3`. They cover the active-queue acceptance, inspection schema, four photo characterizations, verification CI/inventory, Yii2 inspection boundaries/journey, change verification, runtime storage, and architecture guard. `git diff --check origin/main..HEAD` is clean.

Harness still reports CI, GitHub publication state, and deployment as `UNKNOWN`. These are not treated as GREEN, approval, or deployment authorization; the mandatory full exact-source CI remains outstanding.

### Rereview verdict

`APPROVED`

Gate 5 remains approved for integrated candidate source `25ba903be880fd35b3768cb05319c87dc1995182e72b0b8582eb3856bc5c0bf5` and executable source `f19289742dfaba0493cce48b914328da01fe6f8f6915dcd2536aab89b65bdfe3`.

---

## Final post-#104 integrated Gate 5 rereview — 2026-09-12

- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T165736Z-070ac754af/package.json`.
- Integrated commit: `676fa5b3551c3a9d9eabf51e3d453e13d03ecc22`, merging current `origin/main` `6680decf97d26e3fefa154fe2b509c81aa2d47f5` (including reviewed harness correction PR #104).
- Exact candidate source: `1c9e720256a928130ca926d1dc9a74006aea8eedc67b97c16cbb1e8ca1750ba0`.
- Exact executable source: `1abbc89d113bd271443cfe56e6468c53e92aa0bd6550d1d4d4e851cf4ae14c7c`.
- Verification plan SHA-256: `5e0f480bd80c0f2c51a754669d0ed434b32736b460a8aee553d6bd2d7b401bbc`.
- The clean integrated source is the snapshot base commit; its empty patch SHA-256 is `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.

### Findings

No findings.

### Assessment

The diff against current `origin/main` remains exactly the 13 issue #39 paths. The #104 merge changes only the already independently reviewed delivery-harness sibling-import behavior on main and produces no conflict or byte change in the #39 production implementation, specification, acceptance test, or OpenSpec contract. The server-side queue continues to apply its single indexed no-`pto_act` predicate identically in `COUNT` and `SELECT`, before page validation and `LIMIT/OFFSET`. The prior conclusions for authorization, read-only operation, append-only history, SQL safety/performance, retained ordering/projection, and absence of OTIZ, `rapid-pilot`, route/controller/view, schema, or deployment scope remain valid.

All 13 focused records embedded in the package are GREEN. Every record starts and ends at candidate source `1c9e720256a928130ca926d1dc9a74006aea8eedc67b97c16cbb1e8ca1750ba0`, reports `source_drift=false`, and is bound to executable source `1abbc89d113bd271443cfe56e6468c53e92aa0bd6550d1d4d4e851cf4ae14c7c`. The set covers the acceptance, schema, four photo characterizations, verification CI/inventory, Yii2 inspection boundaries/journey, change verification, runtime storage, and architecture guard.

Preflight record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/preflight/1789232075718250000-934e37a351ca424191982ef31cea1a6e.json` is bound to the same candidate/executable sources, reports `outcome=GREEN`, an empty failure inventory and `publication_ready=true`. It also preserves `pr=UNKNOWN` and `ci=UNKNOWN`. `git diff --check origin/main..HEAD` is clean and the package snapshot hash matches its manifest.

Harness GitHub/CI and deployment state remain `UNKNOWN`; they are not treated as GREEN, merge approval, or deployment authorization. The authoritative full exact-source CI remains a subsequent publication gate.

### Final rereview verdict

`APPROVED`

Gate 5 remains approved for final integrated candidate `1c9e720256a928130ca926d1dc9a74006aea8eedc67b97c16cbb1e8ca1750ba0` / executable source `1abbc89d113bd271443cfe56e6468c53e92aa0bd6550d1d4d4e851cf4ae14c7c`.
