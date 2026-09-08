# Manual-pilot object-card actor names — independent review

- Reviewer: Codex agent `/root/auth_review`, independently tasked; did not author the production or test changes.
- Review date: `2026-09-07` (`Europe/Moscow`).
- Review base / current `HEAD`: `c02f1a23121058fb0046e4b8fec859c5754ecb6d`.
- Production scope: `app/PilotHttp/MariaDbAppliedObjectCardReader.php` and `app/PilotHttp/ObjectCardView.php`.
- Test scope: `tests/InstallationProcess/object_card_actor_name_001_test.php`.
- Test verdict: `APPROVED`.
- Code verdict: `APPROVED`.

## Exact reviewed identities

```text
566cc9d2233a1610bc39c6184d5108015fab1f7aeb32ec97782379f7a2b0a621  app/PilotHttp/MariaDbAppliedObjectCardReader.php
5e3bb70a6019a59163c4d0d55fcc14e4924d069f249b0410be111f38fe0d9740  app/PilotHttp/ObjectCardView.php
7b411c76b414e4dcf54e6f28ab816aafede75bdf2fc6c9b2d88b29f25ae411be  tests/InstallationProcess/object_card_actor_name_001_test.php
```

Exact diff SHA-256:

```text
f553016312c258cc44b7e75c321a5afee5ab20240e4215e4881eebe46d5efec6  MariaDbAppliedObjectCardReader.php.diff
d677baedd742868cff6386ef8c9acf864f86e746faa55ea3663f6f1fded1ec14  ObjectCardView.php.diff
1210104ba7329b0952f0e821a3f1e733fe06d5376bbd1bddc31ba733761e662d  object_card_actor_name_001_test.php new-file diff
9cb31f1a15f0cccc51aeed52a9c259321aeefb7aecf6cd187a10f30628cf347f  combined production diff
```

## Findings

No blocking findings.

The reader enriches every card path it owns: missing application storage,
application not found with optional native-migration origin, and current applied
composition. It collects distinct positive immutable event actor IDs and resolves
them in one parameterized local-user query. It deliberately does not filter on
current user status or activation state, so historical names survive later account
blocking. Blank names fall back to the stored email; absent/blank records remain
`null`. Original `actorId` values and event ordering are unchanged.

The renderer uses `actorName` only when it is a nonblank string. Otherwise it emits
the truthful fallback `Пользователь недоступен · ID N`; it never substitutes the
current viewer. Both resolved names and fallback output pass through the existing
HTML escaper. The change reads local identity projection only and does not alter
events, users, authorization, history, or application commands.

The focused test is sensitive to the owner report and material failure modes: an
inactive historical actor with HTML-sensitive name, an active actor with blank name
and email fallback, a missing actor, immutable actor IDs, removal of the old numeric
label, and a viewer identity appearing only in its shell location. The real deployed
object 966 observation remains valid RED evidence for the predecessor runtime: it
showed the numeric author label without issuing a modifying request.

## Independent verification

```text
PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/InstallationProcess/object_card_actor_name_001_test.php
PASS object card resolves immutable historical actor names

php -l app/PilotHttp/MariaDbAppliedObjectCardReader.php
php -l app/PilotHttp/ObjectCardView.php
php -l tests/InstallationProcess/object_card_actor_name_001_test.php
PASS

PATH=/opt/homebrew/bin:$PATH php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

PATH=/opt/homebrew/bin:$PATH tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)

php rapid-pilot/verify-visual-contract.php
php rapid-pilot/verify-focus-contract.php
PASS

/Users/antropophag/.agents/skills/impeccable/scripts/impeccable detect --json app/PilotHttp/MariaDbAppliedObjectCardReader.php app/PilotHttp/ObjectCardView.php
[]

git diff --check -- reviewed files
PASS
```

The review performed no production/test edits, deployment, stand/data mutation,
remote action, or Bitrix action. It is bounded to the exact artifacts above and
does not claim full `VERIFY_OK` or production readiness.
