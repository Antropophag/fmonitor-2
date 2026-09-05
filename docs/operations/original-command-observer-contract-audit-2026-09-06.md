# Original command — lifecycle/storage observer contract audit

- Date: `2026-09-06`
- Reviewer: separately tasked read-only agent `/root/registry_engine_gate1`
- Reviewed source baseline: `3583ef866be64765017b995f80d4d1c64b8db695` (current command source bytes unchanged)
- Scope: existing lifecycle/storage observation and replay cleanup contracts only
- Verdict: **MULTIPLE CONCRETE MISMATCHES; NEW GATED CORRECTION REQUIRED**

No code, test or specification was edited. No test, database, filesystem fixture
or external system was run. This append-only audit is separate from command-v3
and does not alter its findings.

## Normative event contract

The active parent specification defines four lifecycle events:

```text
AFTER_REQUEST_MISS_BEFORE_STREAM
AFTER_FINGERPRINT_MISS_BEFORE_CAS
AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT
AFTER_COMMIT_BEFORE_RETURN
```

It defines the command storage events `STAGE_BEGIN`, `STAGE_WRITE`, `STAGE_DONE`,
`ABORT_BEGIN`, `ABORT_DONE`, `STAGE_CLOSE`, `FINALIZE_BEGIN` and
`FINALIZE_DONE`. Section 10 requires `BEGIN` immediately before its primitive and
`DONE` only after durable success. It also states that the storage observer
receives exact ordered events for operations actually attempted.

The request-301 transcript fixes:

```text
... clock → lifecycle AFTER_REQUEST_MISS_BEFORE_STREAM →
stage_begin → stream_read → stage_write → ... → stage_done → inspector →
abort_begin/throw → safe_log → stage_close → stream_close → audit
```

The exact retry transcript is only authorization plus terminal request `FOUND`.
Request replay creates no stream/storage event and does not consume the supplied
unread stream. These are executable observation requirements, not descriptive
implementation notes.

## Concrete mismatches

### OBS-01 — `AFTER_REQUEST_MISS_BEFORE_STREAM` is never emitted

After terminal-request miss, the service proceeds through an extra empty-
fingerprint availability query, composition and clock, then enters lineage/stage
work. It never calls the lifecycle observer with
`AFTER_REQUEST_MISS_BEFORE_STREAM`. The request-301 exact transcript and the
declared causal point are therefore impossible on current production bytes.

The phase must be emitted exactly once after the approved pre-stream checks and
immediately before stage/stream acquisition. Rejections or failures before that
point and terminal-request replay must emit none.

### OBS-02 — `AFTER_COMMIT_BEFORE_RETURN` is never emitted

The enum declares this lifecycle event, but the accepted path calls only the
separate delivery observer after lease release. No service source emits
`AFTER_COMMIT_BEFORE_RETURN`. A lifecycle observer cannot observe or block at
the declared post-commit causal point.

The correction contract must keep lifecycle and delivery roles distinct and fix
their exact order after durable commit and lease-release attempt. It must also
state which recovered `OUTCOME_UNKNOWN/FOUND` path emits the lifecycle phase;
tests must derive this from the active contract rather than production order.

### OBS-03 — six storage phases are absent and `STAGE_BEGIN` has wrong timing

Current service emissions are limited to:

```text
STAGE_BEGIN (after storage->beginStage returns)
STAGE_WRITE (after successful stage->write)
STAGE_DONE (after stream EOF)
```

There is no `FINALIZE_BEGIN`, `FINALIZE_DONE`, `ABORT_BEGIN`, `ABORT_DONE` or
`STAGE_CLOSE` emission anywhere in the service. The five listed names comprise
five absent event kinds; together with the pre-primitive timing failure they
leave six distinct contract failures.

`STAGE_BEGIN` is emitted after `beginStage()` has already created the stage,
contrary to the rule that BEGIN occurs immediately before the primitive. A
beginStage failure produces no begin event even though the operation was
attempted. Finalize executes without either boundary event. All cleanup paths
call abort/close without their required observation; successful abort is not
distinguished from abort failure at the event seam.

`STAGE_WRITE` and `STAGE_DONE` match the successful-path order currently
described, but do not compensate for missing operation boundaries. The adapter's
internal state mutations are not a substitute because the normative observer is
the dependency passed to the application.

