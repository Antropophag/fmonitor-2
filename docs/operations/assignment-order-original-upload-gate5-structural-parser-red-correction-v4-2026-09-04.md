# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — structural parser RED correction v4

- Prior Gate 3 correction: `e0b3bc41131106c720aa80097c2c39c31fcbc320`
- Result: **INTENDED RED preserved**

The conflicting-identity fixture now contains two real `1 0 obj` headers at
their exact byte offsets and two xref subsections that both actively define
object 1 with different Catalog bodies. Therefore a parser that validates
offsets but omits duplicate/conflict detection can select an entry and accept;
the expected `INVALID_PDF` is sensitive specifically to the conflict.

PHP lint and `git diff --check` pass. The focused suite retains the earlier
active compressed-JavaScript intended RED. Production remains untouched.
