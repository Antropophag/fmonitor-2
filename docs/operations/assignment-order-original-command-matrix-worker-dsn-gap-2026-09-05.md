# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — command-matrix worker DSN gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Setup Gate 5 base: `dcb9b8f2ccc7f0557a9c5d63489545f5ec7849ef`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

The exact worker DTO contains `databaseDsn: string`, `databaseUser` and a
password-file path, but no separate database host/port/name. V14 names no
grammar for `databaseDsn`, no canonical example, no allowed character/bounds
contract and no mapping from that string to the mysqli connection required by
the production repository.

The only two occurrences are the property declaration and the prose “opens the
shared MariaDB DSN”. Therefore a five-FD parent cannot independently construct
the approved serialized worker config: plausible PDO syntax
`mysql:host=...;port=...;dbname=...`, URI syntax and project-specific JSON/text
syntax are observably different public inputs. Choosing one in Gate 2 would
invent the worker bootstrap contract and make expected failure/exit behavior
implementation-dependent.

```text
$ rg -n "databaseDsn|DSN|dsn" specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md openspec/changes/replace-pilot-registration-with-original-upload/design.md openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:1403: public string $databaseDsn,
specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:1462: opens the shared MariaDB DSN/prefix
```

Smallest amendment: replace `databaseDsn` with the already approved evidence
config's exact host/port/database fields, or define one literal DSN grammar,
escaping, length bounds, redaction, invalid-input outcome and mysqli mapping.
Fresh Gate 1 review and technical approval are required before worker/CAS RED.

Partial additive Gate 2 work retained without claiming task completion:

- owned parser corpus and algorithm guard, including classic/xref-stream/
  object-stream positive documents, malformed/truncated/zero/encrypted,
  every forbidden active key, structural filter and object bound;
- Gate5-approved setup followed by fresh independent evidence-reader config,
  exact empty original/request/fingerprint/event/audit/blob/log shapes, all six
  process digests and idempotent close.

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
INTENDED_RED: approved FMonitorPassivePdfInspector production seam is absent.
RED_ASSERTION: expected failing behavior observed
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_evidence_reader_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalEvidenceReaderFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed
$ independent SCHEMATA/PROCESSLIST query
NO_AOOU_DATABASE_OR_CONNECTION_LEAKS
$ git diff --check
PASS (no output)
```

Task 4.1 remains unchecked. No production, specification or OpenSpec artifact
was edited.

```text
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
a73c43a61d96d6aa007929da19548e6b23da46bac7a159fb389148e5e56caa92  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
aa9a4fe04d4bbe4d5377cc6be258b96c6322784d2ee14f9807bf495313db9b67  tests/Support/AssignmentOrderOriginalPdfCorpus.php
60d9a70bdf4184ed9988865e8c0a2a4341750d919e7cc77fb89b7c81f75ab2e7  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
fb687d2682c8cf4ed45420b5372c63ec0b69210866b23d5d975a9ae305876076  tests/InstallationProcess/assignment_order_original_evidence_reader_001_test.php
```
