# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker Result JSON Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved command-transport base: `8260ff32a8ffe56db2915101029e0512104f02a6`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

V21 defines the command line byte-for-byte, including key order, nested upload
shape, RFC 4648 base64 and framing. The corresponding successful result channel
still says only “one canonical JSON line”. It does not define:

- Result top-level key order (DTO declaration order versus binary key sort);
- enum encoding and explicit-null policy;
- integer/boolean representation and UTF-8/slash escaping flags;
- one exact accepted, replayed or conflict line including final LF;
- whether the recursively key-sorted evidence canonicalization also governs
  worker Result, or is intentionally separate.

Both DTO order and binary-key order are plausible canonical encodings and have
different bytes. A Gate 2 parent may decode and check values, but then cannot
detect a worker that violates the required canonical transport representation.
Choosing one order would invent a public IPC contract.

```text
$ rg -n "Result pipe carries|recursively key-sorted|Result \{" specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
117: Result {
1433: Evidence JSON uses recursively key-sorted UTF-8 JSON
1550: Result pipe carries exactly one canonical JSON line
```

Smallest amendment: define exact Result keys/order, backed enum strings, explicit
nulls, JSON flags, numeric/boolean representation, single final LF/no extra
bytes, and publish the exact Example-A accepted line plus replay/conflict forms
used by the two-worker matrix.

Task 4.1 remains unchecked. Existing parser and evidence-reader partial REDs
remain valid; no production, test, specification or OpenSpec artifact was
edited.

```text
c05e7b18b706551a1bd577ac0cdb8477c481d73e2e3e31c14a33db7cfbe41c0f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
e601922888e9e38970f3f828381b2cf944d2cb938de3be0932d6bc2f3dfcc4d1  openspec/changes/replace-pilot-registration-with-original-upload/design.md
e41b4260ad5ceb092a456b6f2f9fc8c1d5efb2403dae1efc49bc30769cbea86b  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
86046faa28f44665d50ce2f0549d9c6b64254819c98b89ce34d3e3ff862b02f7  docs/operations/assignment-order-original-worker-command-base64-gate1-gap-2026-09-05.md
```
