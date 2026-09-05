# Gate 3 test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 storage/parser/boundary v3

- Date: `2026-09-05`
- Reviewer: `Codex agent /root/command_gate5_storage_gate3_v3` (fresh independent reviewer; did not author the reviewed tests, production implementation, or planning artifacts)
- Corrective RED commit: `3590010cb253cf4b0e09a5c0f2382bf1e8d881cb`
- Previous corrective RED: `bc1c917b108ab9ade17c2922e37c9739aa8ee6c4`
- Prior Gate 3 findings: `735a903f38dfef8297676c825bc44af21133747b`, `df89440627b74ac9f063ca341024e6014f721c01`
- Production baseline: `6c4fb5b70065cabb19adab23f6004714c1f0699a`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Verdict: `CHANGES_REQUESTED`

## Exact reviewed identities

```text
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
10f29221e3576e159b4fed2342591eb45138f9e22352ad4b91851df5f8f5c2dd  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ca7f0f48a84c0b695cccd88fd76a7e01bb58d72aee11bba2603395628d1e1eb1  tests/Support/AssignmentOrderOriginalPdfCorpus.php
8b162fe38a8df31637eebf317392419ebb94e9ec54205898242bf418d4a26b25  tests/InstallationProcess/assignment_order_original_private_bytes_restart_001_test.php
dc7f867627ae0f3f5e328ec7db6078f58f0e14649788b8f5cb048e88f223a0a2  tests/Support/assignment_order_original_private_bytes_restart_probe.php
f47a57ea01f37f6bb30b6a09770f2b22629156f67edca9b68ecb6bfc68131c61  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
44f1f7315c14f6ab8b945ad829eb08cd07951d1efba7f78e3ab0d4a106887f96  docs/operations/assignment-order-original-gate5-storage-parser-boundary-red-v3-2026-09-05.md
```

Concurrent domain production was excluded. The reviewed tree was clean at the
exact corrective commit.

## Oracle review

The production-factory correction closes the earlier sensitivity finding. It
creates an isolated database only through the approved process, identity,
capability and assignment-original migrations, seeds Example A, and calls the
public production factory with an existing mode-`0700` root. The submitted
INITIAL command is shape-valid and authorized by that fixture. Its oracle
requires the exact accepted tuple (including request ID, independently computed
digest and byte size), then observes the isolated MariaDB request and private
metadata through the approved evidence reader and independently enumerates one
regular non-symlink private file containing the exact original bytes. Table
inventory is unchanged across construction/use. Arbitrary stub bindings cannot
satisfy these combined durable DB and filesystem effects.

The prior parser corrections remain sensitive. The reachable fixture has no
direct forbidden key in the Catalog and differs by the allowed `/Names 4 0 R`
edge; the otherwise identical physically present forbidden dictionary is
expected passive when unreachable. Focused observation on the baseline gives
`reachable=unsafe_pdf`, `unreachable=unsafe_pdf`, proving the graph-traversal
distinction remains RED. The valid bounded incremental `/Prev` fixture remains
RED as `invalid_pdf`, before those later assertions execute.

The restart test uses the real stage/write/finalize/release boundary, drops its
references, and starts a fresh PHP process which hashes physical files. It
remains RED with exact child count `0` instead of `1`. The production-boundary
run removed its randomly named database and task-owned control root in
`finally`; post-run checks found neither residue. Negative canonical-root,
mode, symlink, protected-chain, safe-log and orphan-fixture controls remain
present.

## Blocking finding: the claimed exact transcript is still incomplete

The v3 evidence says `Exact fresh transcript`, but it does not contain the
literal complete combined output of the command it records. The real invocation
starts with `PHP Fatal error:  Uncaught TestFailure: ...`, includes its expected
stack trace and `thrown in ... on line 29`, then repeats the exception under
`Fatal error: ...`. The evidence contains only the second heading and the
failure labels, and omits both stack traces, source suffixes and the first PHP
fatal block.

The missing `private root no-create: path created` line from v2 is now present,
but adding that line alone does not make a transcript labelled exact into the
literal complete transcript required by the append-only evidence process. A
fresh evidence record must capture the actual stdout/stderr verbatim (or stop
calling the abbreviated semantic excerpt exact), include the exact exit status,
and record fresh hashes. Existing v1-v3 evidence must remain unchanged.

## Independent reproduction

All three executable tests are genuine RED on the reviewed baseline:

```text
assignment_order_original_private_bytes_restart_001_test.php: exit 255; fresh child reports count 0, expected 1
assignment_order_original_pdf_parser_001_test.php: exit 255; valid incremental Prev is INVALID_PDF, expected PASSIVE_PDF
assignment_order_original_production_boundary_001_test.php: exit 255; complete failure list matches v3 labels, including `private root no-create: path created`
```

All three tests pass `php -l`; `git diff --check 3590010^ 3590010` exits `0`.
No specification, task, production code, role, authority, or product contract
was changed by the corrective commit. Gate 4 for this corrective batch remains
forbidden until append-only transcript evidence is corrected and a fresh
independent Gate 3 records explicit `APPROVED`.
