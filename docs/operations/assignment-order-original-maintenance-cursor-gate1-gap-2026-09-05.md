# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — maintenance cursor Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved orphan-fixture base: `3ed7bea1bf9d18c1318b03e07c9dec499ac3c234`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

V36 provides exact eligible orphan fixtures and real maintenance bindings. The
maintenance pagination cursor remains described only as “storage-generated
base64url without padding encoding the last `(createdOrFinalizedAtUtc,
opaqueIdentity)` pair”. The bytes used to encode that pair are not defined.

Missing details include tuple delimiter or length-prefix grammar, timestamp/
identity framing, version/tag, UTF-8/ASCII rules and one canonical example.
JSON array, NUL-delimited fields and length-prefixed fields all satisfy the prose
but produce different `nextCursor` values. “Non-canonical/unknown” rejection
cannot be independently distinguished without the decoder grammar.

A test can feed back an implementation-returned cursor, but then production
output becomes the expected-value oracle and cannot catch a stable wrong codec.
Task 4.1 requires independently derived values and maintenance candidate/cursor
sensitivity; its evidence methods persist exact `nextCursor`.

Smallest amendment: define one exact cursor payload encoding and base64url
algorithm, publish the exact cursor for the canonical timestamp/identity pair,
and define malformed, noncanonical and well-formed-unknown examples/outcomes.

Task 4.1 remains unchecked. Existing parser/evidence partial RED remains valid;
no production, test, specification or OpenSpec artifact was edited.

```text
85e0584008ce452d1ab8c364e989cec447aee4d6945461e81a72bdb0572f9bb2  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
012ca687d9cf6a3733648a874c59424b636a772707b33a35e7b9285be75a579a  openspec/changes/replace-pilot-registration-with-original-upload/design.md
c4c4ba0a29da1451c6fddf42531498f8b6e0c31891cf018adb81929206c464b4  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
62af4bd87ecc3c9f66168470c956ab340a802bb2efd852010878d0bb123cfd2b  docs/operations/assignment-order-original-maintenance-age-fixture-gate1-gap-2026-09-05.md
```
