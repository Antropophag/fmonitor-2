# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v54 — Gate 5 parser/safe-log RED evidence

- Date: `2026-09-05`
- RED author: separately tasked agent `/root/command_gate5_red_final`
- Reviewed implementation SHA: `12fee6f0389cf1e4cd82d174b15d579689efd29f`
- Gate 5 finding record: `eea7a12ed42cd1a4baa91ea8762987ac13768643`
- Repository HEAD before RED edit: `eea7a12ed42cd1a4baa91ea8762987ac13768643`; this factual review-record HEAD supersedes the requested implementation checkpoint without changing the reviewed implementation identity.
- Scope: executable regressions only; production, executable specification, OpenSpec artifacts and tasks were not edited.

## Exact inputs

```text
0f7fbfcdf121bb1dcd75e2ee1b4823699621bd5ae256973923d427cf5263cb97  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
fce7a7bd6e75d6d714241d054fdb46751651f60abf21e5a845c670dbb899943e  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
0e7f5fd84c6974b411a5f8a869fb270e663cae99fdb1017c15e585218dec8bd4  openspec/changes/replace-pilot-registration-with-original-upload/design.md
```

## Parser intended RED

Command:

```text
php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
```

The unchanged valid xref-stream positive control reaches `PASSIVE_PDF`. The four independent corruptions then produce this exact classified mismatch and exit `255`:

```text
Expected: classic_identity=INVALID_PDF, stream_identity=INVALID_PDF, stream_bounds=INVALID_PDF, stream_type=INVALID_PDF
Actual:   classic_identity=PASSIVE_PDF, stream_identity=PASSIVE_PDF, stream_bounds=PASSIVE_PDF, stream_type=PASSIVE_PDF
EXIT=255
```

The classic mutation points object 1 to the valid object-2 offset `0000000058`. The xref-stream mutations replace only object 1's exact nine-byte `/W [1 4 4]` entry with, respectively, the valid offset of object 2, `strlen(pdf)+1`, and unsupported type `3`. Expected values are independently constructed from the approved fail-closed bounds/type/identity policy and do not call parser internals.

## Production safe-log intended RED

Command:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
```

Exact classified failures and exit:

```text
production factory cleanup probe: exact safe log append absent
production factory safe log missing: accepted
production factory safe log invalid mode: accepted
production factory safe log discard device: accepted
EXIT=255
```

The public command seam receives an invalid PDF through a stream whose real `close()` throws. The selected result remains exact `REJECTED/INVALID_PDF/non-retryable`; the independent oracle requires one append-only JSONL record with request-derived correlation, event `ASSIGNMENT_ORDER_ORIGINAL_STREAM_CLOSE_FAILED`, sole safe field `phase=stream_close`, and sequence `1`. Filename, bytes, path and exception text are deliberately sensitive sentinels and are absent from the oracle. The existing owned `0600` file is the positive production-config path. Missing, `0644`, and `/dev/null` targets must fail closed.

## Bounded cleanup

The MariaDB test uses a random isolated database name and task-owned temporary root. Its unconditional `finally` closes the connection, drops only that exact database, and recursively removes only the exact random task root. Both failing runs completed that cleanup. `php -l` passed for both edited tests and `git diff --check` exited `0`.

This evidence is RED only. It is not Gate 3 approval, GREEN evidence, or permission to edit production.
