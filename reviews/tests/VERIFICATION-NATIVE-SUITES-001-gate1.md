# Independent Gate 1 — VERIFICATION-NATIVE-SUITES-001

- Verdict: **APPROVED**
- Reviewer: `/root/original_gate1_v3`, separately tasked agent; not the author of the specification, planning artifacts, tests or runner changes.
- Date: 2026-09-07
- Reviewed HEAD: `9488814b4f1ecd16a458dc435cd2989a42349db1`.
- Reviewed specification: version 0.1, SHA-256 `2db7bebc7d4d3ded6affe4a6dbe1dd00dfe2bd152237d8447375a46d39e0d08e`.

## Findings

No blocking findings. The CLI is the explicit public tooling seam. `list unit|db` has observable output, deterministic ordering, no interpreter/database execution or file writes, and a specified setup-failure outcome for malformed requests and missing required directories. Execution uses the same inventory, identifies each verifier, preserves all failing paths and returns failure only after the remaining suite members have run. Missing Node cannot silently reduce coverage.

Membership is closed and independently checkable: existing InstallationProcess classification is preserved, the three named in-memory selection tests are unit tests, all other discovered AssignmentOrderComposition PHP tests default to db, and Verification Node tests belong to unit. Read-only inventory inspection confirms 22 current PHP members in the standalone family (three named unit members and 19 db members), plus the current Node client test. The worked synthetic example independently yields four PHP and one Node unit members and two PHP db members; it also requires discovery of a new native filename rather than a frozen current-file list.

The trace-only interpreter harness is appropriately restricted to scheduler tooling in an isolated synthetic tree. Its externally assigned outcomes can prove aggregation and ordering without intercepting a production command, PDF transport or native persistence boundary. It cannot prove the actual native tests passed. The normative specification separately requires a real canonical execution of all 22 PHP tests and Node against synthetic MariaDB, followed by full `make verify` after integration. Existing characterization, E2E, lint and RED behavior remain required, with no new exclusions or protected-test edits.

The OpenSpec proposal, design and delta defer to the normative contract and introduce no product behavior, schema, deployment or remote action. The tasks explicitly preserve completion of the source `7cb79d0` verification run before runner/test edits. The design places full verification after the source commit; task 2.3's local delivery checks do not waive the specification's full-verification requirement or authorize a premature `VERIFY_OK` claim.

## Review evidence and boundaries

Read the specification and all four OpenSpec planning artifacts, current `tools/verification/run.sh`, relevant Makefile targets and the handoff's native-family integration obligation. Reused the already-read repository constitution and mandatory product/delivery documents. Only read-only inspection was performed; the running full verification was not touched. No runner or test was executed, authored or changed in this review.

| OpenSpec artifact | SHA-256 |
| --- | --- |
| proposal.md | `c529fc270558ca1bf69b69327dee978e383ba122e0fe0cf2a2d48a232d0db893` |
| design.md | `1bc8ee015bda9d427755e3f05ab017cad4201e7548ed56ceddae272f99f38ea0` |
| tasks.md | `787730096cdae1e25fd5151d8046ed7ca0f9aad805850240527c6a1a76e4f6e5` |
| specs/delivery/native-verification-suites/spec.md | `ceec8340bee93c140807cd6e8f6ecbe79f0aa9a76b6d4acf9e6c582481f443e9` |

Gate 1 only is **APPROVED**. Demonstrated RED, independent Gate 3, minimal GREEN, real execution evidence and independent Gate 5 remain required.
