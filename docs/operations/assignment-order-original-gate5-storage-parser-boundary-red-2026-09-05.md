# Assignment-order original Gate 5 storage/parser/boundary RED

- Date: `2026-09-05`
- Gate: `2` corrective RED after Gate 5 findings 1, 2 and 7
- RED author: `Codex agent /root/command_gate5_red_storage`
- Reviewed implementation baseline: `6c4fb5b70065cabb19adab23f6004714c1f0699a`
- Finding record: `fa97cfd5f5ca900424bcf9863eff66ed6b701a2e`
- Outcome: `INTENDED RED`; no production, OpenSpec tasks or domain tests changed.

## Exact approved truth

```text
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
```

## Exact executable artifacts

```text
16f5e9378388e96199e4d6cbdcfef23361169052aa46102d58e779764ef8fefc  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
b5b0417669e30f3edc9d3f0d07590537a64e3da5890f847344e7a2586fa279e9  tests/Support/AssignmentOrderOriginalPdfCorpus.php
8b162fe38a8df31637eebf317392419ebb94e9ec54205898242bf418d4a26b25  tests/InstallationProcess/assignment_order_original_private_bytes_restart_001_test.php
dc7f867627ae0f3f5e328ec7db6078f58f0e14649788b8f5cb048e88f223a0a2  tests/Support/assignment_order_original_private_bytes_restart_probe.php
619b08ef2a8dd9afd5e630b511b394f64ee818d106ecc2d6c7743879ba0e1108  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
```

The storage test writes unpredictable task-owned marker bytes through the real
stage/finalize/lease seam, releases all process-local references, and launches a
fresh PHP process. The child recursively reads only that isolated private root
and requires exactly one regular non-symlink file with the independently known
SHA-256 and received byte size. No credential, repository path or production
payload enters the fixture.

The parser amendment adds a valid bounded incremental classic-xref `/Prev`
chain, impossible `startxref`, out-of-file xref entry, decoded PDF-name escape
and indirect active action. It retains the existing classic/xref-stream/object-
stream positives and the existing encryption, active-key and structural bounds;
it does not claim malware, signature, OCR or content-stream inspection.

The construction test accumulates findings rather than stopping at the first
one. It requires the approved production factory and verifies no-create private
root, exact mode, symlink and protected-parent rejection; existing safe-log
`0600`/non-symlink validation; and orphan marker token, configured-root
disjointness, injected clock, injected primitive failure, collision and
unchanged-inventory behavior. Every path is random, isolated under the system
temporary directory and recursively cleaned in `finally`.

## Literal RED transcripts

```text
$ php tests/InstallationProcess/assignment_order_original_private_bytes_restart_001_test.php
Expected child evidence: {"count":1,...}
Actual child evidence:   {"count":0,...}
exit 255
```

This is finding 1: finalize records metadata but no immutable byte file survives
the fresh process.

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
A bounded valid incremental Prev chain is accepted after parsing every revision.
Expected: PASSIVE_PDF
Actual: INVALID_PDF
exit 255

Independent focused status probe on the same implementation:
impossible startxref => passive_pdf
escaped /Java#53cript => passive_pdf
```

This is finding 2, not a fixture/setup failure.

```text
$ php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
INTENDED_RED: approved production boundary is incomplete:
production factory: absent
private root no-create: accepted
private root mode: accepted
private root symlink: accepted
private root protected chain: accepted
safe log exact 0600: accepted
safe log symlink: accepted
orphan marker token: accepted
orphan production-root disjointness: accepted
orphan injected clock future: accepted
orphan injected primitive fault: accepted
orphan collision: accepted
exit 255
```

This is finding 7. The additional unchanged-inventory assertion also detects the
current invalid/fault fixture mutations. All five changed PHP files pass
`php -l`; `git diff --check` exits `0`.

Gate 4 correction remains forbidden until a separately tasked independent Gate
3 reviewer records explicit `APPROVED` for these exact executable artifacts.
