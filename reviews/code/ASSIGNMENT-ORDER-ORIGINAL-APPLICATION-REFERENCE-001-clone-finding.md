# Code review follow-up: original-reference reader cloning

- Reviewer: `/root/original_gate5`, independently tasked agent; not implementation or reproducer author.
- Reviewed source: frozen `18916aef904e37ddfbcf8afd9adb6abcdf642654`; reader implementation introduced in `f68fa7a333e66b74c4497320eb2ca1e21f0cd968` is unchanged.
- Specification: ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001 v0.1, sections 2–3.
- Verdict: `CHANGES_REQUESTED` for a bounded issuing-reader identity defect. Previous review remains preserved.

## Confirmed finding

**P2 — cloning a reader shares its issuance map and accepts another instance's reference.** `MariaDbOriginalApplicationReferenceReader` has no clone handler or clone restriction. PHP's default shallow object cloning retains the same WeakMap object, SQL helper and source helper in the clone. The scope check contains database/thread/charset but no reader identity. Consequently a cloned reader finds references issued by the original in the shared map and returns matched for them. Cloning before issuance is also affected because subsequent map mutations are shared.

This violates the explicit contract: references bind the issuing reader instance, and references from another reader must be unavailable. It is a trusted in-process identity invariant failure; it does not imply an HTTP authorization or database-write bypass.

Independently inspected the actual implementation and the external native reproducer and output at `original-reference-20260907/clone-repro.php` / `clone-repro.log`. The reproducer uses the real fixture's public selection and original commands, public reader factory/readCurrent, ordinary `clone`, and a caller-owned native transaction. It invokes no reflection, private method, shadow function or intercepted native operation. Recorded result is `CLONED_ISSUER_GUARD=matched`, followed by successful fixture cleanup. Execution belongs to root; this reviewer inspected its provenance/output rather than claiming another run.

## Smallest contract-preserving correction

Give each cloned reader a fresh empty issuance WeakMap in `__clone()`. Shared SQL/source helpers may remain: multiple readers are allowed to use the same native connection, but issuance cannot transfer between instances. This preserves ordinary object cloning as an independent reader and returns the already-specified unavailable guard status for foreign proofs. Merely binding a recyclable integer object ID would be less robust; disabling cloning would add an unnecessary exception surface outside the stated guard behavior.

Extend native tests through the public seam with cloning both before and after issuance. For each ordering, references issued by the original must be unavailable to the clone and vice versa; each reader must still match its own newly issued reference. Preserve caller transaction/sentinel ownership and no-fact/no-file-write checks. Existing separately constructed foreign-reader coverage does not detect shared-map cloning. Demonstrate intended RED, obtain independent Gate 3 for the added sensitivity, then make the minimal correction and repeat relevant GREEN/independent review. Existing explicit per-instance identity specification supplies the expected result; no relaxed contract is needed.

Full verification is currently running against frozen source; no source/test edits were made. Only this separate review finding was written. The original-reference slice must not be considered fully closed against its identity contract until this finding is corrected and reviewed; parent application and launch completion remain outside scope.
