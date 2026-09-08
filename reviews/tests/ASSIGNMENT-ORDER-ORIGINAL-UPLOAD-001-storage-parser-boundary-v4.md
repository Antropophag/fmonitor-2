# Gate 3 test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 storage/parser/boundary v4

- Date: `2026-09-05`
- Reviewer: `Codex agent /root/command_gate5_storage_gate3_v4` (fresh independent reviewer; did not author the reviewed tests, production implementation, specification, or RED evidence)
- Corrective RED test commit: `3590010cb253cf4b0e09a5c0f2382bf1e8d881cb`
- Evidence-only correction: `62e07f9e507b88aaf37cfc0ecb05dd09f386311d`
- Prior Gate 3 finding: `d900b712646911e5b834a922143625cbb92f50af`
- Production baseline: `6c4fb5b70065cabb19adab23f6004714c1f0699a`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Verdict: `APPROVED`

## Exact reviewed identities

```text
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
10f29221e3576e159b4fed2342591eb45138f9e22352ad4b91851df5f8f5c2dd  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ca7f0f48a84c0b695cccd88fd76a7e01bb58d72aee11bba2603395628d1e1eb1  tests/Support/AssignmentOrderOriginalPdfCorpus.php
8b162fe38a8df31637eebf317392419ebb94e9ec54205898242bf418d4a26b25  tests/InstallationProcess/assignment_order_original_private_bytes_restart_001_test.php
dc7f867627ae0f3f5e328ec7db6078f58f0e14649788b8f5cb048e88f223a0a2  tests/Support/assignment_order_original_private_bytes_restart_probe.php
f47a57ea01f37f6bb30b6a09770f2b22629156f67edca9b68ecb6bfc68131c61  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
b911d97a67474c19fb8416745889be704907cfdff872fb626991bf67d2c55af4  docs/operations/assignment-order-original-gate5-storage-parser-boundary-red-v4-2026-09-05.md
```

Commit `3590010cb253cf4b0e09a5c0f2382bf1e8d881cb` changes only the production-boundary executable and its v3 evidence. Commit `62e07f9e507b88aaf37cfc0ecb05dd09f386311d` changes only the append-only v4 evidence record. `git diff 6c4fb5b70065cabb19adab23f6004714c1f0699a..62e07f9e507b88aaf37cfc0ecb05dd09f386311d -- app rapid-pilot` is empty, so concurrent domain production is excluded from this Gate 3 frontier. `git diff --check 3590010cb253cf4b0e09a5c0f2382bf1e8d881cb^..62e07f9e507b88aaf37cfc0ecb05dd09f386311d` exits `0`.

## Review

The production-boundary oracle uses the public production factory and exact application seam, prepares only approved schemas and the deterministic Example-A fixture, submits an authorized valid INITIAL upload, and requires exact accepted result evidence, durable MariaDB request evidence, private metadata, one exact immutable PDF file, and unchanged table inventory. These independent effects make the test sensitive to missing or stub production composition. Its canonical-root, permissions, symlink, protected-chain, safe-log, orphan-fixture, collision, fault, and cleanup controls remain intact.

The parser executable retains independent passive/unsafe expectations for bounded incremental `/Prev` parsing and reachable versus unreachable forbidden dictionaries. The restart executable drops in-process references and verifies exact private bytes from a fresh PHP process. Their oracles are stable, deterministic, and tied to the approved storage/parser boundary rather than implementation-private methods.

The v4 correction resolves the sole v3 blocker. Extracting the literal stdout, stderr, and exit blocks from the evidence record produces exactly:

```text
9f2b05a837f06e93c6767fb3dba97389628ceccbbd70aca8a56aab0b45c70f0e  stdout (882 bytes)
377653a12f2d24485c2a169578940c26895c47191d2f5a936430de34946ab3b6  stderr (886 bytes)
ce8bafb38615aeb5d44ebbabe78ec14ac35a5de87bdc5ad5ea82a72656024ce4  exit-status file (4 bytes, exact bytes `255\n`)
```

The blocks include the leading stdout LF, both complete stack traces, both absolute source suffixes, final LFs, and the complete decimal exit status. Prior evidence and reviews remain unchanged.

## Independent reproduction

The three executables were run from a detached clean worktree at exact commit `62e07f9e507b88aaf37cfc0ecb05dd09f386311d`, excluding dirty concurrent production in the integration worktree:

```text
assignment_order_original_production_boundary_001_test.php: exit 255; intended missing factory plus all expected negative boundary findings
assignment_order_original_pdf_parser_001_test.php: exit 255; bounded valid incremental Prev is INVALID_PDF instead of PASSIVE_PDF
assignment_order_original_private_bytes_restart_001_test.php: exit 255; fresh child reports exact count 0 instead of 1
```

The independently captured boundary streams were 888 and 892 bytes because the detached worktree path `/private/tmp/fmonitor-gate3-v4.UYz8DC` is six bytes longer than the evidence path `/Users/antropophag/code/fmonitor-2`; the literal failure content and ordering otherwise match. All four reviewed PHP files pass `php -l`. The randomly named database and filesystem controls were removed by the executable cleanup.

## Verdict

`APPROVED`. The tests are traceable to the v54 executable specification, exercise the intended public and process boundaries, have independently derived sensitive oracles, are deterministic and isolated, and are demonstrably RED solely for the missing storage/parser/production-boundary behavior. Gate 4 may proceed for this exact reviewed test frontier without changing its expectations.
