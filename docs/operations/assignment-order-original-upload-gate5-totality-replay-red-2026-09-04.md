# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — totality/replay RED

- Baseline: `2b2a08763e03b22384dd8b15b916e2f1e8d8b5b9`
- Result: **INTENDED RED**

The focused public application test requires malformed UUID, impossible date,
INITIAL lineage, incomplete CORRECTION lineage and blank correction reason to
return exact `REJECTED/INVALID_COMMAND` before request lookup or stream/storage.
It also pins restart-durable `NO_CHANGES`, accepted fingerprint replay before
stale/current validation after the leaf moves, and safe typed mapping of a
throwing authorization port without reading upload bytes.

Actual command exits `255` on the first semantic disagreement: malformed UUID
is accepted and persists revision 1 instead of returning `INVALID_COMMAND`.
PHP lint passes and `git diff --check` has no output. Production is untouched.
