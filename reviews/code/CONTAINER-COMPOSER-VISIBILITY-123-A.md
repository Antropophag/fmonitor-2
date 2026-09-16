# Gate 5 code review — CONTAINER-COMPOSER-VISIBILITY-123-A

- Reviewer: independent `gpt-5.6-sol / low` agent `/root/issue123_gate5`; authored none of the reviewed specification, tests, implementation, or evidence.
- Review date: 2026-09-16.
- Base: `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf`.
- Reviewed commit: `baf31724b67f30a35e9e1289551eab7b28a839bc`.
- Exact candidate source: `dd04b8d312433412c5a9f4dfca167a954de9836510ae0c74f48b52f5d82bcb23`; executable source: `d4132fa982dd6c4c46cfc725c0d28defd7a0f80afe3fa85091d52011ce5b0b25`.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T075226Z-d8a438a22d/package.json`; reconstructible snapshot patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.
- Normative specification: `specs/CONTAINER-COMPOSER-VISIBILITY-123-A.md`, SHA-256 `70c7228d4e2cfb992c7c1611526e8cafdc7ed0be2c8952ad3b85a07968fa563c`.
- Gate 3 record: `reviews/tests/CONTAINER-COMPOSER-VISIBILITY-123-A.md`, SHA-256 `d328a5fc5fc4e1f221d0884186f921701a00c1da0882406be461ce6d4df4c1ed`, final delta verdict `APPROVED`.
- Verdict: `CHANGES_REQUESTED`.

## Verification evidence reviewed

The immutable package contains four source-matched GREEN records, all bound to the candidate and executable identities above:

- A–O public-route regression: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789544630889351000-fa2646dac6044280b3219775d023a0a6.json`, 8/8 tests GREEN in 354.050s. The recorded clean-worktree runs report `setup_failures_before_behavior: 0`; representative command durations are 0.522–1.142s.
- Governance/profile compatibility: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789544991676895000-809735cdf52440c6911741c48316104a.json`, GREEN in 100.172s.
- Exact-source/change-verification regression: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789545098384568000-9c38fa2eb4f743939a2f498c68c5a02b.json`, GREEN in 21.203s.
- Runtime-storage boundary: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789545123963095000-6b9822c9189c4a74a4a7db1df426a481.json`, GREEN in 2.373s.

No local full `make test` or `make verify` was run. Exact-source CI remains pending and is not treated as GREEN.

## Standards

Apart from the blocking exact-source boundary below, the implementation is narrowly located in the existing profile launcher and image recipe. It reuses `review-source.py capture/restore` and `source_details().executable_digest`, preserves additions/deletions/modes, hashes lock/recipe/runtime inputs into image identity, verifies image labels before execution, and runs the composed root read-only. No new manager, profile, snapshot format, product runtime, deployment Compose, dependency update, or application behavior is introduced.

The `.local` mount is not a dependency or project-source fallback: it is the existing explicit per-worktree artifact area, is mounted only when already present, otherwise becomes a disposable tmpfs, and cannot create host `vendor`, `node_modules`, or `.venv`. `/tmp` is separately disposable. This is consistent with the owner-approved writable artifact/runtime exception and worktree isolation.

No additional documented-standard violation or actionable Fowler-baseline smell was found. `git diff --check` is clean.

## Specification

The source-matched tests substantiate the main composition: candidate-only project bytes load from `/workspace`, Composer/Yii load from immutable `/workspace/vendor`, host stale vendor is ignored, corrupt/missing dependencies and changed lock fail before behavior, source and vendor are read-only, two worktrees remain isolated, and governance/integration/browser share the seam. Dockerfile-specific ignore rules prevent host dependency and secret trees from entering the build context. The image tag/build args/labels cover executable source, manifests, lock, recipe, runtime pins, profile, and ignore policy.

One exact-candidate entry-point leak remains.

## Findings

### F1 — Blocking — integration/browser network composition reads live host source after freeze

- Location: `tools/delivery/run-in-profile:98-110`.
- CCV123A-01 requires host changes after freeze not to influence execution, and CCV123A-05 requires integration/browser network/service ownership to be preserved for the exact candidate. The launcher correctly restores the frozen candidate into `$materialized`, but then runs `docker compose -f compose.test.yaml config` after `cd "$root"`. Therefore a change to the live host `compose.test.yaml` after `FMONITOR_EXECUTION_SNAPSHOT` was created can change the selected network and DB environment (or make the profile omit them) while the container source, source digest, and image label still claim the frozen candidate.
- Matrix D currently mutates only a project marker and exercises the governance profile, so all GREEN records can pass without observing this leak. This is a plausible stale/dirty-host regression at an existing integration/browser execution entry point, not an optional broader audit.
- Correction: resolve the profile network configuration from the restored frozen candidate (for example, run the existing Compose config query with `$materialized` as its project directory/source) while preserving the existing fixed Compose project/network semantics. Add a bounded public-route regression that freezes a candidate, mutates the live host Compose input afterward, and proves integration/browser selection remains governed by the frozen snapshot. Because that changes the approved executable test, refresh the plan/RED and obtain the required Gate 3 delta approval before returning to Gate 5.

## Verdict

`CHANGES_REQUESTED`

Gate 5 does not pass for exact candidate source `dd04b8d312433412c5a9f4dfca167a954de9836510ae0c74f48b52f5d82bcb23`. Correct F1, renew the changed-test review, run the affected focused evidence on a fresh exact-source package, and request independent Gate 5 rereview. Standards: one hard exact-source boundary finding; Spec: one blocking missing invariant at integration/browser entry points.

---

## Gate 5 correction rereview — frozen profile Compose source

- Reviewer independence is unchanged; this reviewer authored neither the test correction, Gate 3 delta review, implementation correction, nor verification evidence.
- Corrected commit: `a88a0f43d1b03002f094983a7d0a6270dfad30de`.
- Exact candidate source: `1daed97dd8dce0a37abcc058258b723c88e8b33deaac5fc31afc5856e36e2699`; executable source: `68cbbb96e22d1d17e33d2e8501b638febc5855bef83780981a7e06fb2e0235ae`.
- Delta package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T081411Z-403c362167/package.json`; previous snapshot is retained; correction delta SHA-256 `dd229e827fe9d22a394120a03d814ff84638c72e191d208094b42468d04326ca`; corrected snapshot patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.
- Renewed Gate 3: test commit `32c05957eff0bb7efe09440445b455d70e6fc5ef`, independent approval commit `36314513`, final verdict `APPROVED`.
- Correction implementation: commit `a88a0f43d1b03002f094983a7d0a6270dfad30de`.
- Correction verdict: `APPROVED`.

