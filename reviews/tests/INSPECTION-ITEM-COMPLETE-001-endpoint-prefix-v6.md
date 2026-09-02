# INSPECTION-ITEM-COMPLETE-001 — independent endpoint/prefix Gate 3 rereview v6

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, evidence, specification or production)  
Mission: `TEST-USER-READY`  
Verdict: `APPROVED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved executable spec: SHA-256
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Endpoint admission/cleanup test v6: SHA-256
  `5ea4e54e6221e4dbcb7f576147787be4035251e5f5858dd6f38c13e8e470b32b`.
- Endpoint cleanup evidence v6: SHA-256
  `5c5b6a4232d0f0860b8acb66863742ef9d4d922f47e3e1ff6746022bf5ff7a11`.
- Unchanged prefix test: SHA-256
  `1e8b7f4a58a1a34d86923cf74cf8160cbb7908eec7b98c179043635feb70b04e`.
- Prior v4 review: SHA-256
  `af9c504461b4360d9f0d7288bea7d1ae7e0a6d5897cfd3bd25b49962d76fda5d`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.

No production or test file was edited by this review.

## Independent reproduction and cleanup

With a healthy isolated Compose database, both syntax checks passed. I ran:

```sh
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_prefix_validation_test.php
```

Endpoint v6 reproduced the intended behavior RED after its guarded cleanup and
absence probes:

```text
PRIMARY: TestFailure: Admitted malformed item maps to HTTP 422.
Expected: 422
Actual: 403
RED_ASSERTION: expected failing behavior observed
```

No `CLEANUP` section was present, proving the test's accumulated cleanup list
was empty. Prefix reproduced the unchanged `bad-prefix` RED. Both runner
commands exited `0` under the RED wrapper.

I additionally found no exact `t_iea_%` database, `.test-artifacts/iea-*` root
or owned PHP router after the endpoint run. Compose
`down -v --remove-orphans` removed the container, volume and network; final
Compose `ps --all` was empty.

## Final finding closure

### EP-03 — closed

- The primary body throwable is captured before cleanup rather than escaping
  into `finally`.
- Server stop, runtime DB close, exact database drop, admin close, recursive
  root removal and all three absence probes are individually guarded. Each
  failure contributes to one cleanup list, so later cleanup still runs.
- The single final `TestFailure` preserves the primary class/message and appends
  all cleanup findings. With no primary, any cleanup finding still produces a
  non-null failure. The injected cleanup-only assertion makes this
  verdict-affecting behavior sensitive rather than relying on stderr parsing.
- TERM and KILL polling are bounded. `proc_close` is reached only after
  confirmed non-running status; otherwise a cleanup finding is returned without
  entering an unbounded reap.
- A forced long-running child and nested partial artifact tree exercise bounded
  reap/removal. Starting the real router resets confirmed-reaped state, and the
  final router probe requires that state even when `posix_kill` is unavailable;
  when available, exact PID absence is also checked.
- Exact database and artifact-root absence are asserted before the final verdict.
  Cleanup cannot silently print a warning and leave an otherwise-green exit.

The primary-preserving aggregate is also directly evident in the reproduced
RED: the original expected/actual admission diagnostic survives the cleanup
phase unchanged under the `PRIMARY` prefix.

## Reconfirmed prior closures

- `EP-01`: production TCP/HTTP obtains real page session/CSRF, sends the actual
  malformed-item POST, requires exact 422/rejected/revision-zero/projection-zero,
  preserves non-item 403 and requires sync-context 200/revision-zero.
- `EP-02`: exact `SHOW CREATE TABLE`, all ordered rows of the four v8 evidence
  tables and all owned artifact hashes remain identical across rejected/read-
  only probes.
- `PX-01`: `bad-prefix`, 26 ASCII bytes and non-ASCII each require
  `InvalidArgumentException` with an unconnected handle, preserving the
  canonical grammar and pre-DB ordering.
- The fixture independently distinguishes capability-only actor `7301` from
  assigned engineer `7302`; it grants no `checklist.edit` permission and uses a
  coherent canonical v1-v8 case/order/template/revision setup.

## Gate decision

EP-01, EP-02, EP-03 and PX-01 are closed. Both REDs are deterministic public-
boundary failures for the intended missing behaviors, their expectations are
independent of production policy helpers, and normal/failure cleanup is bounded
and verdict-sensitive. Endpoint/prefix Gate 3 v6 is `APPROVED` for the exact
hashes above. Gate 4 may implement the focused endpoint admission and prefix
validation changes without modifying these tests.
