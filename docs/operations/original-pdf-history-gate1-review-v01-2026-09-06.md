# ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001 — independent Gate 1 review v01

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed repository HEAD: `2de38cb0c5c1dea21914bdc67d32ea3c83ffcf1b`
- Candidate SHA-256: `d7d21869b959dfb839230f084719ff3cc0bc7c6168acfdd5387ebbe710ff34e3`
- Parent original-upload SHA-256: `bdd57ea8b79e7414b7724da953b838b1d7c2d76e4e4df8170fa8664cd492e00d`
- Scope: all-history active-name scan, exact PDF lexical names, structural and opaque stream classification
- Verdict: **APPROVED**

The reviewer authored none of the candidate, parent or OpenSpec artifacts. No
parser source or test was edited or executed during this Gate 1 review. The
earlier harmless audit reproductions remain independent source evidence.

## Determination

The candidate is ready for Gate 2 at the exact reviewed bytes. It closes three
existing parser contract gaps without changing algorithm identity, product
scope, public result vocabulary or accepted resource limits:

1. security scanning covers all selected revisions, unreachable objects,
   overwritten bodies, trailers and decompressed object-stream members;
2. forbidden names are recognized as exact PDF Name tokens rather than regex
   substrings in strings/comments/hex data or prefix names;
3. structural raw/Flate streams remain bounded and decoded, while ordinary image
   and content payloads remain opaque under a closed filter/framing grammar.

The parent specification makes PDF-HISTORY-001 normative for this correction,
and all four OpenSpec artifacts carry the same history/name/opaque-stream
boundary. Existing contradictory parser-test expectations are explicitly
preserved as historical bytes and require a separate reviewed unapplied patch;
the new RED cannot silently alter them.

## Technical assessment

### Historical security scan and current graph

The candidate cleanly separates two concerns. Every selected physical object
body and every trailer dictionary in every xref/Prev revision is lexically
scanned for forbidden names, including entries later freed, overwritten or
unreachable. Every declared object stream is decoded and every member scanned,
including unreferenced members. Historical type-2 entries must resolve the
container/member identity belonging to their own revision.

Newest-entry precedence remains authoritative only for the effective current
Catalog/Pages graph. Older bodies are never allowed to replace the current root
or page tree, but current reachability cannot erase them from the security scan.
This directly addresses both independently reproduced failures without
regressing the prior incremental parser's correct physical-offset/xref identity
checks.

The physical-offset decode cache is exact: each structural payload is decoded at
most once and the same decoded representation serves historical scanning and
latest-graph resolution. Cache identity is physical offset rather than object
number, so a newer replacement cannot hide or alias an older object-stream body.

### Exact PDF lexical rules

The whitespace and delimiter sets, slash-introduced names, case sensitivity and
two-hex-digit `#xx` decoding agree with the linked Adobe PDF/Cos lexical rules.
NUL escapes and malformed/truncated name escapes fail invalid. Escaped whitespace
or delimiters remain bytes inside the same atomic Name and do not create a new
token boundary.

Balanced literal strings, their backslash escapes/nested parentheses, comments
through line end/EOF and hexadecimal strings are skipped as distinct lexical
objects. Odd final hex nibble and hex whitespace remain legal; malformed or
unclosed constructs fail invalid. Structural keys use the same tokenization, so
quoted/commented `/Type`, `/Length`, `/Filter` or reference-like text cannot
manufacture structure.

The independently fixed distinctions are complete: exact `/JS` and `/J#53` are
unsafe; case changes and longer/punctuation/escaped names remain distinct.
Forbidden-looking bytes in literal/hex strings, comments or opaque stream
payloads remain data. Global `#xx` replacement and regex word boundaries are
expressly insufficient.

### Structural and opaque streams

Xref and object streams are always structural. A historical type-2 container
must be a valid object stream and cannot be downgraded to opaque. Structural
streams accept no filter or one direct FlateDecode; array, indirect or unsupported
filters fail invalid. Duplicate/direct numeric/name requirements and exact
length/framing remain inherited.

All other streams, including page content and Image XObjects, validate a single
direct nonnegative Length, dictionary and exact framing but do not decompress or
scan payload bytes. Their Filter is absent, one direct allowed Name, or a
nonempty direct array of allowed Names. The allowlist is closed; malformed,
indirect, duplicate, unknown or non-Name entries fail invalid, while Crypt is
classified unsafe as encryption. Filter chains are not executed, and codec
validity/OCR remain outside scope.

