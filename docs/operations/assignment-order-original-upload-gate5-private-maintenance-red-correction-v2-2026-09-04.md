# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — private maintenance RED correction v2

- Prior Gate 3: `f9da095edfa32f0d624734ac27cbd423fdb8ec4d` (`CHANGES_REQUESTED`)
- Result: **INTENDED RED preserved**

The real-storage contour now proves exact ordered identity continuation across
pages, young-stage retention after maintenance, committed-reference recheck,
active-lease retention before reference lookup, and zero reference operations
on request replay. Digest reuse has both controls: identical bytes require
`ALREADY_PRESENT_VERIFIED`, while same-size different bytes at the claimed
digest path must fail without a lease.

PHP lint and diff hygiene pass. Execution still exits `255` at the earlier
honest `listOrphans()` empty-page disagreement. Production remains untouched.
