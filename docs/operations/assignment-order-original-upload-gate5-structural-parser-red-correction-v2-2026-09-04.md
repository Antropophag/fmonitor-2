# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — structural parser RED correction v2

- Predecessor RED: `0215c00`
- Gate 3 review: `8c028fe18b9200c512ffaada015414bc50dd0431`
- Prior verdict: `CHANGES_REQUESTED`
- Result: **INTENDED RED preserved**

The Flate fixture is now a structurally active compressed object: object 5 is
addressed by a type-2 entry in a binary xref stream and referenced from the
Catalog. The forbidden matrix now includes every approved family independently.
Additional negative fixtures cover generation mismatch, malformed xref-stream
widths, unsupported structural filters, cyclic `Prev`, cyclic Pages, reference
depth above 100, wrong classic offset and latest-root zero-page resolution.

The focused command still exits `255` at the trustworthy first disagreement:
the real inspector returns `PASSIVE_PDF` for active compressed JavaScript where
the approved algorithm requires `UNSAFE_PDF`. Both PHP lint commands pass and
`git diff --check` has no output. Production remains untouched.
