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
