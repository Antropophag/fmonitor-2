# PR #291 CI repair — independent final review

Reviewer: `/root/reconcile_gate3` (independent; no authorship of the repair implementation or test delta)

Verdict: **APPROVED for the bounded CI repair implementation and exact-source focused verification**.

This review covers the entire current dirty delta against `ed1b6cdb3805920b95ee35787d28fb40862a7997`, including every test/fixture/policy correction and the three separately authored source changes. It does not claim the required second full GitHub CI matrix is GREEN.

## Exact binding

- Candidate source: `96d7953c657e7c89c248203c270eda8b7ac8111bf1365bc204f27edf3adcb27a`
- Executable source: `db1568225c51c8eea2badcf46e985514890d0ccf4041a77dcaa43998b54e7f58`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20261110T093434Z-a4dd8d214e/package.json`
- Verification plan SHA-256: `1d2577bdaa15617188b62909246226af1d2f214300d6aff2d4b053b5ce39ff1e`
- Architecture checker SHA-256: `bf5eaaba95f64ffeccd0e038e59ed906a84cf6b4253b5bda1ba307f30809c14b`
- Dashboard store SHA-256: `e04b7e384d8306d0a0ae93ec25d2ade8ab05b56431b87eb2fc8ef6c290f75e98`
- Dashboard public validator SHA-256: `f37c9455f70b5f822f6f2d84c0b8210c547c45b4ecc9132f0eff8a2df26279bd`
- Contract SHA-256: `5da6365b3fe29995e87096a43c547be99109c2cb37ddfd00bc830d3f7a081533`
- CI-repair verification input SHA-256: `a8b0271c3e1463c8f0b4e3263ddcf9377eecec59cccddfe09d373238148ff463`
- Changed-test aggregate SHA-256: `ea85187a3c02b9a2493ebc5234afb65a28db0bb97aaa2c35a7d92fbb4802281b`
- Reconstructible snapshot: `/Users/antropophag/.local/share/fmonitor-2/reconcile-production-20261110/ci-repair-final-source`
- Snapshot patch SHA-256: `2af46442a288d7394fb2574354a70d421094c4c30c17d2aab5b14a05ae820f53`
- Snapshot base: `ed1b6cdb3805920b95ee35787d28fb40862a7997`

## Implementation review

The architecture checker change is narrowly confined to false-positive normalization. Native HTML `<select>` tags are normalized only outside quoted PHP strings. Unquoted JavaScript identifiers/property access are normalized while string and template literals continue through SQL detection. SQL strings, concatenated fragments, multiline SQL, DML, DDL, runtime-write ownership, and rapid-pilot boundary checks remain enforced; no baseline debt or path exemption was added.

The dashboard store now computes `active` from the same canonical `installation` and `document_closeout` stage predicates used by the queue. `needs_assignment_change` remains a separate stage and remains included in total/stage distribution. The public `YiiOperationalDashboard` validator was corrected to the same two-stage sum, so it validates rather than rejects the canonical result. Authorization, date validation, response shape, stage/week/risk validation, read bounds, and read-only behavior are unchanged.

## Test and oracle review

No assertion weakening remains. The new dashboard regression binds the registered change object by exact object ID, case ID, and displayed stage, proves total `4`/active `3`, asserts queue/dashboard parity, and verifies no writes. The existing full dashboard test now expects active `2` after its own change-case insertion and in the 30k stress fixture while retaining total, overdue, upcoming, constant-query, memory, bounded-row, role, HTTP/HEAD, determinism, and failure-safety assertions.

The CI oracle corrections retain exact schema 38 and 105/59 current inventories without rewriting historical profiles; valid four-group activation inputs without weakening credential/token/session/role checks; exact object-recipient reserved-share cents without redistribution; separate dismissal reserves and warnings on both UI axes; public preview no-write and saved-confirmation identity; payment and erroneous-mark reversal obligations; authoritative workforce dismissal timing; adjusted-date precedence; and complete-crew pagination at supported 10/25 sizes with retained search. The previously reported dashboard identity gap is closed.

## Evidence

The bound package contains four GREEN mapped records at the candidate source:

- `tests/Verification/architecture_guard_001_test.py`
- `tests/Verification/architecture_native_select_tokens_291_test.py`
- `tests/Verification/architecture_js_select_tokens_286_test.py`
- `tests/Yii2/yii2_dashboard_active_change_parity_291_test.php`

Additional terminal exact-source GREEN records reviewed:

- `tests/Yii2/yii2_minimal_operational_dashboard_001_test.php` — `1794303267526452000-25263236c6ec4ec4ad235e1f59c310e7.json`
- `tests/Runtime/deadline_transfer_certificate_recovery_001_test.php` — `1794303267526463000-64d055bb3b5440e6bbcab40bfae93108.json`

The canonical OTIZ browser v3 flow was reported GREEN with exact pair, reserve, payment, reversal, dismissal, and pagination assertions. Architecture guard execution remained serial because its fixture temporarily mutates source. `git diff --check` is clean. The prohibited local full suite was not run.

No unresolved final-review finding remains. The repair may proceed to a new exact-source full GitHub CI run. CI/admission remains UNKNOWN until that matrix completes and any failed-job plus `REGRESSION_FAILURE` inventory is resolved.
