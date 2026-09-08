# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker command/base64 Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved worker-ID base: `b95f0449da25e3ebb139dce61732c4d1359ff990`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

V19 closes ID-sequence grammar. The required five-FD worker command still has
no exact serializable upload mapping. The application DTO declares
`upload.stream` as an object implementing a byte-stream interface. The worker
prose says only “keys match Command” and “PDF bytes base64”. It does not state:

- whether JSON uses `upload.stream`, `upload.base64`, `pdfBase64` or another key;
- whether `stream` is directly a string or a nested tagged object;
- standard versus URL-safe alphabet, required/forbidden padding, whitespace,
  canonical re-encoding and strict invalid-character rules;
- encoded/decoded length relationship to the 29,000,000-byte line bound;
- whether malformed/noncanonical base64 is worker exit 70 or application
  `REJECTED/INVALID_COMMAND`;
- whether base64 validation occurs before password/DB access and which V17
  channels apply.

`AssignmentOrderOriginalByteStreamFactory::fromBase64(string)` names a method
but defines no input grammar/outcome and does not resolve the JSON field shape.
Thus neither a valid command line nor invalid/pre-read controls can be authored
with independently derived bytes. Choosing standard padded base64 in
`upload.stream` would invent public worker behavior.

```text
$ rg -n "PDF bytes base64|base64|Command pipe|upload:|stream:" specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
45: upload: {
46:   stream: single rewind-independent byte stream
1397: public function fromBase64(string $base64)
1517: keys match Command, enum backed strings, PDF bytes base64
```

Smallest amendment: publish one literal canonical command JSON example and
define exact upload key shape, standard alphabet/padding/canonical grammar,
strict decode/size ordering, malformed outcome and pre-secret/channel policy.

Task 4.1 remains unchecked. Existing parser/evidence partial RED remains valid;
no production, test, specification or OpenSpec artifact was edited.

```text
e9bc402f092de6e03d67573f40dcd6bb94f25005a44a0cab65fc869da1b06ef7  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
60495390c885ad6524ced4e8beb0c23773e01795c5d6e7c921e5da9b2ad15afb  openspec/changes/replace-pilot-registration-with-original-upload/design.md
598ceb236c129d2588f9b4a48bb51ab98a8c4825014302ece156256e4b7e704a  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
3ddf2ce48ede89d255a3f6ff2f7b5c3c262f7ca94a17dd832f6254f5f159f9b8  docs/operations/assignment-order-original-worker-id-sequence-gate1-gap-2026-09-05.md
```
