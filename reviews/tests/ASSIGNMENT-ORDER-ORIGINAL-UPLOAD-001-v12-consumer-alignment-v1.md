# Gate 3 review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v12 consumer alignment

- Timestamp: `2026-09-04T03:42:14+03:00`
- Reviewer: separately tasked agent `/root/original_upload_v12_consumers_red/original_upload_v12_consumer_gate3`
- Reviewed commit: `92bb33f0526027733fe04c3b82a6498ea190115f`
- Comparison base: `a8f325004c21485abcde06add7023674c8a6db60`
- Exact pre-v12 RED control: `84971d3adef997c70bc33dfcbe28b54be7ead113`
- Reviewed diff SHA-256: `0c61ec7a22fcb2efb007c688ca4acc7a50b8a2d135ae39f4a46754f19bcf6414`
- Evidence: `docs/operations/assignment-order-original-upload-v12-consumer-alignment-red-green-evidence-2026-09-04.md`
- Verdict: `APPROVED`

## Independence and scope

The reviewer did not author or edit the reviewed tests, fixtures, evidence,
OpenSpec artifacts or production code. This append-only record is the reviewer's
only change. The reviewed diff contains only test/verifier consumers and the new
evidence record; no `app/`, `bin/`, migration or other production source changed.

## Review findings

The successful canonical-runner consumers require exact terminal
`schemaVersion=12` and ordered `appliedVersions=[1,2,3,4,5,6,7,8,9,10,11,12]`.
`workforce_canonical_runner_001_test.php` additionally pins the full ordered
38-table catalogue and the exact seven capability literals:
`assignment_order.prepare`, `assignment_order.confirm_registration`,
`installation.open`, `construction_control_engineer`,
`assignment_order.original.upload`, `assignment_order.original.correct`, and
`assignment_order.original.storage.reconcile`.

The v12-only successor cleanup is scoped to successful multi-prefix fixtures and
does not weaken predecessor assertions. Direct historical v11 setup/conflict
fixtures in `assignment_order_original_upload_001_schema_test.php` remain intact;
the classification barrier fixture still returns literal v11, and conflict
assertions for earlier schema versions remain exact. No non-frontier assertion
was converted into a terminal-v12 success assertion.

No blocking or non-blocking finding was identified.

## Independent commands and results

```text
git diff a8f325004c21485abcde06add7023674c8a6db60..92bb33f0526027733fe04c3b82a6498ea190115f | sha256sum
0c61ec7a22fcb2efb007c688ca4acc7a50b8a2d135ae39f4a46754f19bcf6414  -

git diff --name-status a8f325004c21485abcde06add7023674c8a6db60..92bb33f0526027733fe04c3b82a6498ea190115f
PASS: only tests, rapid-pilot verifier, and the evidence record changed

rg -n "schemaVersion.?[=:> ]+11|schemaVersion\\\":11|v1-v11|terminal v11|canonical v1-v11|31-table catalogue|> 11" tests rapid-pilot --glob '*.php'
PASS: only the classification v11 barrier, three explicit pre-v12 v11 schema fixtures, and unrelated numeric data remain
```

In a disposable home-directory worktree at exact `84971d3`, applying only the
reviewed checklist/identity consumer changes reproduced the claimed intended RED:

```text
php tests/InstallationProcess/checklist_template_schema_001_test.php
exit 255: expected terminal v12/[1..12], observed terminal v11/[1..11]

php tests/InstallationProcess/identity_access_schema_001_test.php
exit 255: expected terminal v12/[1..12], observed terminal v11/[1..11]
```

The disposable worktree was removed and `git worktree prune` completed.

With `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local`, the following
reviewed focused commands passed at exact `92bb33f0526027733fe04c3b82a6498ea190115f`:

```text
php tests/InstallationProcess/checklist_template_schema_001_test.php
php tests/InstallationProcess/classification_provenance_schema_001_test.php
php tests/InstallationProcess/identity_access_schema_001_test.php
php tests/InstallationProcess/inspection_evidence_schema_001_test.php
php tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
php tests/InstallationProcess/inspection_planning_schema_001_test.php
php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
php tests/InstallationProcess/installation_completion_schema_001_test.php
php tests/InstallationProcess/pilot_case_import_001_test.php
php tests/InstallationProcess/pilot_http_auth_001_test.php
php tests/InstallationProcess/workforce_canonical_runner_001_test.php
php tests/Verification/harness_otiz_canonical_compat_001_test.php
php rapid-pilot/verify-calendar-projections.php
```

The evidence honestly records repository-wide `make verify` as RED with four
failing stages and does not use focused GREEN to claim integration GREEN.

## Gate decision

Fresh independent Gate 3 for the v12 canonical-runner consumer alignment at
exact commit `92bb33f0526027733fe04c3b82a6498ea190115f` is `APPROVED`. Any subsequent
change to these test/verifier oracles requires a fresh independent review.
