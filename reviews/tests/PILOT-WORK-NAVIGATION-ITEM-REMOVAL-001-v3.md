# Test review: PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 v2 deep-seam RED v4

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/navigation_v4_review`
- Independence: this reviewer did not author the specification, OpenSpec artifacts, tests, RED evidence, fixtures, or implementation
- Reviewed commit: `a478e68`
- Gate 1 strategy review: `docs/operations/pilot-work-navigation-gate1-rereview-v2.md` — `APPROVED`
- Reviewed evidence: `docs/operations/pilot-work-navigation-removal-red-evidence-v4.md`
- Verdict: **CHANGES_REQUESTED**

## Blocking finding

The exhaustive renderer half of the approved v2 topology is now sufficient, but the required route-specific wiring evidence is still unavailable. The controlling Gate 1 review says Gate 3 must verify that every cited route suite remains GREEN and reaches its production view; a predecessor-blocked suite cannot count. On a reset/migrated test database at `a478e68`, object card stops at `Expected: 200 / Actual: 503`, prepare stops after its success request at missing `Состав распоряжения`, and UI shell stops at its inherited `shell identity` assertion. Full `make verify` independently reports the same object-card, prepare, UI-shell and E2E predecessor failures and ends without `VERIFY_OK`.

These failures are correctly classified as separately owned RBAC/fixture/shell predecessors rather than false navigation REDs. That classification preserves test honesty, but it does not satisfy the approved deep-seam proof. Until the predecessor suites reach their successful configured views, they cannot prove production wiring for object card, prepare, installers, administration and the other route-specific callers which rely on them.

## Coverage that passes review

- The focused test renders all ten approved current-screen values through production `PilotView::document()` with the minimal or broad actor required by the spec.
- Every state has an independently fixed literal SHA-256 sequence for all remaining direct navigation children. The six distinct predecessor vectors cover root/no-current, objects-current, construction-control-current, installers-current, users-current and roles-current; shared route states reuse only the applicable identical current-state vector.
- Those complete child bytes are sensitive to order, labels, destinations, conditional visibility, `aria-current`, disabled/accessibility attributes and inline icon bytes. An inserted renamed or icon-only first-slot substitute changes cardinality or bytes.
- The common DOM oracle additionally rejects exact visible/hidden `Моя работа`, direct and referenced accessible names, root destinations and root current/disabled substitutes. Repeat representation and content-byte preservation are deterministic.
- The intended focused RED reproduces at the first removal assertion (`/pilot/`, `Expected: 0`, `Actual: 2`), after parsing and renderer setup.
- The real root and object-list sentinels both reach successful configured GET representations and fail only on the missing navigation removal (`Actual: 1` and `Actual: 2`, respectively). The canonical object-list RBAC fixture change at `a478e68` therefore closes that sentinel's former authorization predecessor.
- Inspection item endpoint, inspection planning runtime, route-CSP inventory and identity-access runtime verifiers pass, but these passing subsets do not replace the failed named production-view suites.

## Reproduced verification

After `make test-db-reset && make migrate` with the repository test credentials:

```text
php tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php
  intended RED: /pilot/ no visible or hidden work label; Expected 0, Actual 2

php tests/InstallationProcess/pilot_http_auth_001_test.php
  intended sentinel RED: work navigation removed; Expected 0, Actual 1

php tests/InstallationProcess/pilot_object_list_001_test.php
  intended sentinel RED: no work item or root destination; Expected 0, Actual 2

php tests/InstallationProcess/pilot_object_card_001_test.php
  predecessor failure: Example A status; Expected 200, Actual 503

php tests/InstallationProcess/pilot_prepare_form_001_test.php
  predecessor failure: heading; Expected 1, Actual 0

php tests/InstallationProcess/pilot_ui_shell_001_test.php
  predecessor failure: shell identity; Expected 1, Actual 0

make verify
  architecture-check PASS
  lint PASS
  unit-test FAIL
  db-test FAIL (8 verifiers)
  characterization-test PASS
  e2e-test FAIL
  diff-check PASS
  FULL_VERIFICATION_FAILURE count=3 stages=unit-test,db-test,e2e-test
```

## Reviewed hashes

```text
ffb72c0602a26e24aa86f7df339bcc209f6b0ce894f8a41988527c62e9db8c65  specs/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001.md
3e0a910f293e4601f46b3e8e5c6a2dc3586e58f8154e79a224b13d7505cceff5  tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php
9de51a02c3a3900112c853a5f4dfb55c6195f93a7f4dc127d0e7b86268ba716b  tests/InstallationProcess/pilot_http_auth_001_test.php
42e8c066638f41de4ca0486f489273d0e58ed45fa0467fcd56cfd7809d238c4c  tests/InstallationProcess/pilot_object_list_001_test.php
251fd7ff59a71c81dafb93d186eb9180065732261d681735d778ada7f27b4488  tests/InstallationProcess/pilot_object_card_001_test.php
edda5307311eb395e104e34f407a02f01f2bbf255d17476a1901b6e99ada2886  tests/InstallationProcess/pilot_prepare_form_001_test.php
f92b872ee5e2d4e3a356c650eb2ee7ade731817e33813bcfadcb12a6219abf82  tests/InstallationProcess/pilot_ui_shell_001_test.php
a9f9e5df67812bae01afe0ea7846db3d2f40acd76e76c2e47b351e31ddc7ea4c  docs/operations/pilot-work-navigation-removal-red-evidence-v4.md
```

Gate 3 remains closed. Resolve the named predecessor contracts without weakening their assertions, rerun them to successful production views, and request a fresh independent review. No navigation-removal GREEN is authorized by v4.
