# Code review: ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001

- Reviewer: `/root/original_gate5`, independently tasked agent; not specification, test or implementation author.
- Implementation author: root implementation agent.
- Reviewed source: `f68fa7a333e66b74c4497320eb2ca1e21f0cd968`, against test source `f60dcea`.
- Specification: v0.1; independent Gate 1 and Gate 3 initial/v2 APPROVED.
- Verdict: `APPROVED` for the bounded metadata-reference and borrowed-transaction guard.

## Findings

No blocking findings. Independently reviewed all 13 changed production files, approved tests and the existing helpers reached by the new source. The factory validates the prefix before SQL and creates no connection/configuration/filesystem dependency. Invalid IDs precede connection access. Lookup uses the existing idle-only consistent read-only snapshot; the connection is neither closed, reconfigured nor reconnected. The existing snapshot helper rejects an active caller transaction before beginning its own transaction.

References expose only the exact 14-field metadata whitelist and nested scalar composition snapshots. The metadata array returns by value. Issuance uses a private per-reader WeakMap keyed by the actual reference object; constructing or cloning a DTO does not forge an issued reference. The seal and connection/database/charset scope remain private, including private storage metadata encountered while validating immutable rows. Actor authorization remains the trusted consumer's obligation; no HTTP grant, filesystem availability or applicability policy is inferred.

The borrowed guard checks issuance/scope/active transaction, then locks the exact canonical case/object row before validating source. Traced the found-reference path through registered composition, submission source, root/lineage, revisions, terminal requests, audits and events: current reads use explicit FOR UPDATE throughout, including validation backing. The seal covers immutable root fields, the originally referenced revision, registry, selection header and ordered members; only the root leaf pointer is omitted for separate revision comparison. A valid later correction therefore returns changed, whereas changed sealed source or malformed current backing is unavailable. No guard begin/commit/rollback, wait-policy change, connection close or persistence write appears.

The four existing helpers retain default-false locking options. Prior callers keep their snapshot behavior; new locking is opt-in within the owning module rather than SQL rewriting or duplicated cross-module ownership. Existing fingerprint/revision/private-reference helper branches are not used by this new guard. Classes remain small and responsibilities clear. No migration, domain writer, audit mutation, private-byte read or runtime grant was added.

## Verification

Inspected `green-first.log`: all 14 native cases pass with 14 healthy setup and 14 successful cleanup records, at prefixes 0 and 25. Both native correction workers are observed waiting on the case lock before caller release. The RR cases prove an ordinary caller snapshot remains stale while the guard sees the later committed correction. Sentinel writes distinguish preserved caller ownership from hidden commit/rollback, across matched, changed and unavailable outcomes. Exact metadata, copy isolation, missing/foreign/configuration cases, corruption rejection and temporary private-root unavailability retain their independently specified expectations.

Independently matched the test SHA-256 to Gate 3 v2 (`e61aae78ca06f11b82b658152608b1cb976cacbdb77de2c949344a64b46aec56`). Inspected all four regression manifests and nine corresponding logs: each recorded exit is zero, covering original data/lineage, registered composition, selected original binding/lifecycle, upload HTTP flow/prefill and native selection acceptance/concurrency. These native executions belong to the implementation agent; this reviewer does not claim another DB execution.

Independently ran PHP lint for all 13 changed production files, `git diff --check f60dcea f68fa7a`, and `make architecture-check`: PASS, including all seven architecture rules. Operations evidence also records full lint PASS. External evidence root: `/Users/antropophag/.local/state/fmonitor2-verification/original-reference-20260907`; operations record: `docs/operations/original-application-reference-green-2026-09-07.md`.

## Required changes and completion boundary

None for this bounded slice. This reference confirms metadata/source identity within the caller transaction; it does not prove current PDF-byte availability, authorize an actor or determine which order may be applied. Parent composition application, history/download HTTP, opening, protected E2E and full exact-source integration verification retain their separate gates. No full `VERIFY_OK`, parent completion or launch approval is asserted. Only this review record was written; no production/test/spec changes or commit were made by the reviewer.
