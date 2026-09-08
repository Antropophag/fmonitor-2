# Test review: PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 — combined RED v2

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, evidence, OpenSpec artifacts, or implementation
- Reviewed commit: `09e5e581d2d843c319c4b91b97f97c8ec8b03943`
- Reviewed evidence: `docs/operations/pilot-work-navigation-removal-red-evidence-v2.md`
- Verdict: **CHANGES_REQUESTED**

## Blocking findings

1. **Canonical HTTP coverage still includes only two of the ten governed route families.** `pilot_http_auth_001_test.php` adds the removal assertion after successful authenticated `/pilot/`; `pilot_object_list_001_test.php` has the assertion after successful `/pilot/objects`. The focused test lists the remaining routes but calls `PilotView::document()` directly. It still does not prove canonical admission/success/current-screen selection for object card, prepare, object checklist, construction-control queue/checklist, installers, admin users, or admin roles. Section 3 explicitly requires at least one successful canonical HTTP representation from every enumerated family and says reconstructed renderer coverage is not a substitute.

2. **GET/HEAD removal coverage is not established per family.** Root and object-list HTTP tests pair GET with HEAD, but the remaining eight renderer-only entries have no HTTP status/header/content-length/body parity evidence. Existing generic method/error controls prove the shared entrypoint's predecessor behavior, not that every governed representation remains admitted after this change.

3. **Exact sibling/accessibility/icon preservation remains partial.** The focused renderer oracle checks full sibling label/destination/current/disabled tuples only for two synthetic inputs. It does not compare icon bytes or the complete accessible structure required by section 4, and it does not apply exact sibling expectations to every route/actor representation. Combining two canonical absence assertions does not fill this preservation gap.

Continue the route-fixture inventory already recorded in RED v1. Reuse healthy canonical fixtures for each remaining family and add bounded GET DOM plus paired HEAD assertions without weakening their RBAC/schema checks. For families still blocked by unrelated predecessor failures, resolve those predecessors or amend the approved scope through Gate 1; renderer enumeration cannot waive the contract.

## Prior gaps now closed or materially improved

- **Root route preservation:** authenticated `/pilot/` remains 200 with exact shell/content assertions, and its main `<h1>Моя работа</h1>` remains content rather than navigation. `/pilot` GET/HEAD keeps exact 308 redirect behavior.
- **Root GET/HEAD:** the real HTTP test checks full header/content-length parity and empty HEAD body.
- **Inherited HTTP controls:** 401, 403, 404, 405 and 503 matrices, security/redaction headers, body-read priority and resource cleanup remain in the companion HTTP test.
- **Zero-read/zero-write evidence:** existing environment/dependency spies, database snapshot, filesystem guard, CSS metadata snapshot and cleanup remain unchanged around the new root assertion.
- **Object-list seam/RBAC:** configured `/pilot/objects` GET/HEAD reaches the navigation assertion only after identity/admission; its authorization/data matrix remains intact.
- **Focused hidden/accessibility sensitivity:** exact visible/hidden text, direct ARIA/title/image labels, resolved `aria-labelledby`, root destinations/current substitutes, repeat bytes and content preservation remain useful shared-composition checks.

## Intended RED

The focused renderer test remains RED at exact label absence (`Expected: 0`, `Actual: 2`). The real authenticated root test reaches a successful 200 response and fails at the new navigation count (`Expected: 0`, `Actual: 1`). The object-list test independently reaches configured HTTP/RBAC and fails at its removal predecessor. These are valid REDs for the two canonical families covered, not setup failures.

## Reviewed hashes

```text
17d383f8dc12d2f08789f9f2e196cffd50b5dad1166cdd5ca5722b41dc318626  specs/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001.md
7ce465c3a3e15957e8c4b89311dd1ce783c5db93355f97c2d194db5a33bcd870  tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php
9de51a02c3a3900112c853a5f4dfb55c6195f93a7f4dc127d0e7b86268ba716b  tests/InstallationProcess/pilot_http_auth_001_test.php
861462feb34df7eb107167c314f5605ab1c5e554bb88c1706c0240c05e624f9a  tests/InstallationProcess/pilot_object_list_001_test.php
```

Gate 3 is not approved. The combined tests authorize no production removal until all approved route families and preservation assertions are represented.
