# Gate 5 code review — YII2-INSTALLER-DIRECTORY-ASSIGNMENTS-001

- Reviewer: independent `issue38_gate5` agent; authored none of the reviewed specification, tests, fixtures, production code, or evidence.
- Review date: 2026-09-14.
- Base: `cf0299d8ea65332b654662c696966b5bb953a1d6`.
- Exact candidate source: `642c46e6a701c96830f7ada0d38825be1e2f23332a84fe9b036ee800fda8665f`.
- Reconstructible snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T205810Z-079d9db2ad/snapshot/source.patch`, SHA-256 `0825fef422b0d18599ee0c01b43d4965f93359651accd6b67e2b8a3d2e7f3162`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T205810Z-079d9db2ad/verification-plan.json`, SHA-256 `5576866b92cde9d6b1442a5905f86db3312505316b71bbf44223205f0129c718` (`CRITICAL`, Gate 3 and final review required).
- Prior Gate 3: `APPROVED` for the final corrected test source `cea8e7b01e9d66466fd417e4e6df84bd1d9e2ed349eb4b12a928be5d1e0cc392` in `reviews/tests/YII2-INSTALLER-DIRECTORY-ASSIGNMENTS-001.md`.

## Verification evidence reviewed

The package contains source-matched GREEN records for:

- `php tests/Yii2/yii2_installer_directory_native_assignments_001_test.php`;
- `python3 tests/Verification/change_verification_001_test.py`;
- `python3 tests/Verification/architecture_guard_001_test.py`.

All three records name candidate source `642c46e6a701c96830f7ada0d38825be1e2f23332a84fe9b036ee800fda8665f`. Full local `make test`/`make verify` was not run, as prohibited. Exact-source CI, deployment, and live enforcement remain `UNKNOWN`; they are not treated as GREEN or approval.

## Findings

1. **HIGH — a valid legacy fallback assignment is classified as free whenever any native assignment exists anywhere.** In `app/Workforce/MariaDbYiiInstallerDirectory.php:16-18`, `$activeIds` correctly accumulates both current native installers and no-application legacy fallback installers, but `$activeTabIds` is chosen as `$nativeIds !== [] ? $nativeIds : $activeIds`. Consequently, in the mixed state already created by the acceptance test (native case 4512 plus no-application fallback case 4600), installer 7001 receives and displays the fallback assignment while the availability condition and summary omit that installer. `/pilot/installers?availability=free` can therefore show a row containing an active fallback assignment, `/pilot/installers?availability=assigned` excludes it, and summary `assigned` undercounts unique installers. This violates the design requirement that summary, filters, rows, and links consume the same current projection, and breaks A4's preservation of the existing registered-order projection for no-application cases. The test asserts only that the fallback link is visible; it does not re-check summary or filters after inserting the fallback, so the source-matched GREEN does not catch the split semantics. **Correction:** derive the unique active installer set from the union of current native assignments and eligible no-application fallback assignments (the accumulated `$activeIds`), and add post-fallback assertions for unique summary plus assigned/free filter consistency. Because this changes the approved test, recompute the plan and return through Gate 2/Gate 3 before implementation review.

2. **HIGH — malformed mandatory application snapshots can be accepted and coerced into assignments.** `app/Workforce/MariaDbYiiInstallerDirectory.php:26` validates only the top-level keys, non-empty installer array, `(int) tabId > 0`, and duplicate coerced IDs. It does not validate the mandatory `selectedEngineer` structure, installer `fullName`/`position`, or a strictly positive integer representation; for example `selectedEngineer: null` and `tabId: "7002junk"` pass and the latter is silently projected as installer 7002. The repository's established application integrity validator (`app/InstallationProcess/YiiObjectCardApplicationIntegrity.php:9-33`) treats these fields and composition identity as mandatory and fails closed. A4 requires an incorrect mandatory current snapshot to return the sanitized `503`, not a partially trusted projection. **Correction:** validate the current application with the canonical integrity rules (selecting the necessary identity/order fields) or enforce an equivalently strict complete snapshot/identity check locally; add deterministic malformed nested-field/type witnesses proving GET and HEAD fail closed without fallback or writes. Recompute the plan and obtain the required renewed Gate 3 approval for those test corrections.

The latest-application join and per-case `NOT EXISTS` fallback boundary otherwise select the intended owners. The implementation uses Yii query construction for table data, validates configured prefixes, performs a fixed number of reads rather than per-row SQL, makes no DML/runtime-DDL calls, preserves application history, and does not introduce rapid-pilot or excluded-scope dependencies. Those properties do not resolve the two observable correctness/fail-closed defects above.

