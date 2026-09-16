# Code review: INTENDED-RED-OBSERVATION-PROVENANCE-001

- Reviewer: Codex independent reviewer, `gpt-5.6-sol` / low
- Implementation author: separate executor agent, per the owner-authorized issue #123 slice B assignment
- Verdict: `APPROVED`
- Specification: `specs/INTENDED-RED-OBSERVATION-PROVENANCE-001.md`
- OpenSpec change: `openspec/changes/reject-wrapper-only-intended-red/`
- Verification lane: `CRITICAL`; required reviews: Gate 3 and final
- Reviewed source: base `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf` plus retained binary patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T025452Z-9ad9fe45a0/snapshot/source.patch`, SHA-256 `d5f749ddc6b08ca83e5ea04d072d3d0fcc003a0e8287c07c8efd26d497b373cb`
- Snapshot manifest: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T025452Z-9ad9fe45a0/snapshot/manifest.json`, SHA-256 `6c7361d8c2414d2516c1c3c89d8b672b341359c2503eaa4c06aac389e54f1359`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T025452Z-9ad9fe45a0/package.json`; candidate source `0d3d6ee137061dd5189ab365848b2c73cbef59cc4c149a4abcb6694358d008d6`; executable source `a55624781cf642786ca5b4bf18be1a225984c2e5bb9fa1f20d945db91bcf8da4`
- Gate 3 input: `reviews/tests/INTENDED-RED-OBSERVATION-PROVENANCE-001.md`, final corrected-test verdict `APPROVED`

## Findings

None.

## Review

The implementation conforms to the A-M contract. Direct commands retain their existing stdout/stderr oracle channel. The supported profile wrapper captures child stdout and stderr separately, re-emits the raw bytes, and appends a structured envelope containing exact byte counts. The harness validates that envelope and restricts intended-RED admission to the child observation prefix. Missing, malformed, or inconsistent provenance fails closed to the ordinary non-RED classification. Wrapper argv, command echo, serialized metadata, and diagnostics therefore cannot alone admit `INTENDED_RED`.

Control-marker priority, normalized exit handling, `raw_child_returncode`, `command_verdict`, and retained stdout/stderr paths remain unchanged. The wrapper still exits with the child status. Its observation directory is created with `mktemp -d`, all expansions used by cleanup are quoted, and the `EXIT` trap removes only that resolved private directory. Child output files are private beneath the temporary directory and are removed after their bytes have been replayed and measured.

The change stays within the declared tooling boundary: `tools/delivery/harness.py`, `tools/delivery/run-in-profile`, their focused test, and delivery/specification records. It adds no product/domain state, authorization, audit facts, dependency hard-code, evidence redesign, FAST/T06/T03 behavior, `rapid-pilot/` work, or slice A/C behavior.

The A-M test is sensitive to plausible regressions: it distinguishes argv-only, command-echo-only, wrapper-metadata-only, absent/malformed provenance, setup-before-child, unrelated assertion, green, direct intended RED, and wrapped legitimate-oracle cases. It also checks the child-not-reached sentinel, raw wrapper exit/verdict, full retained streams, and the public prepare/run machine-readable results. The existing lifecycle fixture remains in the same focused run.

## Verification evidence

- Prepared GREEN record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789527193204376000-25614273611e421890d3fe54b6564314.json`; outcome and command verdict `GREEN`; raw child return code `0`; executable source `a55624781cf642786ca5b4bf18be1a225984c2e5bb9fa1f20d945db91bcf8da4`; 26 tests passed in 40.859 s. The record retains the complete 2,182-byte stderr log externally.
- Reviewer rerun: `python3 tests/Verification/delivery_harness_001_test.py` — 26 tests passed in 42.756 s.
- Reviewer rerun: `python3 tests/Verification/change_verification_001_test.py` — 18 tests passed in 19.555 s.
- Reviewer rerun: `python3 tests/Verification/architecture_guard_001_test.py` — 59 tests passed in 15.592 s.
- Reviewer syntax checks: `bash -n tools/delivery/run-in-profile` and `python3 -m py_compile tools/delivery/harness.py tests/Verification/delivery_harness_001_test.py` — passed.
- `git diff --check 11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf` — passed.
- Snapshot restore succeeded in detached worktree `/private/tmp/fmonitor-123-gate5-restore.OoGP4j`; reviewed implementation, test, and normative-spec bytes matched the submitted worktree.

No local full `make test` or `make verify` was run. The required one exact-source GitHub CI run remains a post-review delivery step and is not represented here as GREEN.

## Decision

`APPROVED` for Gate 5 on the exact reconstructible source above. The review record itself is the only file added after snapshot capture; any later code, test, specification, or lifecycle change requires delta review before publication.

---

## Gate 5 delta review — CI compatibility correction

