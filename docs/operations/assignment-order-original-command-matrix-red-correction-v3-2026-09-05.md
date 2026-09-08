# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — command matrix RED correction v3

Date: `2026-09-05`

Correction author: `/root/assignment_command_gate3`; this author must not review
the corrected batch. Authority: Gate 3 v2 `18aaf2d` CHANGES_REQUESTED.

Outcome: **INTENDED RED — all v2 findings corrected**.

- Worker transport now runs identical and different races against two separate
  fresh databases, private roots, password files and safe logs. Each proves a
  complete exact initial baseline; different-race additionally proves its exact
  revision-2 prerequisite. Both READY snapshots are byte-equal to the full
  baseline. Winner is observed before loser release, and complete final domain,
  request, fingerprint, event, audit, process, blob and log inventories are
  checked without filtering/reset. Identical loser retry is byte-idempotent.
- Command framing executes an exact `29,000,001`-byte line, reordered top-level
  keys, reordered upload keys, and canonical empty base64. The first three have
  exact exit-70/no-stdio/no-barrier/no-result channels; empty bytes reach the
  application and return exact `REJECTED/NOT_PDF` with worker exit 0.
- FD protocol executes real directory and FIFO descriptors in addition to
  regular file, device, closed, stdio, range, duplicate and aliased socket
  cases. A shutdown cleanup handler bounds FIFO/directory/config/root cleanup.

No production, specification or OpenSpec file changed. Task 4.1 remains
complete and task 4.2 remains pending another fresh independent reviewer.

## Reproduction

```text
$ php -l tests/Support/assignment_order_original_isolated_races.php
No syntax errors detected
$ php -l tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
No syntax errors detected
$ php -l tests/InstallationProcess/assignment_order_original_worker_protocol_001_test.php
No syntax errors detected

$ tools/verification/run.sh red <eight focused suite paths>
Each suite: its approved INTENDED_RED missing production seam
Each wrapper: RED_ASSERTION: expected failing behavior observed
Each wrapper exit: 0

$ php tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK

$ independent information_schema SCHEMATA / PROCESSLIST query for t_aoou_%
0
0

$ independent /tmp and /private/tmp verifier-root inventory
no matching roots

$ git diff --check
PASS (no output)
```

## Exact corrected hashes before this evidence file

```text
13dedd0a6d8b06e419b3d5c61e690d637768899b0e845f11584b4b55538cee1c  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
a120ab2f22af2dc10a36d09dd047735bb87efc2129b4b7e01d40de19de56a759  tests/InstallationProcess/assignment_order_original_worker_protocol_001_test.php
257f7399e5dc35c8f568c5a865e07722f45e85b65ccc6410fe34652e3821dc25  tests/Support/assignment_order_original_isolated_races.php
```