## Verdict

`CHANGES_REQUESTED`

Gate 5 does not pass for candidate source `642c46e6a701c96830f7ada0d38825be1e2f23332a84fe9b036ee800fda8665f`. Correct the complete mixed native/fallback summary-filter-row semantics and mandatory snapshot validation, renew the planner-selected test review after test changes, then prepare a new exact-source Gate 5 package with focused GREEN evidence.

---

## Gate 5 correction review — 2026-09-15

- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T211744Z-ad74e4ef5d/package.json`.
- Corrected exact candidate source: `478b4f419f5593fd6805cbdadb4507e0741adbc964bc6abb0cb6d410118e7d2b` over base `cf0299d8ea65332b654662c696966b5bb953a1d6`.
- Reconstructible snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T211744Z-ad74e4ef5d/snapshot/source.patch`, SHA-256 `f5b25f9181aab2334f1ad32e91f746168bc92c1e1caa2554d3efcbfd2776fd23`.
- Correction delta from the first Gate 5 source: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T211744Z-ad74e4ef5d/delta.patch`, SHA-256 `e621555b24b2841c5cd3d956d4f3c85c066ecad684ef5ea19a4b092f9831a7e5`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T211744Z-ad74e4ef5d/verification-plan.json`, SHA-256 `a157221a34ae1de26d50c593d8665b54e65fc66254e07a29e297dd33820996d4` (`CRITICAL`, Gate 3 and final review required).
- Independence is unchanged; this reviewer authored none of the correction implementation, tests, fixture/oracle corrections, Gate 3 dispositions, or evidence.

### Prior-finding disposition

1. **Mixed native/fallback classification — resolved.** `MariaDbYiiInstallerDirectory` now derives `$activeTabIds` from the single `$activeIds` union populated by both latest native applications and eligible registered-order rows for cases with no application. Summary, assigned/free filters, row assignments, and links therefore consume the same unique current installer set. The renewed acceptance test constructs the formerly failing mixed state and independently requires summary `2`, both installers in `assigned`, and neither in `free` while preserving the fallback link.

2. **Mandatory application integrity — resolved.** The native projection now selects the application fields required by `YiiObjectCardApplicationIntegrity::validateComposition()` and invokes that canonical validator after the case/object consistency check. Invalid numeric types, mandatory installer and engineer fields, engineer identity, installer uniqueness/order, composition identity, and composition hash all fail through the existing sanitized directory error boundary. The correction matrix covers GET and HEAD, recomputes hashes for semantic-invalid cases so the assertions are independently sensitive, retains separate persisted identity/hash mismatch cases, excludes stale/internal output, and proves whole-fact preservation.

The renewed Gate 3 sequence records the intermediate sensitivity returns and final `APPROVED` verdict for exact test source `b49c150c1f93f659fb104283c47a3215e00d170f77ac40a2fadb3d47ce54f30c`. Its subsequent narrowly scoped test-delta audit is `APPROVED` for source `0fa2e7d1d1354481c0f6b443cc7978f8806f6d6a4dfe4dfd241d0c49a2b7e501`; it verifies only canonical hashes for synthetic valid applications and the corrected unique-summary literal, without weakening behavior or changing the seam.

### Complete correction findings

None.

The complete candidate retains latest-application selection per case, excludes legacy rows as soon as that case has any application, preserves the no-application fallback, deduplicates unique installers for summary/filter use, provides deterministic assignment ordering, and performs a fixed bounded query set without per-row SQL. Dynamic identifiers remain restricted to validated configured prefixes; data predicates use Yii query construction and validated positive installer IDs before constructing the numeric `IN` expression. Reads contain no DML or runtime DDL, preserve append-only history, load no rapid-pilot/PilotHttp layer, and do not enter the excluded redesign, engineer-management, checklist/offline, Bitrix, or OTIZ scopes.

The package contains source-matched GREEN evidence for the native end-to-end acceptance test, change-verification test, and architecture guard, all naming source `478b4f419f5593fd6805cbdadb4507e0741adbc964bc6abb0cb6d410118e7d2b`. The prohibited full local suite was not run. Exact-source CI, publication, deployment, and live enforcement remain `UNKNOWN` and are not implied by this approval.

### Correction verdict

`APPROVED`

Gate 5 passes for exact candidate source `478b4f419f5593fd6805cbdadb4507e0741adbc964bc6abb0cb6d410118e7d2b`. Publication remains contingent on the repository delivery workflow and one authoritative exact-source CI run.
