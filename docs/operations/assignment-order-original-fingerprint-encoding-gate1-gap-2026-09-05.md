# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — fingerprint encoding Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved cleanup-log base: `c118a0667c461a6e38fa78ea1ad52cba062571a9`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

An exhaustive constructibility pass over the remaining task-4.1 axes found one
independent-value omission. Accepted-operation fingerprint is defined as SHA-256
of a “canonical length-prefixed encoding” of an exact tuple, but the length-
prefix encoding itself is absent.

Plausible encodings—32-bit big-endian byte length, ASCII decimal plus colon,
netstring, or UTF-8 code-point length—produce different digests for the same
approved tuple. No exact initial/correction fingerprint literal or encoded
preimage is published. Consequently Gate 2 cannot independently assert
`fingerprintsCanonicalJson`, typed accepted commit fingerprint, cross-request
replay identity, or identical/different CAS sensitivity. Computing expected via
the future production encoder would make the implementation its own oracle.

Smallest amendment: define prefix width/radix/endianness, byte-vs-code-point
length, field separator/empty encoding and UTF-8 rules, then publish exact
encoded preimage and SHA-256 for Example A and correction Example C.

Other audited areas are currently constructible from approved literals:
worker DSN/IDs/command/result/FDs/faults, composition derivation, cleanup logs,
orphan fixture/age/cursor, maintenance authorization/evidence, parser grammar,
result publisher faults and combined release outcomes. Opaque storage identities
can be tested by grammar/referential consistency and are intentionally not exact
literals. No additional independent Gate-1 omission was found in this pass.

Task 4.1 remains unchecked. Existing parser, evidence, maintenance and worker-
transport partial RED remains valid; no production, test, specification or
OpenSpec artifact was edited by this record.

```text
$ rg -n "length-prefixed|fingerprintsCanonicalJson|fingerprint" specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
157: Accepted-operation fingerprint — SHA-256 canonical length-prefixed encoding exact tuple
1722: public function fingerprintsCanonicalJson(): string
1847: fingerprints = {schema:"aoou-fingerprints-v1",...}
```

```text
2078ffbf83bd738221a9c03215f34a33e717c363fdfc9b31b13fd7e1aca31633  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
3f026d03d4155682f2c5b3ca9221ff973b31474b30317664ecf21c63cd594210  openspec/changes/replace-pilot-registration-with-original-upload/design.md
e10a027bc030a6c4357271e8d71cc67f0c70dbce3b710d01a2bb05d1491f02b0  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
c2cc5e04e288a18ad0c1dc0154176cbd61a50a581a60e365aebf446a6e1b8b84  docs/operations/assignment-order-original-storage-close-safe-log-gate1-gap-2026-09-05.md
```
