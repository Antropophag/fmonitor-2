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
