# Assignment-order original command — combined Gate 5 readiness inventory

Date: 2026-09-05. Investigator: `/root/selection_contract_reconciliation`.
Observed HEAD: `82d283c9dc5714ec3ecd98d197e3acfc4a433daa` with a concurrently edited
safe-log descriptor-verification amendment in the executable spec and four
OpenSpec artifacts.

Status: **ONE KNOWN COMMAND BLOCKER; OTHERWISE READY FOR FRESH COMBINED REVIEW
AFTER EXACT REBASE AND REGRESSION**.

This is a bounded read-only pre-review inventory. It is not Gate 5, does not
approve the command, does not review the concurrently moving safe-log candidate,
and does not cover downstream HTTP, composition selection, application of
composition or opening. No production/spec/test artifact was edited.

## Concrete blocker shortlist

1. `G5-SAFELOG-2` remains the sole identified command-level blocker. The current
   production logger still validates security attributes on a pathname
   observation and checks only device/inode on the opened descriptor; it does
   not revalidate opened-descriptor regular type, effective UID and exact 0600.
   The current spec/OpenSpec bytes are changing concurrently to define a safe
   verification seam. They require their own exact Gate 1, demonstrated RED,
   independent Gate 3, minimal GREEN and independent corrective Gate 5 before a
   combined review can treat the finding as closed. This inventory neither
   evaluates nor endorses the uncommitted verification technique.
2. No additional blocker was found in the current command, parser, persistence,
   schema/setup, storage, worker, replay/CAS, audit or maintenance surfaces.
   This conclusion is a pre-review shortlist, not an approval: the future
   combined reviewer must inspect the final post-descriptor diff and may find a
   new issue.

## Disposition of prior combined-command findings

- The command v2 review's bounded-parser blocker is closed by the independently
  approved incremental grammar correction at implementation
  `f3afea2d5a0b60eed0d82bebde03174087d8b255`. Current parser hash remains the
  approved `1a59ecc5...`. Classic and stream xref identity/offset handling,
  incremental `/Prev`, object streams, dictionary uniqueness, graph/stream
  framing and active-content bounds are covered by the accumulated corpus.
- The command v2 production-safe-log binding omission is closed: current
  `ProductionAssignmentOrderOriginalFactory` requires `safeLogFile`, constructs
  `AssignmentOrderOriginalFileSafeLog` before private-storage validation and
  binds it into the public application. `G5-SAFELOG-1` parent-component
  canonical-path rejection has independent approval at
  `65988fb6520039def9d00f28a531651e796ef6be`. Only descriptor attributes remain.
- Assignment-scoped initial lineage, full-row composition validation,
  pre-stream drift rejection, authorization-before-replay, terminal attempt
  audit, non-duplicate SQL failure separation, real post-finalize lease barrier,
  response-loss/unknown-commit reconciliation and private-byte restart coverage
  remain present in current production/tests.
- Original schema v2's last quote-aware correction has independent approval at
  `4c7df544a186cd0ee8b4e58f37b8868b7735789c`; current schema/capability/database
  setup tests retain the approved hashes. The later database-setup byte-identity
  correction has independent approval at
  `77c3e5f7d0f29225dcef474093edfeb9953bc585`.
- Maintenance/orphan, lease-race and worker transport/protocol surfaces are part
  of the combined regression even though the ordinary production upload factory
  itself does not call maintenance. Their current tests pass and no unresolved
  review finding was located outside safe-log descriptor integrity.

## Current focused execution result

The exact shell loop below ran every current
`tests/InstallationProcess/assignment_order_original_*_test.php` file in sorted
filesystem glob order. All 18 tests exited zero and emitted their expected OK
marker. Total runtime was about 96 seconds; no test was skipped.

```sh
set -o pipefail
for f in tests/InstallationProcess/assignment_order_original_*_test.php; do
  php "$f" || exit $?
done
```

Observed markers covered capability migration, database setup, evidence reader,
domain and MariaDB Gate-5 matrices, lease race, maintenance, base and incremental
PDF grammar, private-byte restart, production boundary, schema v2, initial/
remaining/authorization command suites, worker post-finalize negative,
protocol and transport.

## Required final combined regression commands

After the descriptor correction is complete and committed at one exact SHA, the
fresh combined reviewer should run this minimum matrix from a clean worktree:

```sh
set -o pipefail
for f in tests/InstallationProcess/assignment_order_original_*_test.php; do
  php "$f" || exit $?
done

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/process_command_authorization_001_test.php
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/production_migration_runner_001_test.php
php tests/InstallationProcess/production_composition_001_test.php

find app/AssignmentOrderOriginal -maxdepth 1 -type f -name '*.php' -print0 \
  | sort -z | xargs -0 -n1 php -l
openspec validate replace-pilot-registration-with-original-upload --strict
make architecture-check
make unit-test
make lint
git diff --check <approved-gate3-base>..<combined-review-sha>
```

