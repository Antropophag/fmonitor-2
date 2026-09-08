# PDF history implementation gate ledger — 2026-09-06

This is a technical correction under ORIGINAL-PDF-HISTORY-001 v0.1, not combined
original-command approval or launch completion. Root authors specification,
tests and implementation; separately tasked agents own reviews.

- Gate1: `original-pdf-history-gate1-review-v01-2026-09-06.md`.
- Main 110-case RED and source manifests remain in the private archive recorded
  by `original-pdf-history-red-v1-2026-09-06.md`.
- Main Gate3: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-001-v1.md`,
  SHA256 `2cc8d60ea01257c61ba75be099859a2ca5e37f5904212ee2b25c7039922b09b9`.
- Existing-oracle patch Gate3:
  `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-ORACLE-001-v1.md`,
  SHA256 `5f49f3d46ac9947bd1dc3a2e4aec9cf4e2e17d284b2e9347dfe230fe9bd6d563`.
  Applied only after both Gate3 approvals; expected patched old test SHA256
  `3acfed07b98126d97d9556e359584c2a9b8cdecd092b42cd624c8bf75908c6d8`.

## Separately reviewed framing-oracle correction

Initial GREEN inspection found that the new image Length+1 fixture counts its
trailing LF, leaving `endstream` intact. This is the same valid opaque framing
as the pre-existing contentStreamLength positive control. The independent
`original-pdf-history-length-oracle-conflict-review-2026-09-06.md` documents
that byte-level contradiction. No production image-specific exception was added.

Root prepared the exact one-byte delta1→2 candidate patch
`original-pdf-history-length-oracle-amendment-v1-2026-09-06.patch`, SHA256
`580c6d016b517171a70124e91672375bb95843a7495e568581e67b831c62c505`.
Independent Gate3 `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-PDF-HISTORY-LENGTH-ORACLE-001-v1.md`
SHA256 `1c672616e9b1c2e0cddf4605f08944babe4f1d4c05cccb86dd72f45a5b8a6793`
approved it before application. The test retains INVALID; +2 now consumes the
first byte of the marker and is unambiguously invalid. Main test after hash:
`c675de79379a80bc1b19a2b1ece7c673228d2c4bef957f93d65be27539bf503c`.
Original reviewed RED and review records remain unchanged.

## Filter-chain additive RED

Early independent code audit identified an extra WIP restriction on repeated
allowed codecs, absent from HISTORY-001 section4. Repeated codec values differ
from forbidden duplicate Filter dictionary keys. Root added six public-inspector
cases, three intended failures and three controls. Private archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-pdf-filter-chain-red-ov_dbick`.
Authoritative evidence SHA256:
`f953120cbbc41c8af03b9ba7649c864239fd546359cd35b960c351cddc05b977`.
Test SHA256 `8f7c2d19c65f7f2912268b16b098540982e958e30411c3684efc2ca445650b70`.
The manifest pins the entire uncommitted PDF implementation at observation.
An initial enum-autoload setup failure is retained separately and is not RED;
authoritative run constructs the inspector before using runtime enum constants.
No reviewed test expectation was relaxed and no case was skipped.

Full exact-SHA GREEN and independent Gate5 are separate future records.

## Additive test approvals and implementation scope

Filter-chain Gate3 APPROVED:
`reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-PDF-FILTER-CHAIN-001-v1.md`, SHA256
`935639cddefbf13690c04624e579dbecbf64233e53f199d823557390fcbfd34c`.
Only after approval was the extra codec-value uniqueness restriction removed.

Nested-value syntax Gate3 APPROVED:
`reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-PDF-NESTED-VALUES-001-v1.md`, SHA256
`f8f530f8da15779b03981f7b00a124b18bc9f12273d2d9b0c043c9378ddcd6ef`.
Test `ab40ff465c81d065e6505c9c78225519eb7d5561318a7222cb737a30030c5919`
has six invalid nested key/value failures and four valid controls. Private RED:
`/Users/antropophag/.local/state/fmonitor2-verification/original-pdf-nested-values-red-yxz8g0ai`,
evidence `480a6a6433236fa7aa80130ca39b4334c5d427f62a27ff6636ca33c2f34387c4`.
An iterative value parser now validates nested dictionary key/value pairing,
array/scalar/reference values and duplicate structural keys without introducing a
new nesting limit. Decoded Name values never act as structural delimiters.

The implementation separates byte lexical views, direct dictionary entries,
physical object offsets/framing, structural stream caches, xref sections and
latest graph/page validation. Historical type2 entries resolve the cumulative
snapshot at their own revision; unsafe scanning includes all selected physical
objects, trailers and all declared ObjStm members. Ordinary payload bytes remain
opaque and do not consume the structural Flate aggregate. Existing structural
framing and exact Root requirements are retained. Public algorithm constant is
restored without changing the algorithm ID or numeric limits.

All three parser suites plus both additive suites passed local checks before
integration verification. These worktree checks are preliminary; only the later
exact-SHA full affected regression record can support implementation Gate5.
