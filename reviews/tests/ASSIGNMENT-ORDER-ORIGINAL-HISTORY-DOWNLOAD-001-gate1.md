# Independent Gate 1 — ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001

- Verdict: **APPROVED**
- Reviewer: `/root/original_gate1_v3`, separately tasked agent; not the author of the specification, tests or proposed implementation.
- Date: 2026-09-07
- Reviewed HEAD: `58203f3945d00c17ca1c96d5ec446aa40d618d86`.
- Specification: version 0.1, SHA-256 `a67f3eb59d89b9479cbc1940766eaf060d1102a33aee7873056dbadfa72b8e16`.

## Findings and decision

No blocking findings. The factory and two lookup methods are an explicit trusted production data seam owned by AssignmentOrderOriginal. Actor/capability/object authorization stays with the consumer. The diagnostic EvidenceReader is expressly excluded from runtime, and this prerequisite neither authorizes assigned-engineer access nor resolves application/correction-date policy or parent HTTP completion.

The history cursor contract is closed: ascending immutable revision numbers strictly after the supplied cursor, limit 1–100, next cursor only when more revisions exist in the same snapshot, and found/empty for a cursor at or beyond the end. Context, total and current pointer share that snapshot. A later correction cannot mutate a returned value or hide the previous order's history. Selected-source binding and complete lineage/request/audit/event validation prevent a foreign revision or corrupt backing from becoming a successful result. Pagination bounds the returned page without weakening source validation.

The metadata projections explicitly whitelist page, selected composition and nine revision fields. Download identifies the requested historical revision, not whichever leaf is current later, and exposes no storage identity/path/filename, configuration or diagnostic inventory. The copy semantics and lack of retained resources are observable. Missing source/revision is distinguished from malformed backing and unavailable file bytes; a healthy metadata history remains usable when private storage is unavailable.

Transaction ownership is explicit: only an idle borrowed connection can host an owned read-only snapshot; existing caller transactions are refused without being committed or rolled back. Download releases the database snapshot before filesystem work and relies on the accepted revision's immutability. The result owns only a fully checked byte string, so later filesystem errors cannot turn an already-prepared value into partial PDF output.

The filesystem contract is appropriately strict and bounded: fixed digest-derived native identity, canonical root, owner/mode/link checks, lstat/fstat coherence, existing read-only shared nonblocking digest lock, exact size/EOF/hash validation and cleanup before return. Busy locks, unsafe paths, corrupted bytes and cleanup failure return unavailable without a download value. Existing storage inspection confirms writers use the same digest lock identity and native content filename. The current writer lock helper uses create/chmod semantics and therefore cannot be reused directly for this read-only operation; Gate 3/5 must verify the prescribed read-only open and absence of repair/state writes.

## Evidence obligations and scope

The independent fixtures fix composition, document dates, actors, PDF hashes/sizes and correction reason; only command-generated identities are taken from public receipts. Required native evidence covers two-revision pagination and old/new PDF selection, exact 20 MiB preparation, wrong binding, corrupt backing, filesystem rejection variants, exclusive-lease contention/recovery, prefix 0/25, preserved caller transaction, immutable returned copies and stable native resource counts. Healthy native setup must precede intended RED. The reader is not required to reparse an already-accepted PDF, but must fully verify its accepted size/hash before returning bytes.

Read the normative specification, all four OpenSpec artifacts and existing storage/root/digest-lock/lease implementations. Reused the previously inspected registered source/StoredReader/application-reference contracts and mandatory repository documents. No specification, tests, production files, DB or filesystem fixtures were changed or executed; only this review record was added.

| OpenSpec artifact | SHA-256 |
| --- | --- |
| proposal.md | `f74ea2c1d1e646f4b07609512b6b36716c67ed11af812c1a55e13bf3f4fce195` |
| design.md | `44dd0343c650da86f7cf89eeec43a9e523b8850e8942a85e8ec5e4e1b3168cf2` |
| tasks.md | `c4a35a4f456339da84dfd7e8df11b31e7a3bbbfc7b2e9d8cd57172f821a15a91` |
| specs/pilot/original-history-download/spec.md | `43fc4fee72af3e78c503adc026681d6400be8370354ce24211b6e9b73f7f0fd4` |

Gate 1 only is **APPROVED**. Native RED, independent Gate 3, minimal GREEN, relevant regressions/architecture checks and independent Gate 5 remain required. Parent all-role HTTP, restart, full VERIFY and launch remain unclosed.
