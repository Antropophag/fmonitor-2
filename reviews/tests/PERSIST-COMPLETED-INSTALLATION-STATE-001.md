# Independent Gate 3 test review — PERSIST-COMPLETED-INSTALLATION-STATE-001

- Date: `2026-09-25`
- Reviewer: independently tasked agent `/root/gate3_approve_final`; did not author the reviewed specification or tests.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T132602Z-45410233f5/package.json`
- Base: `7aa2951debde7c2c7f4b07e69fbed8d6713b038b`
- Candidate source: `2a0f6485da1785f0f38df67336a3073f80bb4fd6a0517d916cbaf40d9bd8b874`
- Executable source: `761e5d364b6dd0963845bfd39c1462ab0b119e6203e97916e2f54418291b4916`
- Verification plan SHA-256: `25c00242048040de2c66b6e16588a5cecf6bbec3e166026159b4f5c0034710ec`
- Verdict: **APPROVED**

## Scope and result

The narrowed slice ends at the first real `85% → 100%` snapshot. Historical
reconciliation, deployment CLI/recovery, broad read-projection unification,
repeat-settlement expansion, and `INSTALLER_ATTRIBUTION_ABSENT` remain outside
this Gate 3 scope.

No blocking findings remain. The specification and tests trace A1–A5 through
the public Yii/application and OTIZ calculate → snapshot seams, retain
authorization and no-fact rejection checks, separate the state/event assertions,
exercise rollback and concurrency, independently cover all three prohibited
completed-checklist commands, and cover both completed-document correction
commands.

## Prior finding dispositions

- **FIXED — canonical completed migration canaries.**
  `tests/Support/ExcelInputsAdversarial.php` now creates an eligible
  `completed/native_candidate`, excluded `completed/legacy_active` and
  `completed/legacy_historical` cases, and retains the missing-legacy-object
  canary. The expected exact object IDs fail both when the eligible completed
  case is omitted and when either excluded category is admitted. The old
  noncanonical `active_candidate` value is gone.
- **CLOSED — real final-15% publication witness.**
  `tests/Otiz/excel_inputs_001_test.php` publishes the real 85% snapshot, records
  PTO and declaration, and requires the completed snapshot to expose
  `previous_progress_bp=8500` and `current_progress_bp=10000`.
- **CLOSED — state/event reachability, checklist commands, corrections,
  rollback and concurrent declaration.** The previously reviewed split tests
  remain byte-identical in this package and retain their intended RED reasons.

The contract's quarantine wording does not identify another classification
category at this reader seam: eligibility is the exact existing
`native_candidate OR no provenance` predicate. Thus canonical
`legacy_active`/`legacy_historical` exclusion plus the missing-provenance rule
is the bounded migration-classification matrix for this change; unrelated
quarantine decision ledgers are not an additional premium-input category.

## Focused RED evidence

The final changed OTIZ test was rerun and failed at the intended new selection
oracle:

```text
excel_inputs_001_test.php
Expected IDs: [4510,4511,4512]
Actual IDs:   [4510,4512]
Reason: current production reader still excludes completed/native_candidate
```

The full narrow matrix was also started from this exact package. Its observed
results retained the independently established intended RED causes: absent
completion event, state still `working`, missing transactional fault point,
completed document corrections returning 503, and completed checklist commands
not yet reaching their domain 409. `yii2_documentary_http_001_test.php` remains
the adjacent GREEN control from the preceding review. No full local suite was
run.

## Exact reviewed hashes

```text
0fdd7890a92c992330df18253df116c3c962c2fbe93a8028fb77ed6ce93c3645  specs/PERSIST-COMPLETED-INSTALLATION-STATE-001.md
5efb78330053555099a4739cbbb59894823a78ed341853fa075bce5315dca452  tests/Yii2/yii2_completed_installation_state_001_test.php
cc28bfa47d3ec60f2efed9fc9effc5ccd36631b1d044814b20211a28d164fbfd  tests/Yii2/yii2_completed_state_transition_001_test.php
f46b0ad88360f1b95e85f163fc50ed0965e766f94c193c4ffef47d4a2b83c822  tests/Yii2/yii2_completed_transition_rollback_001_test.php
f5b08f1f338f1a835bce8391fbc3e68ac356828de53714053842a57da02bd0ff  tests/Yii2/yii2_completed_document_corrections_001_test.php
9ba3be88f6a656f9f683902fe1b39b62ceaea0ad6b235f37ab35d1506cbd895a  tests/Yii2/yii2_completed_declaration_correction_001_test.php
bb21e591e7f91a946c6a882c05b3bf6bbfc90e00d0082a10fa4a29ca39f415ab  tests/Yii2/yii2_completed_checklist_admission_001_test.php
29652201624619d420535ac0067d1882827a6efcdeb57e1a99ac775d6629f227  tests/Yii2/yii2_completed_checklist_attribution_001_test.php
20e4126a884999ae5f217edae179df2fdd00960666a758d418b32a9b84367d0d  tests/Yii2/yii2_completed_checklist_retraction_001_test.php
542b863267b9a543fde641da93e71e83f441dfb4a1d701211ccb6f6cece38d65  tests/Yii2/yii2_documentary_concurrency_001_test.php
095556aef712d18e47142c2587786dc888b5df2ff94244ef835402c1f07ca9a9  tests/Otiz/excel_inputs_001_test.php
c72f1679d653a2b9d5fe8ef7253b6594d51bb256cb5b3a613ecc225b0a7e9ece  tests/Support/ExcelInputsAdversarial.php
```

Gate 3 is **APPROVED** for candidate source
`2a0f6485da1785f0f38df67336a3073f80bb4fd6a0517d916cbaf40d9bd8b874`.
Gate 4 may begin with a separately tasked executor. CI, merge, and deployment
remain `UNKNOWN`.