### F1 disposition

F1 is `RESOLVED`. `tools/delivery/run-in-profile` now changes directory to the restored `$materialized` candidate before evaluating `compose.test.yaml` for both integration and browser. The network name and resulting DB environment therefore derive from the same frozen source used to build and execute `/workspace`, not from later live-host bytes. The fixed Compose project name still resolves the established `fmonitor2-test_default` network, so existing service ownership is preserved.

The renewed test freezes a candidate containing an independent `frozen-compose` marker, mutates the live checkout to `later-host-compose`, and invokes both affected public profiles through an explicit existing snapshot. A bounded Docker shim observes the actual `-f` input before delegating unchanged argv to the real CLI. Its pre-implementation RED recorded both profiles consuming the later host marker; the corrected exact-source GREEN records both consuming only the frozen marker. This catches the original plausible regression without changing profile behavior or introducing a private test seam.

### Corrected evidence

All records bind candidate `1daed97dd8dce0a37abcc058258b723c88e8b33deaac5fc31afc5856e36e2699` and executable source `68cbbb96e22d1d17e33d2e8501b638febc5855bef83780981a7e06fb2e0235ae`:

- A–O public-route regression: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789546180836569000-0eef30e87e134e41bdccb3449a5a5ad2.json`, 8/8 GREEN in 87.882s; setup failures before Yii behavior `0/N`; measured command durations 0.416–0.574s for the reported clean-worktree cold-ish/warm runs.
- Governance/profile compatibility: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789546278873739000-c561bd2fbba54a9e800bae8416bdf04e.json`, GREEN in 100.668s.
- Exact-source/change-verification regression: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789546389098807000-5ebbb335409b4ca3b2a7c135e504ea93.json`, GREEN in 21.190s.
- Runtime-storage boundary: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789546415603776000-eb5ba0dd3d654b659ee3861bcd5f62b2.json`, GREEN in 2.050s.

No local full suite or `rapid-pilot` access occurred. Exact-source CI remains pending and is not implied by this approval.

### Complete rereview conclusion

No new standards, specification, security, isolation, fail-closed, lock/source identity, artifact-boundary, or maintainability finding was introduced by the one-line implementation correction and bounded test delta. The complete candidate retains read-only exact source, container-managed locked Composer dependencies, stale-host dependency exclusion, missing/corrupt dependency failure, host dependency cleanliness, per-worktree artifact ownership, candidate isolation, profile compatibility, and source/lock invalidation.

Gate 5 is `APPROVED` for exact candidate source `1daed97dd8dce0a37abcc058258b723c88e8b33deaac5fc31afc5856e36e2699`. Publication remains contingent on the repository workflow and one authoritative exact-source CI run; merge, deployment, and settings are not approved by this review.

---

## Post-main-merge Gate 5 delta review

