# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v49 fingerprint encoding — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_fingerprint_gate1`
- Reviewed commit: `df1d3449bf4df49a6503bad5937e535a480db682`
- Triggering gap: `docs/operations/assignment-order-original-fingerprint-encoding-gate1-gap-2026-09-05.md`
- Scope: v49 executable-spec/OpenSpec fingerprint-encoding amendment only; no
  test or production implementation reviewed or changed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests, production implementation, prior reviews or gap evidence. This
append-only review is the only authored artifact.

## Independent review

The accepted-operation fingerprint tuple remains in the exact normative order
`mode, installationCaseId, assignmentOrderId, rootOriginalId-or-empty,
targetRevisionId-or-empty, expectedCurrentRevisionId-or-empty, documentDate,
compositionSnapshotIdentity, compositionSha256, pdfSha256`. V49 now gives one
unambiguous binary encoding for every member: an unsigned four-byte big-endian
byte length followed immediately by the raw member bytes, with no separators
or terminal marker. A null lineage identity is exactly a zero length and no
bytes. Positive integer IDs are unpadded base-10 ASCII. String lengths count
UTF-8 bytes rather than Unicode code points.

The encoding is injective over the ordered tuple: each four-byte length closes
the following byte extent, including adjacent empty members, so concatenation
cannot create a separator collision. The existing scalar grammars also keep
every member far below the unsigned 32-bit length limit: generated/accepted
root and revision identities are ASCII and bounded to 80 characters, hashes
are fixed 64-byte lower-case hexadecimal values, mode and date are fixed,
composition identity is built from bounded positive integer/version values,
and the two numeric IDs use the platform's bounded integer domain. The explicit
UTF-8 byte-count rule remains definitive if a future member grammar admits
non-ASCII content; no code-point count is permitted.

Using an independently written `pack("N", strlen($member)) . $member` loop,
the Example A member byte lengths are
`7,4,2,0,0,0,10,17,64,64`. Forty prefix bytes plus 168 member bytes give the
published 208-byte preimage, whose independently recomputed SHA-256 is:

```text
dd356db041181636ce1ecfc619f9055a625d81250e59ad3543c9f5cd5b582a7d
```

For the canonical correction-race tuple, the member byte lengths are
`10,4,2,13,13,13,10,17,64,64`. Forty prefix bytes plus 210 member bytes give
the published 250-byte preimage, whose independently recomputed SHA-256 is:

```text
719d1773101e3211fb0857ad8fcb375fac10a5c7e08180491181a93a4e30f91e
```

The values use the section-12 Example A literals exactly: case `4512`, order
`81`, composition identity `composition-81-v1`, composition digest
`388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5`
and PDF digest
`4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`.
The initial date is `2026-09-01`; the correction tuple uses mode `correction`,
date `2026-09-02`, root `original-0001`, and target plus expected-current
identity `revision-0001`.

Propagation remains coherent. The derived composition identity/digest and the
received PDF digest feed this exact encoder before accepted-fingerprint lookup.
The resulting fingerprint is persisted with the accepted operation and exposed
by the typed evidence reader. Cross-request semantic replay therefore compares
the independently assertable fingerprint, while same-request replay retains
its earlier terminal-request precedence. Both identical and different
two-worker races pause after fingerprint miss; a repository CAS conflict keeps
the content lease while rereading the same accepted fingerprint and current
lineage, so the published correction digest distinguishes semantic replay from
stale/different evidence without borrowing a production oracle. No request ID,
actor, filename, MIME, upload time or correction reason leaks into semantic
identity.

The executable specification, OpenSpec delta and design agree on the encoding,
both example lengths and both digests. The amendment closes the recorded
independent-value gap without changing the product workflow or broadening this
slice. Task `1.31` remains unchecked in the reviewed commit, correctly leaving
closure to the integrator. Task `4.1` may now continue against these fixed
fingerprint expectations; this Gate 1 approval does not approve its tests or
production implementation.

## Verification evidence

```text
$ git rev-parse HEAD
df1d3449bf4df49a6503bad5937e535a480db682

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check fe517668bc2279fe43430fc1b582cd8c9481893f..df1d3449bf4df49a6503bad5937e535a480db682
PASS (no output)

$ php -r '<independent pack("N", strlen($member)) tuple recomputation>'
208 dd356db041181636ce1ecfc619f9055a625d81250e59ad3543c9f5cd5b582a7d
7,4,2,0,0,0,10,17,64,64
250 719d1773101e3211fb0857ad8fcb375fac10a5c7e08180491181a93a4e30f91e
10,4,2,13,13,13,10,17,64,64
```

## Exact reviewed hashes

```text
b12f3c88762ae59b298fca26b0942f223e95a7b2914398e7e0959d804a2eb845  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
605bd0255e0488b08b39657a05896da200d26e5ea801054276f2eb3dca8701f2  openspec/changes/replace-pilot-registration-with-original-upload/design.md
5a59fc31bc77c97f96a9f101396219bfd6f512e8495f9ead55d65621585ee15d  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
c78122420a2df8e82cd61ac3c7a8baf3110907127e1a9d6c973b45c25874a779  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
c94d10c445495258c1ced379949a21ca8a1a10b6933dcafb6e4e940b3e32d74c  docs/operations/assignment-order-original-fingerprint-encoding-gate1-gap-2026-09-05.md
```

No findings. This record intentionally omits its own circular hash.