This classification prevents ordinary content/image expansions from consuming
the structural decoded-byte budget while preserving the total 20 MiB input
limit and all dictionary/framing checks.

### Error classes and bounds

A structurally valid file containing any forbidden Name returns UNSAFE_PDF.
Malformed tokenization, name escapes, filters, framing, xref/container/member
identity or limit exhaustion returns INVALID_PDF. Encryption remains UNSAFE_PDF.
The matrix keeps malformed and active causes separate when asserting an exact
class.

The unchanged boundaries are unambiguous:

- received bytes: 20 MiB;
- selected objects: at most 100,000;
- reference depth: at most 100;
- aggregate actual structural Flate expansion: 67,108,864 bytes inclusive,
  charged once per physical payload;
- xref/Prev chain: at most 64 sections.

A terminating section 64 is allowed. If that section still has `/Prev`, section
65 would be required and the parser must fail invalid. Cycles, repeated offsets
and broken offsets remain fail-closed. Opaque compressed payloads do not consume
the structural expansion budget and cannot widen the received-byte ceiling.

### Compatibility with prior approval

The prior incremental-parser Gate 5 approved exact direct-object discovery,
xref identity/offset validation, newest effective-object precedence, Prev/root
grammar, structural stream framing and existing bounds. It did not test or prove
all-revisions ACTIVE scanning, exact lexical exclusion of strings/comments, or
opaque content/image filter handling. PDF-HISTORY-001 adds those missing checks
while preserving every approved structural property.

Algorithm ID remains exact `fmonitor-passive-pdf-v1`, now exposed by the restored
public `ALGORITHM_ID` constant and `algorithmId()`. Because this is correction of
already normative v1 behavior rather than a new parser policy, no product-owner
decision or alternate algorithm identifier is required.

## Mandatory evidence assessment

The fixed matrix is sufficient to distinguish the required implementation:
unreachable and overwritten active dictionaries, raw/Flate unreferenced object-
stream members, historical container mismatch, trailers, encoded/indirect names,
string/comment/hex/prefix controls, opaque image/content filters and marker bytes,
structural filter failures, decode-cache single charging, exact object/depth/
decode/Prev boundaries and safe controls.

Generated fixtures must pin their own offsets, lengths, decoded sizes and
structural membership. The old unreachable-passive and ordinary-content-budget
assertions require a separately reviewed exact patch with fresh mismatch evidence.
No expectation may be converted to a skip or derived from production output.

## Verification

```text
$ git rev-parse HEAD
2de38cb0c5c1dea21914bdc67d32ea3c83ffcf1b

$ shasum -a 256 specs/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001.md
d7d21869b959dfb839230f084719ff3cc0bc7c6168acfdd5387ebbe710ff34e3

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
```

The linked Adobe Cos documentation confirms the candidate's relevant lexical
distinctions for literal/hex strings, atomic case-sensitive names, slash prefix,
raw whitespace/delimiter termination and `#xx` representation. The large linked
PDF reference was not downloaded during this review; its authoritative link and
section remain pinned by the candidate.

## Exact reviewed hashes

```text
d7d21869b959dfb839230f084719ff3cc0bc7c6168acfdd5387ebbe710ff34e3  specs/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001.md
bdd57ea8b79e7414b7724da953b838b1d7c2d76e4e4df8170fa8664cd492e00d  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
466c4fa0fa0ab57dc6f34cb11cbae7c64822282cb0aa4a48630dbdd123248655  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
f956831965fbd17e16f91bae13e723db66714a05a3b8e15fb51eb50e589cc34c  openspec/changes/replace-pilot-registration-with-original-upload/design.md
a51b6159609ba27dc5282507f9f6f820b3e36f25dc9d1271b0507327b611e605  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
37df160531cdaa4c2204a635173e563f6322f95bf065763bddea7a1d0dd7bd71  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
a665d621e85e8f6833a5ddcf4af6f4ce8bbca4b685d0df2fdc5e08ab75577444  docs/operations/original-parser-all-revisions-contract-audit-2026-09-06.md
79dfa1bedb3c092effb4cf3f89a4e1c5f7962bdf88cf2280f567dcdfb7123f00  docs/operations/original-parser-opaque-stream-and-name-audit-2026-09-06.md
e460454efd741e303275a5e54e7c1e3609f1963c919eed9232864507d6aaf6da  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-incremental-grammar-v6.md
```

This review omits its own circular hash. Approval authorizes Gate 2 only for the
exact PDF-HISTORY candidate. It is not test approval, parser implementation
approval, combined command Gate 5, full verification, deployment or launch
readiness.
