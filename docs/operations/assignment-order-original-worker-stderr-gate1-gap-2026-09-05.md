# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker stderr Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved worker DSN base: `4fd4fa5e0d9b471b1588a86bbc8e1f5e1ba01ac4`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

V16 now defines an exact valid DSN grammar and mysqli tuple, so the prior DSN
constructibility gap is closed. The required invalid/pre-secret matrix exposes
one remaining closed-output ambiguity.

The contract says invalid DSN/user/config returns exit `70` and **fixed redacted
stderr**, before password content, DB, storage or safe-log access. It does not
state the stderr literal, final-LF policy, byte length or whether DSN, user and
path failures share one literal. The malformed barrier branch likewise says
only “redacted stderr”. Repository search finds no other worker stderr contract.

Gate 2 therefore cannot independently assert the fixed output. Choosing
`WORKER_CONFIGURATION_INVALID`, `SETUP_FAILURE`, an empty stream or any other
message would invent public verification behavior. Merely comparing two future
implementation outputs would prove consistency, not conformance to an
independently derived expected value, and would not catch a consistently wrong
or information-bearing message.

```text
$ rg -n "fixed redacted stderr|stderr|exit 70" specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md openspec/changes/replace-pilot-registration-with-original-upload/design.md openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:1485: fails worker configuration with exit `70` and fixed redacted stderr
specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:1488: malformed/EOF/timeout returns exit `70`, no commit and redacted stderr
```

Smallest amendment: specify one exact ASCII stderr line including final LF for
all config/pre-secret failures, and one exact line for malformed/timeout barrier
if different. State that stdout/result/barrier outputs are empty on those
failures and that neither line contains input-derived bytes.

Task 4.1 remains unchecked. Existing partial parser/evidence RED at `c954714`
remains additive evidence but does not satisfy the worker, command, maintenance,
fault or concurrency matrix. No production, test, specification or OpenSpec
artifact was edited by this gap record.

```text
4a3c83258015868907059b5bb31eb67ecd40d0253203a6c3b3a1b2f154c8fd7c  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
e42050043a9ffc9c61963a89f893e1358c8ad644731b1e5c60066229e3bf19b2  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
aa9a4fe04d4bbe4d5377cc6be258b96c6322784d2ee14f9807bf495313db9b67  tests/Support/AssignmentOrderOriginalPdfCorpus.php
60d9a70bdf4184ed9988865e8c0a2a4341750d919e7cc77fb89b7c81f75ab2e7  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
fb687d2682c8cf4ed45420b5372c63ec0b69210866b23d5d975a9ae305876076  tests/InstallationProcess/assignment_order_original_evidence_reader_001_test.php
530c67f355f123f2de6541192b2c080c30a9f54280cbab3ec085acacabb01bfc  docs/operations/assignment-order-original-command-matrix-worker-dsn-gap-2026-09-05.md
```
