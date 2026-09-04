# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — factory/worker RED correction v2

- Recorded: `2026-09-04T06:33:00+03:00`
- Predecessor RED commit: `a65447e`
- Gate 3 record: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-factory-worker-v5.md`
- Gate 3 verdict: `CHANGES_REQUESTED`
- Result: **INTENDED RED preserved**

The reviewer found that the first correction checked production-factory and
worker behavior as separable contours. The verifier now cross-checks them: an
accepted/replayed terminal result emitted by a real-mode worker must be visible
as an exact `REPLAYED` result through the already constructed production
application before a deliberately unreadable replacement stream is touched.
Thus a worker-local SQL flow cannot pass merely by returning the expected IPC
DTO; it must share the production repository/request precedence, while the
evidence reader independently verifies owned blob, event and audit outcomes.

Re-run with the isolated test-contour credential again exits `255` at the same
earlier intended boundary:

```text
INTENDED_RED: valid MariaDB/storage production config must construct submitAssignmentOrderOriginal; factory threw LogicException
```

`php -l` passes and `git diff --check` has no output. No production file changed.
