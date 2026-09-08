# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v21 worker command encoding — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_worker_command_rereview`
- Reviewed commit: `c697975dccdf294d7a47eb055cff44e16fe98f1a`
- Prior review: `404c10ca34bb8efd263e335af6364027adfa7c04` / `docs/operations/pilot-assignment-order-original-worker-command-encoding-gate1-review-v20-2026-09-05.md`
- Scope: corrected technical worker command/framing/base64 amendment and coherence of the complete current executable specification/OpenSpec package; no tests or production implementation reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Findings

No blocking findings remain. V21 closes all three findings from the v20 review
without changing product behavior, roles, capabilities, public application
command semantics, composition/opening behavior or the blocked legacy flow.

### G1-V20-01 — closed: literal canonical command fixture

The executable specification now contains one complete literal initial-command
JSON line. Independent extraction verifies:

- valid UTF-8 JSON with exactly 12 ordered top-level keys and exactly three
  ordered `upload` keys;
- all initial lineage/reason fields are explicit `null`, while IDs, date,
  boolean, filename and media type satisfy the existing DTO grammar;
- the source fixture line ends in byte `0x0A`, and the specification explicitly
  makes that single final LF part of the wire fixture;
- the complete line is 835 bytes including LF;
- `upload.bytesBase64` uses canonical padded standard RFC 4648 encoding and
  round-trips byte-exactly;
- decoded content is the existing 327-byte positive PDF oracle with SHA-256
  `4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`.

### G1-V20-02 — closed: exact pre-secret resource precedence

V21 normatively orders scalar/path metadata configuration validation first,
then the entire command framing, UTF-8, JSON, exact-key/type, base64 and stream-
factory validation. Password-file content, MariaDB connection, private storage,
safe-log, application invocation and barrier access all occur only afterward.
The OpenSpec scenario and design summary carry the same pre-password/pre-DB
rule. Thus malformed command input has one testable resource-access outcome.

### G1-V20-03 — closed: bounded and observable framing

The worker reads chunks of at most 65,536 bytes into one buffer that never
exceeds 29,000,000 bytes. It establishes the first LF as the final buffered
byte and then observes EOF/no extra byte under a five-second monotonic deadline
before UTF-8/JSON/base64 work. EOF-before-LF, data after LF, overflow and timeout
are framing failures. This excludes unbounded reads, parsing before completed
framing, and decoded construction before the wire bound is proven.

The post-framing order is literal and independently executable: UTF-8 → JSON →
exact keys/types → base64 alphabet/padding → the sole strict decoder
`AssignmentOrderOriginalByteStreamFactory::fromBase64`. That factory performs
the one decoded allocation, validates re-encode equality and returns the
stream; bootstrap retains no second decoded-byte copy.

## Acceptance assessment

- Malformed framing, UTF-8, JSON, keys/types and base64 retain the exact
  controlled exit-70 contract: one fixed stderr line, empty result channel and
  no barrier bytes because parsing precedes barrier access.
- Empty canonical base64 remains transport-valid and reaches application file
  validation.
- A decoded 20,971,521-byte payload has 27,962,028 base64 bytes, fits under the
  29,000,000-byte command ceiling with bounded metadata, and therefore reaches
  the application `FILE_TOO_LARGE` boundary rather than being reclassified as
  a transport failure.
- The command adapter creates the application byte stream only from decoded PDF
  bytes, so the existing received-byte contract counts PDF bytes rather than
  base64 transport text.
- The delta spec, design and executable spec are coherent; OpenSpec strict
  validation passes.

This approval closes task 1.17's independent Gate 1 review requirement. It does
not approve Gate 2 tests or production code and does not itself mark task 1.17
complete; the integrator owns append-only task/evidence bookkeeping.

## Verification

```text
$ git rev-parse HEAD
c697975dccdf294d7a47eb055cff44e16fe98f1a

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 404c10c..c697975 --check
PASS (no output)

$ literal-fixture independent jq/base64/hash/line-ending check
json_bytes_with_lf=835
top_keys=12 upload_keys=3
pdf_bytes=327
pdf_sha256=4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784
base64_roundtrip=OK
fixture_source_line_ending=10
key_order=requestId,mode,installationCaseId,assignmentOrderId,actorUserId,documentDate,compositionConfirmed,rootOriginalId,targetRevisionId,expectedCurrentRevisionId,correctionReason,upload
upload_key_order=bytesBase64,originalFilename,declaredMediaType
```

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
c05e7b18b706551a1bd577ac0cdb8477c481d73e2e3e31c14a33db7cfbe41c0f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
e601922888e9e38970f3f828381b2cf944d2cb938de3be0932d6bc2f3dfcc4d1  openspec/changes/replace-pilot-registration-with-original-upload/design.md
587976abf1422e515f48f4f7ae25b643d03e4bf60cef4cf571f453173350dfbe  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
359bb6952394f090c28812152fff75a3fde713870f7b0d2603fb8bbf5ccffdfa  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
86046faa28f44665d50ce2f0549d9c6b64254819c98b89ce34d3e3ff862b02f7  docs/operations/assignment-order-original-worker-command-base64-gate1-gap-2026-09-05.md
a9bff7ac50ef67c893872138b09c80db614d8a13385bfa24e6cf9d0b0d3f102f  docs/operations/pilot-assignment-order-original-worker-command-encoding-gate1-review-v20-2026-09-05.md
```

This review record omits its own circular hash.