- Reviewer: Codex independent reviewer, `gpt-5.6-sol` / low
- Authorship history: root agent authored the normative contract and executable tests under the owner-authorized assignment; the original Gate 4 implementation and this CI correction were authored by separately tasked executor agents; this reviewer authored the Gate 5 decisions and did not author production code or tests
- Prior reviewed source: package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T025452Z-9ad9fe45a0/package.json`, verdict `APPROVED` above
- Correction base: committed candidate `3c5b9a06fc4be1375f967c4ab5045ed9c651db84`
- Reviewed correction source: base above plus retained binary patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T032415Z-bf265cc912/snapshot/source.patch`, SHA-256 `523737a94986dfb0c459defdbe00cc7115d5b789a622d8f0d83458c1aaa53fa8`
- Snapshot manifest: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T032415Z-bf265cc912/snapshot/manifest.json`, SHA-256 `24d6fc17eae65f011b08f191d28db7d73e2a813234da4c87300674f283f01e62`
- Delta against the prior approved snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T032415Z-bf265cc912/delta.patch`, SHA-256 `7e93a0c448b03cdcf89bdcda124b05e24624ce78134d2d76364c6697540f1fe3`
- Prepared package candidate source: `91549dcac3a3bd7098926a20f69824fe686f5a0ad9db879295d8383694a68801`; executable source: `5be3d53b9ddca0ab34daeb738ce5b8cc2e304ef94dcaa051ffc5b7afbf0cb3c9`
- Verdict: `APPROVED`

### Findings

None.

### Delta assessment

The correction removes the incompatible seventh `observation` key from `RUN_IN_PROFILE_RESULT` and restores the established six-key schema exactly: `argv`, `duration_seconds`, `exit_code`, `git_sha`, `image_digest`, and `profile`. `tools/delivery/run-in-profile` again streams child stdout/stderr directly and preserves its existing raw behavior and exit status.

The harness now derives the permitted child observation without changing that public wrapper payload. A structured result is accepted only when it is a terminal, valid JSON object with exactly the six legacy keys, correctly typed values, a non-negative duration, and an `exit_code` equal to the raw normalized child exit. Missing, malformed, extra-key, non-terminal, or exit-mismatched provenance fails closed for `INTENDED_RED`. For a valid envelope, protocol command/result lines are excluded from the preceding stderr candidate, while ordinary child stdout and stderr remain eligible. Thus serialized argv and wrapper protocol diagnostics cannot alone admit the expected marker, but the legitimate direct and wrapped oracle paths remain admitted.

The delta is confined to `tools/delivery/harness.py` and `tools/delivery/run-in-profile`; the package delta also carries the already approved review record and lifecycle checkbox. It introduces no product/domain, persistence, dependency, deployment, FAST/T06/T03, `rapid-pilot/`, or slice A/C behavior. Removal of the temporary observation directory also removes the new cleanup path from the original candidate, restoring the wrapper's prior streaming implementation rather than adding another shell lifecycle.

The unchanged A-M focused test exercises the real six-key wrapper for metadata-only rejection and legitimate wrapped-oracle admission, plus fail-closed missing/malformed envelopes, setup-before-child, direct compatibility, raw exit/verdict, and retained evidence. The pre-existing `quality_graph_ci_setup_001_test.php` independently asserts the exact six-key legacy payload and its field types.

### Focused evidence

- Prepared GREEN record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789528959405343000-926feaf3e193436bb14883ca1799049d.json`; source `91549dcac3a3bd7098926a20f69824fe686f5a0ad9db879295d8383694a68801`; executable source `5be3d53b9ddca0ab34daeb738ce5b8cc2e304ef94dcaa051ffc5b7afbf0cb3c9`; outcome/command verdict `GREEN`; raw child return code `0`; 26 tests passed in 41.256 s.
- Reviewer rerun: `python3 tests/Verification/delivery_harness_001_test.py` — 26 tests passed in 40.395 s.
- Reviewer compatibility check: `php tests/Verification/quality_graph_ci_setup_001_test.php` — `QUALITY-GRAPH-CI-SETUP-001 PASSED`.
- Reviewer syntax checks: `bash -n tools/delivery/run-in-profile` and `python3 -m py_compile tools/delivery/harness.py` — passed.
- `git diff --check 3c5b9a06fc4be1375f967c4ab5045ed9c651db84` — passed.

The recorded GitHub CI status is still `FAILURE` for the pre-correction committed head. This delta approval does not relabel that run or claim exact-source CI GREEN; the corrected source requires the authorized exact-source CI rerun before PR-ready handoff.

### Delta decision

`APPROVED` for Gate 5 on candidate source `91549dcac3a3bd7098926a20f69824fe686f5a0ad9db879295d8383694a68801`. Any later production, test, contract, or lifecycle change beyond this appended review record requires another source-bound delta review.
