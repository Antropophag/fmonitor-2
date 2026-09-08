# Canonical metadata format reconciliation

The owner-approved v0.6 text had its H1 before the authoritative metadata fence,
contrary to its own metadata-first rule. The parser remains strict. This change
moves only the existing H1 below the existing metadata block; no requirement,
identity, version, word or narrative order changes.

- Previous approved bytes: SHA-256 `189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859`, preserved in Git at3f9514b and earlier refs.
- Canonical bytes: SHA-256 `5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b`.
- Exact transform: split the original H1, metadata fence and remaining narrative at
  their existing blank lines; concatenate metadata, H1, narrative. Reversing that
  permutation recovers the previous approved bytes exactly.

The existing owner approval applies to unchanged content. The independent code reviewer
confirmed that no new parser exception or bootstrap semantics are needed. All old
RED/review/GREEN records remain immutable. New records will explicitly rebind the
same eight approved tests and preserved genuine historical RED evidence to the
canonical digest; they will not claim a new RED on already-correct current code.
Gate5 will review cumulative implementation history and the exact latest delta.
No receipt has yet been issued, so no historical receipt is modified or waived.

The ongoing3f9514b full run remains executable-behavior evidence. Final canonical
lineage and exact-checkout verification will be recorded separately.