### OBS-04 — same-request replay performs an operation excluded by its exact transcript

On terminal request `FOUND`, Runtime line 67 calls `stream->close()` and suppresses
its Throwable before returning replay. The normative retry transcript is only
authorization plus terminal lookup, and the supplied stream is explicitly unread.
If “unread” was intended to permit one close, the exact transcript and close
outcome are incomplete; as written, the direct close is an extra observable port
operation.

The correction must choose one exact inherited interpretation before RED:

- either retry owns and closes the supplied stream exactly once, adding that
  operation and its failure outcome to the exact transcript; or
- retry performs no stream call, matching the literal two-step transcript.

This is a technical lifecycle clarification of the existing replay behavior,
not a new product outcome. A test must use a stream spy whose read and close calls
are independently observable and prove no storage/lifecycle/ID/PDF/commit work.

### OBS-05 — accepted-fingerprint replay bypasses the required cleanup protocol

When the real post-stream fingerprint lookup returns a stored accepted result,
Runtime line 74 directly invokes `stage->abort()`, `stage->close()` and
`stream->close()` inside one broad try/catch, then returns `REPLAYED`.

This path emits none of `ABORT_BEGIN`, `ABORT_DONE` or `STAGE_CLOSE`. The first
throw skips every later cleanup operation. Abort typed `FAILED` is ignored.
Close failures are swallowed without the required safe diagnostic. It therefore
violates the exact ordered event contract, attempt-always cleanup, and existing
safe-log failure protocol even when the returned replay payload is correct.

The path must use the same bounded acquisition/cleanup owner as other
non-accepted post-stage outcomes: abort attempt, stage close and stream close
exactly once in order, exact events for each attempted/successful primitive, and
best-effort diagnostics without replacing the replay result. No finalize, ID,
commit, domain event or delivery is allowed.

## Coverage gap and bounded correction matrix

Current tests exercise selected lifecycle barriers and storage outcome/fault
behavior, but no reviewed test asserts the complete declared event sequence.
The forthcoming dynamic fingerprint test must not encode absence of the earlier
request-miss event merely because current production omits it.

One focused public application-seam correction can cover:

1. successful initial acceptance with all four applicable lifecycle points and
   `STAGE_BEGIN/WRITE/DONE/FINALIZE_BEGIN/FINALIZE_DONE/STAGE_CLOSE` in exact
   order relative to stream, finalize, commit, release and delivery;
2. invalid-PDF abort success and abort failure with exact
   `ABORT_BEGIN`, conditional `ABORT_DONE`, `STAGE_CLOSE`, later cleanup and
   terminal audit order;
3. begin/write/finalize/stage-close faults proving BEGIN-before-primitive and
   DONE-only-after-success semantics;
4. same-request replay with the clarified exact read/close ownership and zero
   storage/lifecycle/downstream work;
5. post-stream accepted-fingerprint replay with attempt-always ordered cleanup,
   exact events, diagnostics and unchanged stored result;
6. commit success, commit conflict/replay and unknown-outcome recovery, fixing
   which post-commit lifecycle/delivery phases occur and in what order.

Tests must first demonstrate RED and receive independent Gate 3 before production
changes. The implementation should centralize observation around the existing
bounded stream/stage cleanup owner rather than add test-only event emitters or a
second storage mutation path.

## Exact reviewed hashes

```text
d65470e2e1da510aa1ebe6d9cce6549b8fffcc6bf66035716623eb0cad61f890  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
8ea2b82300c157f2ca6c69b9b4eef5ab773d8e612d22f6725d4d88aa705c013f  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
0a9c81f0cd173ae1e75262bcae5e3ae88b6d662476eb284785564b5008a4456c  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
57b1e0e18f53baaf632bcb1fd7893a1370dd0a0c181d649772160cecf53a3bef  tests/InstallationProcess/assignment_order_original_upload_001_test.php
7b6297b8bed813f682db82a496dd599f17a89cea6c85aae9554300483a036b4f  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
d90866b903de139b2e83afa368847126306fbce7245be408fd19aa460bc75ffa  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-command-v3.md
```

This audit is not Gate 1, Gate 3 or Gate 5. It does not approve the combined
command and does not change the separate command-v3 fingerprint/ID/clock
findings.