- Review scope: merge commit `b5d871cd9ab05f46301717d296fc6a26dfc753c6` against updated main `5e746a50b018bb6a353bd27d774684d7ab97d784`.
- Exact candidate source: `58a50f569c654104f54c9cb4876144fd897ee376b5e15ae80934af8fc12b75a4`; executable source: `fb644ad8c20313c1aef46a4f504ab1ba1ffc958045e5abf5dddfd94fa377c0d6`.
- Delta package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T082802Z-068b937009/package.json`.
- Verdict: `CHANGES_REQUESTED`.

The sole textual conflict in `docs/operations/current-delivery-goal.md` was resolved by retaining the active #123-A pointer, and `tools/verification/suites.tsv` correctly retains both additive suite entries. The #123-A source/dependency implementation itself did not conflict. The package contains source-matched GREEN records for A–O, governance compatibility, change verification, and runtime storage. Those four checks do not exercise the newly merged #123-B structured-wrapper parser against the #123-A launcher.

### P1 — Blocking — merged #123-B wrapper contract rejects the #123-A result schema

- Locations: `tools/delivery/harness.py:241-278`, `tools/delivery/run-in-profile:146-154`, and `tests/Verification/delivery_harness_001_test.py:599-699`.
- Main's new `intended_red_observation()` accepts a `RUN_IN_PROFILE_RESULT` only when its keys are exactly `{argv,duration_seconds,exit_code,git_sha,image_digest,profile}`. The #123-A canonical launcher now correctly and necessarily adds `source_digest`. Consequently the merged parser returns `None` for a valid real #123-A result, discarding child observation and preventing a genuinely reached child marker from being classified through the #123-B contract. A direct read-only probe of `intended_red_observation()` with the seven-field canonical result returns `None`.
- The merged #123-B real-wrapper fixture is also stale relative to the #123-A launcher: its fake Docker CLI does not model the added source/lock label inspections and read-only/tmpfs run arguments. Bounded reproduction `python3 tests/Verification/delivery_harness_001_test.py Harness.test_intended_red_provenance_cases_a_to_m` fails in 2.101s at line 660 because the public launcher exits `2` instead of the expected pre-behavior `255`. Thus exact-source CI would encounter an already reproducible focused regression if publication proceeded.
- Correction: compose the two already-approved contracts without broadening classification. Teach the structured parser the canonical seven-field result and validate `source_digest` as a non-empty/exact digest field; update only the real-wrapper fake Docker fixture so it supports the #123-A build/label/read-only invocation and still exercises the same #123-B behavioral assertions. Add or retain a direct assertion that a child marker in the real seven-field wrapper is admitted while argv/metadata-only markers remain rejected. Obtain the applicable independent test-delta review, then rerun this focused test plus the four #123-A package commands on one fresh exact source.

No second post-merge finding was found. The current `CHANGES_REQUESTED` verdict is limited to P1 merge compatibility; it does not reopen the previously approved #123-A source/dependency composition or F1 correction. Exact-source CI must remain pending until the focused shared-harness regression is GREEN.

---

## P1 correction rereview

- Test delta: `00c83c4d1dfd334ba86377751ac31daf85d2824c`, independently approved at Gate 3 by commit `c85c5338`.
- Implementation correction: `60ebf58e75699944f5f1d9aa2f88eedf9ed31e69`.
- Scope: P1 only; no classification precedence, product behavior, profile composition, or broader harness policy change.
- Verdict: `APPROVED`.

P1 is `RESOLVED`. `intended_red_observation()` now requires the exact canonical seven-field `RUN_IN_PROFILE_RESULT` schema and validates `source_digest` with the same 64-lowercase-hex shape emitted by #123-A. The parser still rejects missing, malformed, extra-field, exit-mismatched, and metadata-only provenance; it strips wrapper metadata and admits only the independent child stdout/stderr observation. A direct bounded probe confirmed a valid seven-field result returns the child observation, while an invalid digest or an extra field returns `None`.

The renewed fixture models only the launcher mechanics introduced by #123-A: build args are retained for exact label responses, Composer-lock/source label inspections are answered from those actual args, read-only/tmpfs options are consumed, and the wrapper entrypoint is skipped before forwarding the unchanged child command. It neither injects an intended marker nor decides classification. The test also parses the real launcher result and independently requires its `source_digest` to be 64-hex.

Focused verification `python3 tests/Verification/delivery_harness_001_test.py Harness.test_intended_red_provenance_cases_a_to_m` is GREEN, 1/1, in 6.475s on reviewer reproduction (root evidence reported 6.331s). The formerly failing real-wrapper child-marker case now reaches `INTENDED_RED`; argv/metadata-only, setup-failure, unrelated-failure, missing/malformed provenance, direct RED, and GREEN cases retain their prior outcomes.

No new finding exists in the P1 delta. The post-main-merge Gate 5 delta is `APPROVED` at implementation commit `60ebf58e75699944f5f1d9aa2f88eedf9ed31e69`. This narrow approval restores compatibility between the already-approved #123-A result schema and merged #123-B provenance parser; publication still requires final exact-source CI under the repository workflow.
