# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker Result-write fault Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved combined-fault base: `8fdad0628a1bac99e1bb45c23f193462e9cb5235`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

The worker Result contract requires distinct sensitivity for serialization/
oversize before write and `fwrite` returning false, zero or a positive short
count. On short write it must not retry and the parent must discard the prefix.

No worker fault enum represents Result serialization, oversize, write failure or
short write. `RESPONSE_DELIVERY` is an application observer fault before Result
serialization and cannot prescribe an OS write return count. Public worker API
accepts only an integer FD, not an injected writer.

Canonical Result fields are tightly bounded and produce a line far below 16384
bytes, so oversize is not reachable from any valid public command. A normal
blocking pipe/socket will accept the small line atomically or block; it cannot
portably/deterministically return an intended positive short count. `/dev/full`
can exercise false/failure but not the required untrusted-prefix/no-second-write
branch. A fake parent-produced malformed prefix tests only parent validation,
not worker write behavior.

Smallest amendment: add verification-only exact fault scenarios for
`result_serialize_oversize`, `result_write_failed`, `result_write_zero` and
`result_write_short_<n>` (one fixed n is sufficient), or expose a verification
writer factory while production binds the real FD writer. Define exact result/
stderr/barrier channels and committed replay behavior for each.

Task 4.1 remains unchecked. Existing parser/evidence partial RED remains valid;
no production, test, specification or OpenSpec artifact was edited.

```text
3d60ecd7f62220e89c256f6115eccc486c148ca2dee9843b314b53a360f5e9d1  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
68b6087ee77eccd8404b63be4f58826df78d29f674da56157e750e65f76ca2ca  openspec/changes/replace-pilot-registration-with-original-upload/design.md
9061728ae6ef7f02867ad67aaaf65dfb4d3924fcb007229a697bd1f07e6fd34d  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
269770f9e8403c8b755e00f32ff9332dbbe4287af23a49722d5b338ed535b7f2  docs/operations/assignment-order-original-worker-combined-release-fault-gate1-gap-2026-09-05.md
```