Use the repository's documented local MariaDB credential only; do not record
secrets in evidence. If the descriptor test has a platform-specific helper, run
its approved public test command and exact setup/control cases in addition to
the glob, and verify that no production config/environment/runtime selector can
activate it. Do not use the previously rejected or automatically blocked native
technique as an unreviewed substitute.

Before any release/integration claim, separately run `make verify` on the same
exact SHA and require literal `VERIFY_OK`. A passing `make verify` is integration
evidence; it does not replace the fresh independent combined Gate 5 review.

## Fresh-review inputs and diff boundary

The combined reviewer should receive:

- final exact executable spec and OpenSpec hashes after safe-log work stops;
- the approved descriptor Gate 1, RED, Gate 3, implementation and corrective
  Gate 5 records;
- the prior command v2 findings, incremental-parser approval, parent-symlink
  approval, schema-v2 approval and setup-v4 approval;
- a production diff from the last independently reviewed command base through
  the final descriptor implementation, plus a focused descriptor diff from its
  approved Gate 3 base;
- full command/test manifest hashes and the exact regression transcript above.

Because the spec and OpenSpec files were modified during this inventory, their
hashes below are observation anchors only. They must be regenerated after the
descriptor amendment is frozen. Existing parser/schema/setup approvals apply to
their reviewed production behaviors, not automatically to new normative bytes.

## Exact observation hashes

Manifest hashes are SHA-256 of the literal sorted `sha256sum` output generated
at inventory time; individual lines can be regenerated with the commands below.

```text
8f212b285403e39c877a86bb751b195fff1709b9da66e704e9c0a421e4449f4e  sorted app/AssignmentOrderOriginal PHP hash manifest
9f75d2c758f0e6693b152e437b93c2f8323808bcc5c8cf6af4547fdf1af09f5d  sorted assignment_order_original_*_test.php hash manifest
f879c3a7e9ddb199dc62234d3d35fa2f8470f360b54825dab5f0d33113076062  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md (moving observation)
c1521e7708ca7134b101e78e608bd868834785469194ed295778b5a110b960c5  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md (moving observation)
f33c4626d29d74d909edb71abd477696c94be822ee6c0a559713b2ea0d4cadee  openspec/changes/replace-pilot-registration-with-original-upload/design.md (moving observation)
f000fcc8cfc282a49ebcbc8e9409cfff23307b4a8863cc030016049466c32d23  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md (moving observation)
b9627c8b5fcbcea9aeadb33429aae40bbead988164bed138caa73c30e9ec0959  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md (moving observation)
4c893c34377546ded04fc094bf5cbfd8dd5647655416ec25a8e6e28c65ef114d  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
7f90d23d59ba193ed3fa5f917625e0da0d58176e2ae76c3e6edca73b4efc926f  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
1a59ecc5ec45470ff76a6c043e29c67bdb89b641b298851540a050b46d6fe394  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
232c56f7009ee4416a35d3942dc2ee46053dc383fb0c11e769f418337f9a158d  app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php
ce1e072b08705f6347de23e2867ee53c8e774c528b3abf5f1c5b121d9a21bc3c  app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php
8a7e9a58199afbebaf2d2e82ca758bbeb4f9d44909ff5fbc953a6c6432b6e634  app/AssignmentOrderOriginal/MariaDbMaintenanceService.php
0ad53f4146b9b18773e189ae93c62a6ebb589420ff479538ad3f97d96554ecdc  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-command-v2.md
e460454efd741e303275a5e54e7c1e3609f1963c919eed9232864507d6aaf6da  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-pdf-incremental-grammar-v6.md
d9178b78bb08463e2e0ce1c89587ca9a413509125a40d8f5f44f93ece7088f8c  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-production-safe-log-v55.md
96c56da5c13bc15bc76decbf06a853ded3401a8e5dc34bf16cda44f16a6c0db4  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-production-safe-log-parent-symlink-v60.md
81338f0facb4c3dae84a8315d9ea998cccc9eadd06ab121927608bbab265c87a  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-schema-v2-v3.md
99340f1372a9d9037ba6497128914ed88bef7cdf5da5e56d7033fcba4ef2a7d1  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v4.md
```

Manifest commands:

```sh
find app/AssignmentOrderOriginal -maxdepth 1 -type f -name '*.php' -print0 \
  | sort -z | xargs -0 sha256sum
find tests/InstallationProcess -maxdepth 1 -type f \
  -name 'assignment_order_original_*_test.php' -print0 \
  | sort -z | xargs -0 sha256sum
```
