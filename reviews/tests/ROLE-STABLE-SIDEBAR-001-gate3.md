# Gate 3 test review: ROLE-STABLE-SIDEBAR-001

- Reviewer: separately tasked agent `/root/sidebar_gate3` (OpenAI Codex, `gpt-5.6-sol`, low); authored none of the reviewed specification, lifecycle artifacts, or test.
- Review date: 2026-09-19.
- Test author: root agent.
- Reviewed source: reconstructible dirty snapshot over base `62d027d54af7a01a1da300eda2901ba68b2bab20`; candidate source `a11bb0b07987afa78c8ec4e6c03fb4793aa0756191a81fa1d6cb8abb1abba753`; retained patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T134918Z-b04b77d6db/snapshot/source.patch`, SHA-256 `f31b62b2f9759b4424123f80dd86c72c21b484ec2b5f32c2a323c9097852ec71`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T134918Z-b04b77d6db/package.json`; verification-plan SHA-256 `468f93d66dd67644cd637f0d1fa19bd98c6dbd77543392722c34c7e741eb9120`.
- Specification: `openspec/changes/fix-role-stable-sidebar/specs/runtime/role-stable-sidebar/spec.md` (`ROLE-STABLE-SIDEBAR-001` acceptance binding).
- Reviewed lifecycle/context: proposal, design, tasks, verification input and mapping, current delivery goal, delivery record, required-context manifest, test delta, and retained intended-RED record.
- Public seam: repeated real Yii HTTP GET responses and semantic DOM of `nav[aria-label="Основная навигация"]`, with direct-route authorization and persisted-fixture comparisons.
- RED evidence: retained command `php tests/Yii2/yii2_main_navigation_001_test.php`, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789825745220977000-93eaf88b520f47ce8c5d7124e9fe19e5.json`. It reached the HTTP/DOM behavioral assertion and failed on `/pilot/installers` because permitted `ОТиЗ` was absent; this is an intended defect failure rather than setup failure.
- Verdict: `RETURNED` (`CHANGES_REQUESTED`).

## Findings

1. **HIGH — three restricted matrices construct an impossible duplicate feedback expectation.** In `tests/Yii2/yii2_main_navigation_001_test.php:70-75`, `$assertMatrix` always adds the route-specific feedback link before adding every entry from `$expectedSections`. The `withoutObjects`, `withoutAdmin`, and `restricted` arrays at lines 155, 163, and 173 also contain `/pilot/feedback`. Those phases therefore expect both the correct `/pilot/feedback?from=...` link and an additional plain `/pilot/feedback` section link. They are currently unreachable because the retained RED stops earlier, but a correct implementation of the scoped defect will reach them and fail for this test error. Remove `/pilot/feedback` from those three section arrays while continuing to include the feedback route in each applicable `$availableRoutes` map. Capture fresh intended RED from the corrected exact source.

2. **MEDIUM — the delta specification's canonical permission mapping is incomplete and conflicts with its mapped test.** `openspec/changes/fix-role-stable-sidebar/specs/runtime/role-stable-sidebar/spec.md` says the system SHALL use “one canonical permission mapping” and enumerates objects, construction control, OTIZ, and admin, but omits `installers.read` → `/pilot/installers`. The verification input and test explicitly include the standalone installer surface and both presence and absence behavior for that permission, and the inherited `specs/YII2-MAIN-NAVIGATION-001.md` also defines it. Add the installer mapping to the normative delta requirement so the test expectation is independently grounded in the reviewed contract rather than only an inherited artifact that the verification mapping does not cite.

## Assessment

The selected real HTTP/semantic-DOM seam is appropriate and the retained failure is sensitive to the reported screen-dependent membership defect. The test otherwise exercises ordered membership and labels, nullable/current markers, repeated reads, exact persisted-fact preservation, removed permissions, direct-route denials, guest redirect, and preservation of OTIZ internal navigation. Fixture setup is isolated and deterministic. The design records persistence/schema/replay and adjacent-shell impacts adequately for this bounded presentation correction. Findings above are the complete blocking list for the submitted source.

## Required changes

Correct both findings, regenerate the source-bound verification package as required by changed spec/test inputs, retain fresh intended-RED evidence, and resubmit the complete corrected delta for independent Gate 3 review before Gate 4 implementation.

---

## Correction rereview — 2026-09-19

- Corrected candidate source: `469c404cb010120f6a52d35882d76b83396fea69135352757f973c6393267016` over base `62d027d54af7a01a1da300eda2901ba68b2bab20`.
- Corrected reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T135153Z-60091320ec/package.json`; verification-plan SHA-256 `e5ae5bb5dff56d7420f705dc412e4ca645fa578cd3d14e5ba3888a085b4e659a`.
- Reconstructible snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T135153Z-60091320ec/snapshot/source.patch`, SHA-256 `1c5b4190889150922562081a49311fcefd56982716b9ba21eaf1b3b7de3f5ecf`.
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T135153Z-60091320ec/delta.patch`, SHA-256 `0b8dd41ac4141f22e6517eee800de49dbc357523b775cfa34b55b2eeda4ea1aa`.
- Reviewer independence is unchanged; no production implementation was reviewed.
- Final verdict: `APPROVED`.

### Prior findings disposition

1. **Resolved.** The three restricted permission arrays now contain section links only. Each corresponding available-route selection separately adds `/pilot/feedback`, so the helper expects exactly one route-specific feedback link while still exercising the nested feedback screen in the same permission-equivalent matrix. The impossible duplicate/plain feedback expectation is gone without reducing route coverage.
2. **Resolved.** The normative canonical mapping now explicitly includes `installers.read` for the installer navigation item, aligning the delta contract, inherited navigation contract, verification mapping, and positive/negative installer assertions.

### Corrected RED evidence

The refreshed retained command `php tests/Yii2/yii2_main_navigation_001_test.php`, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789825890408446000-6423bb20bafc4ac28a4c5486fb4f895c.json`, is bound to corrected source `469c404cb010120f6a52d35882d76b83396fea69135352757f973c6393267016` and corrected test blob `ea23e05c367954d33d75a637128f73ea3e23d3aa3cc6cd1064b447c87dc1e980`. It reaches the real HTTP/semantic-DOM assertion and fails on `/pilot/installers`: expected permitted `ОТиЗ`, actual item absent. The failure is the intended screen-dependent membership defect, not a setup failure.

### Complete corrected-candidate assessment

No remaining findings. The corrected contract and test are traceable through the refreshed verification plan; expected membership, labels, order, current marker, feedback return path, permission removals, direct denial outcomes, guest redirect, repeated-read determinism, persisted-fact preservation, standalone installer navigation, nested feedback navigation, and OTIZ internal-navigation preservation are covered at the public HTTP/DOM seam. Expectations are independently derived from the permission mapping, fixtures are isolated, and the correction does not weaken sensitivity or expand production scope. Gate 4 may proceed against this reviewed source; later specification or test changes require delta review.
