# Test review: ASSIGNMENT-ORDER-ORIGINAL-COMMAND-SHAPE-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit authored by Timofey Grishin
- Reviewed commit: `9b8d6ec03846b8b8e96444e9f451da72d106e266`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-SHAPE-001.md` v0.2, SHA256 `e98e37f832a64995c68e329e106b1c56ec0ded28d309ee43539b9970e7cb5d0d`
- Public seam: existing `AssignmentOrderOriginalApplication::submitAssignmentOrderOriginal(Command): Result`
- Red command and intended failure: `php tests/InstallationProcess/assignment_order_original_command_shape_001_test.php`; 194 cases, 152 intended failures and 42 positive controls, aggregate exit `255`
- Verdict: `APPROVED`

## Exact reviewed inputs

```text
e98e37f832a64995c68e329e106b1c56ec0ded28d309ee43539b9970e7cb5d0d  specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-SHAPE-001.md
eb99c71d06dbf858bf3ec804d65c1d32094f1972693841bf8eb97a27995b1b51  docs/operations/original-command-shape-gate1-review-v02-2026-09-06.md
32858b2c602f65f19b5efeb1773db354d3fd6b44fb3535c88995d2021bf553d2  tests/InstallationProcess/assignment_order_original_command_shape_001_test.php
d46e91621f5fead33ea2d5aaac4b25b197cb54c5fd31fa671e0a1893150f74a6  tests/Support/AssignmentOrderOriginalShapeFixture.php
c04f1e73636869adde1eff2d6d38a2af11900f16534a3291c48daa9faa65e4aa  tests/Support/AssignmentOrderOriginalDynamicPortsFixture.php
2adfc6ddb2414fe95435ff1ac287d323be1cbdd25297d70581c49a24609607de  docs/operations/original-command-shape-red-v1-2026-09-06.md
d7663e86e135ddb977042d43b790dcb219cd052e54e87bceb9e7a67e255f9c1f  /Users/antropophag/.local/state/fmonitor2-verification/original-shape-red-x9pog9w0/evidence.json
e9bb54ab7c7945e354635a2415c4b59709d04ba723d8b0c3a77b066bb6c7cfb3  /Users/antropophag/.local/state/fmonitor2-verification/original-shape-red-x9pog9w0/red.log
d8ca5ba2f38d463b8f84b0e91b045412f5c625a0694f0fe51e037ed98453d00a  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
2108f10eac0e7face1c2095371ae0796de8ae825a1feb05f028b656b0cd5bf3f  app/AssignmentOrderOriginal/AssignmentOrderOriginalPortValues.php
```

The v0.2 specification has exact independent Gate 1 `APPROVED`. Runtime and port-value hashes match the authoritative RED archive; no production change is included in the test commit.

## Findings

Traceability and seam choice are complete. All cases construct the existing passive command DTO and invoke the real verification-factory application. Shape validation is observed through the returned full result and public port counters; no private validation method, reflection, database, filesystem or alternate acceptance seam participates.

Invalid-shape coverage is run twice: with an ordinary empty repository and with a terminal-hit repository. Every malformed filename, correction reason, date, caller opaque ID and forbidden INITIAL non-null field must return the exact nonretryable `REJECTED/INVALID_COMMAND` tuple before authorization, terminal lookup, composition, clock, storage, ID allocation, inspection, lifecycle, audit or delivery. The supplied stream remains unread and is closed once. This directly detects the current replay-before-shape defect.

The terminal-hit Given contains a literal prior `AssignmentOrderOriginalAcceptedCommit` in repository evidence, including request/fingerprint/root/revision/composition/date/content/event facts. Its terminal result is consistent with that commit. Before/after canonical evidence equality proves malformed input neither fabricates nor mutates prior acceptance; the case is not merely a result-only stub.

Calendar cases independently distinguish syntax and Gregorian validity: non-leap `2026-02-29`, impossible days, month/day zero or 13, year zero, non-padded month and trailing space are invalid at shape step. Valid `2024-02-29` proceeds through the normal application and is persisted unchanged, preventing blanket date rejection.

Raw text coverage checks UTF-8 before normalization and distinguishes allowed TAB/LF/CR from prohibited Cc values. It includes embedded/leading NUL, vertical tab, U+0001, DEL, U+0085 and malformed UTF-8. Empty, ASCII-space-only and NBSP-only inputs prove trim-to-empty rejection. The invalid bytes remain raw test data; no replacement character is used as an oracle.

Code-point and normalization sensitivity is adequate. Cyrillic one/max/max+one boundaries distinguish Unicode code points from byte length for filenames and correction reasons. NBSP/U+3000 edges prove the explicit Unicode trimming behavior rather than ASCII `trim`; interior TAB/LF/CR remain byte-preserved, with only trailing CR removed. Accepted corrections persist the exact normalized reason while the original command property retains its raw value, proving immutable DTO handling and no normalization-induced fingerprint requirement.

Caller opaque-ID matrices cover root, target and expected-current independently with empty, 81-byte, ASCII space, TAB, NUL, DEL, NBSP, malformed UTF-8, slash, backslash and single slash/backslash. Positive one-byte, 80-byte and punctuation/quote values reach the denied authorizer with the exact correction capability and no repository lookup, demonstrating the storage-safe grammar without invented lineage evidence.

Shape-valid filename/reason changes on a terminal request preserve the existing replay and close the unread stream, with only the terminal lookup observed and prior evidence unchanged. This proves metadata normalization does not add a replay/fingerprint equality constraint.

Generated-ID coverage uses the same complete bad-ID matrix for initial root and correction revision. Each malformed generated value must map to retryable `FAILED/PERSISTENCE_FAILURE` after one failing source call, with no retry or next-source allocation, no finalize/lease/commit/audit/delivery, exact abort/stage-close/stream-close, and unchanged preexisting correction evidence. Positive one-byte, 80-byte and punctuation values are persisted unchanged in their correct root/revision field.

Positive controls are independently fixed and exercise valid filenames, reasons, leap day, caller IDs, generated IDs and terminal replay. Initial success yields exact Example A; correction success retains root `original-0001`, allocates only `revision-0002`, persists revision 2 and the normalized reason. The authorizer accepts the exact upload and correction capabilities, correcting the preliminary authoring issue before authoritative RED.

Fixtures are deterministic and in-memory. Business counters cover authorization, every repository operation, composition, clock, stage begin, root/revision IDs, inspector, lifecycle, storage observer and delivery. Evidence snapshots include accepted and attempt facts. There is no primary evidence, OS state, DB, file, permission or remote dependency.

Independent reproduction produced:

```text
passes=42 failures=152 cases=194
exit=255
```

This exactly matches the archive. The raw log intentionally contains malformed UTF-8 fixture bytes in assertion diagnostics; it was compared and cited by byte SHA-256. Only the count display was processed bytewise, without treating replacement text as evidence. Both PHP artifacts lint and `git diff --check` passes.

No blocking traceability, expected-value independence, branch constructibility, ordering, sensitivity, determinism or isolation finding remains. Minimal GREEN may implement only the approved scalar validation, normalization and generated-ID grammar without altering other original-command behavior.

## Required changes

None.

Lifecycle/storage callback completeness, response-loss clarification, public declaration parity, combined command Gate 5 and release readiness remain separate scopes.
