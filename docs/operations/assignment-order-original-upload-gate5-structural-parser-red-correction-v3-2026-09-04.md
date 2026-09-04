# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — structural parser RED correction v3

- Prior Gate 3: `eb3c058645bb8ffa1396f7a20b71996a227c3716` (`CHANGES_REQUESTED`)
- Result: **INTENDED RED preserved**

Forbidden compressed-object cases are now independently sensitive:
`JavaScript` no longer contains `JS`, and `OpenAction`, `AA`, and
`EmbeddedFiles` use structurally complete direct values rather than dangling
references. New independent fixtures require failure for conflicting duplicate
xref identities, declared object namespace above 100000, and aggregate Flate
structural expansion above 67,108,864 bytes.

The focused test and fixture builder pass PHP lint and `git diff --check`. The
real inspector still returns `PASSIVE_PDF` for the first active compressed
`JavaScript` fixture; expected `UNSAFE_PDF`, exit `255`. Production is unchanged.
