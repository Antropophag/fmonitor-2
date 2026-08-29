# Test review: PILOT-SERVICEDESK-REDESIGN-001 v0.1

- Gate: 3 — fresh independent review
- Reviewer: separately tasked agent `/root/sd_redesign_test_review`
- Test author: separately tasked Gate 2 agent `/root/sd_redesign_tests`; reviewer authored neither reviewed input
- Reviewed commit: `fb75e5318ff4ef4407e1c54e693ae2cd642b96e4`
- Specification commit: `461a930b808117bf4a5e78767576fdc34df6d5e9`
- Specification: `specs/PILOT-SERVICEDESK-REDESIGN-001.md`, version `0.1`, `APPROVED`
- Test: `tests/InstallationProcess/pilot_e2e_flow_001_test.php`
- Public seam: configured-production raw HTTP DOM/CSS backed by the existing isolated real-MariaDB journey; responsive browser geometry is deferred to Gate 5
- Date: `2026-08-29`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **The queue assertions contradict the approved specification and would force prohibited product behavior.** Section 1 says that new search, filtering and tabs are not introduced, and section 4 permits only a noninteractive result summary. The reviewed test nevertheless requires a search form, an `Все` tab and a `Фильтры` button. These values are not traceable to the normative specification or an approved worked example.

2. **The tested queue column contract is the wrong oracle.** Section 4 specifies, in order, `Объект`, `Адрес`, `Плановые сроки`, `Состояние`, `Следующий шаг`, with registration nested under the object identity. The test instead requires `Объект`, `Рег. №`, `Адрес`, `Сроки`, `Следующий шаг`: it promotes registration into a separate column, changes the specified heading, and omits the required state column. A conforming implementation would fail this assertion.

3. **The focused additions do not cover most of the mandatory raw HTTP/DOM/CSS oracle in section 9.** There is no served-`pilot.css` request or assertion at all, so the required shell dimensions, gutters, bounded detail rail, breakpoints, wrapping/width rules, narrow topology, focus-visible treatment, reduced-motion rule, and bans on `.shlz-*` selector ownership, `!important`, remote imports/URLs, Ant selectors, gradients and the old hero rule are insensitive. Stylesheet order and local-only stylesheet URLs are also not asserted. These are raw CSS/DOM obligations expressly assigned to Gate 2, not the Gate 5 browser-geometry matrix.

4. **State and screen coverage is materially incomplete.** `pefRedesignCommon` and raw-token suppression are applied only to the queue and initial object card. The prepare screen and prepared, registered and working detail states are not checked for the shared shell, sole heading, stable IA, exact active navigation, inline script/style bans, duplicate IDs, raw enum/ISO suppression, document/person/history presentation, or state-exclusive next-action rail. In fact the inherited working-state assertion still requires the raw ISO timestamp `2026-08-28T12:45:00+03:00` in visible text, directly contradicting sections 5 and 9; because `pefRedesignNoRaw` is never called for that state, this regression is preserved rather than caught.

5. **The narrow semantic-list assertion does not prove the approved nonduplicating/accessibility contract.** It requires a mobile list in the same response as the desktop table but never proves that only one representation is exposed to accessibility APIs at a time, nor verifies an application-authored strategy in served CSS. It also checks only the canonical link, not the required purposeful fact order or complete wrapped facts. The expectation therefore admits an always-visible duplicate rendering that section 4 forbids.

6. **Required fixed examples and negative UI states are absent.** The test does not assert localized workforce provenance, exact two fieldsets/legends and selection-row component semantics, retained selected values after validation PRG, associated invalid controls/errors, exact document-row count and artifact links in DOM, localized chronological history, humanized dates, hidden actor/user IDs, empty queue/catalog presentation, or successful-read permission suppression of unauthorized controls. Existing transport/domain assertions are valuable inherited regressions but do not establish these redesign acceptance statements.

7. **The test is deterministic and isolated, and its observed RED is genuine but insufficient for approval.** It uses a unique random MariaDB schema/user and task-owned artifact root, fixed clocks/data, real configured HTTP, and cleanup in `finally`. The run reaches the product response and fails because the old shell lacks the specified skip link, not because setup is broken. That valid RED does not repair the contradictory and missing oracles above.

## RED verification

Command run at reviewed commit `fb75e5318ff4ef4407e1c54e693ae2cd642b96e4`:

```text
$ php tests/InstallationProcess/pilot_e2e_flow_001_test.php
PHP Fatal error: Uncaught TestFailure: redesign skip link
Expected: 1
Actual: 0
at tests/bootstrap.php:36
called from tests/InstallationProcess/pilot_e2e_flow_001_test.php:39
called from tests/InstallationProcess/pilot_e2e_flow_001_test.php:79
exit code: 255
```

The failure is at the configured production raw HTTP DOM seam. The test's outer cleanup completed: the worktree stayed clean before this review record, and no task-owned `t_pef_*` database/user or new artifact directory was reported by the test. (`mariadb` CLI is not installed in this environment, so database residue was not independently enumerated by that client.)

## SHA-256 reviewed-input manifest

```text
021a280e8a16f921ebc02d76de74583ca096cc491eb9f531c6a22d7dc3ac88ba  specs/PILOT-SERVICEDESK-REDESIGN-001.md
5a97a1483cdd4f7dec94db9740d07df2ad89582e5aa76ffdcf263c51548a74c4  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Git blob identities:

```text
80ea5eefc48634b19a507bcd9120b681fecb734c  specs/PILOT-SERVICEDESK-REDESIGN-001.md
c69c5b2fe26f8aff2c8f7e549ec32b173d7fb832  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Any byte change to either reviewed input invalidates this review. The review record is excluded from the self-referential manifest.

## Required changes

1. Remove the search/tab/filter assertions and replace the queue header oracle with the exact section-4 columns and nesting.
2. Add focused raw served-CSS assertions for every section-9 CSS obligation, including stylesheet order and the narrow single-accessible-representation strategy; keep computed geometry and screenshots deferred to Gate 5.
3. Exercise the shared shell and raw-label suppression across prepare plus all four detail states; replace visible raw ISO expectations with the specified localized text plus a machine-readable `<time datetime>` assertion.
4. Add traceable DOM assertions for state-exclusive actions, documents, team, history, fieldsets/choices/provenance, validation association and retention, permission-readable states, and required empty states.
5. Demonstrate a fresh intended RED after these corrections and return the changed test to a new independent Gate 3 review.

Gate 4 must not begin from this test commit.
