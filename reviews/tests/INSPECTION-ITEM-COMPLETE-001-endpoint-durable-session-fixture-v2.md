# Independent Gate 3 rereview — durable endpoint session fixture v2

- Date: 2026-09-04
- Reviewer: fresh separately tasked agent `/root/checklist_session_fixture_gate3_v2`
- Test/correction author: not this reviewer
- Reviewed commit: `684210b0bbca62fa79b75bc39532d6cc11d7bd15`
- Prior changes-requested review: `e9fbc32cd587cfd12be90172d2d40fecc80054f4`
- Public seam: raw HTTP through `public/router.php`, the real checklist route,
  and the configured durable session owner
- Verdict: **APPROVED**

The reviewer did not edit the reviewed test, specification, evidence, or
production code. This append-only review record is the reviewer's only change.

## Review result

V2 closes both prior findings. `FMONITOR_SESSION_STATE_ROOT` is now the exact
task-owned root, so the canonical managed path and the observer agree on
`<root>/sessions/<unique-instance>`. The observer requires `sessions` and the
managed instance to be current-euid, non-symlink directories with mode 0700,
and requires `sessions` to contain that instance and no sibling.

Every managed entry is enumerated by `scandir` and inspected with `lstat`.
Only current-euid, non-symlink regular files with mode 0600 and one of the four
canonical committed, lock, stage, or revoked filename grammars are accepted.
The adversarial fixtures independently demonstrate rejection of an unexpected
filename, unexpected directory, dangling/file/directory symlinks, FIFO, Unix
socket, and a symlink path escape. Each case must be rejected before cleanup,
and each task-owned probe root must then be absent.

This closes DSF-01: session mutation is observed only in the one exact managed
instance; a sibling or invented descendant makes the snapshot fail. It closes
DSF-02: non-regular entries and symlinks are no longer filtered out by a
regular-file-only iterator. The symlink-first recursive cleanup does not follow
the adversarial links and removes only roots created by this test.

The four checklist table schema/row snapshots and all non-session artifact
hashes remain byte-identical. The exact managed instance starts empty and may
gain only canonical session files. The diff from `2ae633c` changes only this
ownership oracle, its sensitivity/cleanup support, the corrected environment
wiring, and append-only evidence; the endpoint's 422/403, rejection payload,
projection, non-item denial, and sync-context product assertions are unchanged.

## Independent reproduction

On exact `684210b0bbca62fa79b75bc39532d6cc11d7bd15`:

```text
$ php -l tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
No syntax errors detected in tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php

$ tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
PRIMARY: TestFailure: Admitted malformed item maps to HTTP 422.
Expected: 422
Actual: 403
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php

$ git diff --check 2ae633c4db26608a7b6d05a82c5de933f7befe81..684210b0bbca62fa79b75bc39532d6cc11d7bd15
PASS (no output)
```

Reaching the 422/403 assertion proves that the root-layout checks, all eight
sensitivity cases, initial empty-instance assertion, GET/CSRF flow, durable
session observation, and the unchanged table/artifact assertions passed first.
Independent post-run probes found no `t_iea_%` database, no
`.test-artifacts/iea-*` root, and no `/home/antropophag/code/iea-socket-*`.

## Exact hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
a2e376531a4db9364cc16636388d9bc8285bd54b06d16ddd8b68edd6f0818496  reviews/tests/PILOT-SESSION-STORAGE-001-local-auth-lifecycle-v1.md
1abbf879022d43d2e85bc4bfcd1ae8845fe46c09c8c7768fb9e8c4f0013c354e  reviews/tests/PILOT-SESSION-STORAGE-001-architecture-ratchet-v2.md
c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb  specs/INSPECTION-ITEM-COMPLETE-001.md
32f6291c0a56c6e472d75ecac76688545d0094fb0eaec27abbe6ff942b585bab  tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
f69e0510b1cba615a220ed31f70dafc1b8f8ed5a7b828a165b4450e4eede4bf8  docs/operations/inspection-item-endpoint-durable-session-fixture-red-correction-v2-2026-09-04.md
8d6e60eb15429f3824abf3ccfed7d0aaa1d1fdaa90a03487a76cef4d464b92df  reviews/tests/INSPECTION-ITEM-COMPLETE-001-endpoint-durable-session-fixture-v1.md
```

Gate 3 is approved for this exact test/evidence state. Gate 4 may proceed from
the preserved endpoint-admission RED without changing the approved product
expectations.
