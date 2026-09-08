# Test review: ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit authored by Timofey Grishin
- Reviewed commit: `1afdec2ea26651ee4cf81ab45b37900e7e3df843`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001.md` v0.2, SHA256 `482b5153e84e4dfe51ff75b526458fdd974e503a2d6f4b31db880a1f15c4ba54`
- Public seam: pure `AssignmentOrderOriginalSafeLogAttributePolicy`, opaque `AssignmentOrderOriginalOpenedSafeLog`, compatibility `AssignmentOrderOriginalFileSafeLog`, direct Runtime/FileStorage imports, and `ProductionAssignmentOrderOriginalFactory::create`
- Red command and intended failure: `php tests/InstallationProcess/assignment_order_original_safe_log_owner_001_test.php`; exit `1` at the explicit missing owner/policy direct-import assertion after successful stable fixture setup and exact cleanup
- Verdict: `APPROVED`

## Exact reviewed inputs

```text
482b5153e84e4dfe51ff75b526458fdd974e503a2d6f4b31db880a1f15c4ba54  specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001.md
1b41e346d51444464ac0ffd7afb779302c32be9f51bc9ee05b092d31cd3f1460  docs/operations/safe-log-shared-owner-gate1-review-v02-2026-09-06.md
5c0cbb4ff6527184c0effabe435556fb7e0c7fd42b5e31fd57a150841d17885b  tests/InstallationProcess/assignment_order_original_safe_log_owner_001_test.php
31f04cde588cd76c6971936468e9589f43778cbf934a860c1b8cdd55b457d4e4  docs/operations/safe-log-shared-owner-red-v1-2026-09-06.md
```

The exact v0.2 specification has independent Gate 1 `APPROVED`. This review covers the new stable public behavior test only. The existing production-boundary regression remains a separate approved test and was not invoked during this focused review.

## Findings

Traceability and seam choice are complete. The test cites the exact approved contract and loads the existing Runtime and FileStorage files directly with `require_once`, deliberately bypassing autoload. `class_exists(..., false)` for both new public classes makes missing dependency wiring an explicit RED instead of allowing the production factory's broad error mapping to disguise it as a valid configuration denial. Subsequent assertions call only the specified public policy, owner, compatibility facade and production factory APIs. The test uses no private reflection or implementation constants.

The pure-policy matrix is independently literal. It fixes the valid regular-file/exact-0600 tuple and changes mode, type, UID, device, inode and effective UID one axis at a time. Separate setuid, setgid and sticky-bit literals prove that `07777`, rather than only access bits, governs exact mode acceptance. Literal UID `0` proves root is accepted when actual and expected identity agree. These inputs perform no filesystem permission or privilege transition.

A real valid owner control precedes all invalid acquisition and factory cases, preventing a globally rejecting owner implementation from satisfying the test. It proves initial line counting, exact ordered JSON, the two fixed correlation prefixes, per-owner sequences `2` and `3`, one-LF appends, preservation of prior bytes, request switching, explicit and repeated close, public closed state, and rejection of record/request mutation after close without reopen or byte changes.

Serialization and direct unserialization must return the fixed redacted error while leaving the live owner usable; cloning must be unavailable through ordinary PHP syntax. The compatibility facade must preserve the same exact string-constructor output. Stable invalid-mode acquisition must preserve bytes, identity, permissions and timestamps, while missing-path acquisition must not create a file. The canonical compatibility query must return the resolved valid path.

Factory-order sensitivity is adequate in composition with the valid owner control and the separately approved production-boundary regression. Invalid existing and missing safe-log paths must map to the exact production configuration exception before either overridden public database method is called and before the absent private root is accessed or created. A missing class cannot satisfy these cases because direct-import availability and successful owner behavior are asserted first.

Expected output is independent of production behavior. File bytes, request IDs, correlation values, sequence numbers, policy tuples, fixed exceptions and expected states are literals from the specification. Actual file metadata is used only to prove identity and preservation of task-owned fixtures; it does not supply the expected policy matrix or log content.

Setup is deterministic and isolated. The test creates random task-owned sibling directories under the resolved system temporary directory, restores every temporary umask in `finally`, and creates each file once with exclusive `x+b`. The valid, compatibility and decoy files are initially `0600`; the invalid file is initially `0640`. No chmod/chown, permission interval, privilege change, observer, interception, native hook, resource inventory, database, production data, real document, or external system participates.

Cleanup tracks exact file and directory device/inode/type identities before unlink/rmdir, aggregates failures, and preserves the sibling decoy throughout owner operations. The focused RED occurs after all four stable files are constructed. Its output contains only the intended assertion, demonstrating that umask restoration and exact teardown completed without a secondary failure.

Independent reproduction at reviewed commit:

```text
$ php tests/InstallationProcess/assignment_order_original_safe_log_owner_001_test.php
RED_ASSERTION: shared safe-log owner and policy are missing from direct runtime imports
Expected: true
Actual: false
```

Exit status: `1`. Classification: intended missing public owner/policy RED, not an import, fixture, cleanup or environment failure.

## Mandatory evidence limit

This stable-file black-box test cannot distinguish descriptor metadata obtained through real `fstat` on the retained handle from an erroneous repeated pathname `lstat`. It also cannot prove successful kernel-level descriptor closure, the native-close false/warning/Throwable branches, cached close failure, absence of repeated native owner I/O, or exact handle data flow.

Gate 5 must inspect the exact implementation source and prove every structural obligation in specification section 7: descriptor mode/UID/device/inode flow into policy; final non-following pathname identity and native effective UID; one retained handle for count/append/close; no raw-handle adoption or pathname reopen; one close attempt after every post-open failure; cached close results and destructor behavior; facade delegation without an alternate writer; and factory acquisition before database/private-root work. Behavioral GREEN alone cannot close `G5-SAFELOG-2` or receive approval.

Within this required proof split, no blocking traceability, public-seam, expected-value independence, sensitivity, rejected-case, determinism, setup-isolation or cleanup finding remains. Minimal GREEN may proceed without changing the approved expectations.

## Required changes

None.
