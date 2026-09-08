# Manual-pilot direct-opening card — independent review

- Reviewer: Codex agent `/root/auth_review`, independently tasked; did not author the reviewed production/test changes.
- Review date: `2026-09-07` (`Europe/Moscow`).
- Review base / current `HEAD`: `c9bd432afbc23497d45b1355f2408db271672191`.
- Scope: `app/PilotHttp/MariaDbAppliedObjectCardReader.php`, `app/PilotHttp/ObjectCardView.php`, the `card()` integration in `app/PilotHttp/PilotE2ECoordinator.php`, and `tests/InstallationProcess/object_card_actor_name_001_test.php`.
- Test verdict: `APPROVED`.
- Code verdict: `APPROVED`.

## Exact reviewed identities

```text
ac6762de267dde76273f12a5787b00fa86c6748ff8cdd54fee1018b9103586fb  app/PilotHttp/MariaDbAppliedObjectCardReader.php
f3af130ebbd79ee1bfe015da2fd37cde75027a6bc90ea1b9f86ae628860f151c  app/PilotHttp/ObjectCardView.php
d4a56da97f1c9a364ca1ccf8c5e1737d423bd8a7722b75f7215958285f74c9a9  app/PilotHttp/PilotE2ECoordinator.php
113d58d96be33d1df22271875b1ce51816653011827a8b97d73acddc36d76414  tests/InstallationProcess/object_card_actor_name_001_test.php
```

Exact diff SHA-256 against the review base, with the test represented as a new-file diff:

```text
8a1ea4e5db694fd9ca7457db19100d33c7762a0c9eb915d9436f431997562bcf  MariaDbAppliedObjectCardReader.php.diff
fb7579174ad72b338a64dd2b9fe3fa71308d755fb3ed6a3259acb9a640150bc2  ObjectCardView.php.diff
26fa8dc09476831f4d108e85517e83c23e3abdd3f756dfcdec1a1ba0e87377a9  PilotE2ECoordinator.php.diff
fbba0fbddac210c5f0ce726887678e8fa7b30e4b1449113220b5f2ba8bfcdd5b  object_card_actor_name_001_test.php.diff
b051144b1a4d069a087cdde1ac5decef2c30789f1b50b4e1689e0cfde4ec8b97  combined production diff
```

## Findings

No blocking findings.

The unopened-card query selects the greatest selection revision before joining an
original root. The root must match that exact selection's case, order, composition
identity and composition hash, and the revision must be the root's current leaf.
Consequently an older confirmed roster cannot reappear when the latest selection is
still awaiting its original. In that case the card returns to `Требуется распоряжение`
with no order, engineer or `confirmedOriginal`, rather than falling back to stale
composition facts.

For an exact current original, the projection uses status `confirmed`, the selected
engineer/installers, current revision metadata and the native download route. It
does not call the application command or mutate case/application/history state. An
unapplied original carries expected sequence 0. If an earlier original was applied
and then corrected before opening, the card keeps the existing application row and
projects the corrected current revision with the actual current application sequence.
Once opened, it preserves the applied original and roster instead of switching to a
later read-time selection.

The coordinator exposes the opening form only when server-side `installation.open`
is present, trusted local auth supplied a 64-byte CSRF value, and the card contains
an exact `confirmedOriginal`. It generates a fresh RFC 4122 version-4 request UUID
using CSPRNG bytes and bit masks. A broad read-only viewer sees the accepted original
but receives no opening form. GET performs only projection reads, session reads and
request-token generation; it creates no application, opening, assignment or event.

The renderer escapes CSRF, UUID, object/order/revision/sequence and date-bound values
through the existing HTML escaper; the only unescaped date is the locally generated
canonical Moscow `today`. The form posts only to the execution HTTP seam with
`action=open_confirmed`, exact confirmed-original identity/sequence and actual start
date. No `Применить состав` control remains. Legacy prepared/registered fallback
branches remain compatibility presentation only and do not gain a mutation.

The focused test proves no GET-side application/opening, truthful confirmed status,
exact selected roster/current original/download, authorized form fields, absent apply
CTA, viewer denial, preserved prior application after correction, and corrected
revision with sequence 1. The SQL structure independently confirms the latest-pending
selection refusal; adding an explicit second-selection-without-original regression
would strengthen sensitivity but is not a blocking defect in this owner-priority
manual-pilot correction.

The actor-name behavior included in the same exact files remains consistent with the
earlier independent approval: batch local-name resolution, inactive history, escaped
name/email/unknown-ID fallback, immutable actor IDs, and no viewer substitution.

## Independent verification

```text
PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/InstallationProcess/object_card_actor_name_001_test.php
PASS object card resolves immutable historical actor names
PASS accepted original returns to truthful unapplied card

PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/AssignmentOrderComposition/confirmed_original_opening_http_001_test.php
PASS HTTP original confirmation -> direct opening -> current object card

PHP lint for the three production files and focused test; focused git diff --check
PASS

PATH=/opt/homebrew/bin:$PATH php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

PATH=/opt/homebrew/bin:$PATH tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)

php rapid-pilot/verify-visual-contract.php
php rapid-pilot/verify-focus-contract.php
PASS

/Users/antropophag/.agents/skills/impeccable/scripts/impeccable detect --json app/PilotHttp/MariaDbAppliedObjectCardReader.php app/PilotHttp/ObjectCardView.php app/PilotHttp/PilotE2ECoordinator.php
[]
```

The review performed no implementation/test edit, GET-side business mutation,
deployment, stand/data mutation, remote action, or Bitrix action. It is bounded to
the exact hashes above and does not cover the reviewer-authored opening helper or
claim full `VERIFY_OK`/production readiness.
