# Independent code review: YII2-AUTH-001 core

- Reviewer: `/root/inventory_review` (did not author authentication core)
- Baseline: `4e083473`
- Reviewed original commits: `47259381`, `63c9350c`, `d96524b8`
- Rebased equivalents: `ab420a1e`, `62178ff9`, `438591d7`
- Reviewed core tip: `438591d7`; corrective source reviewed on auth HEAD
  `def5bb7aa654b64b5259111daf54939bf5810894` with root-authored working-tree fix
  (`AuthController.php` SHA-256
  `cd69f56555e03fe197563fd95659756b5670dc3967f5cc29aa46cd819d8b4554`)
- Excluded: UI views/assets and deployment presentation commit `e9485333`
- Verdict: `APPROVED` for the authentication core scope. The separately authored
  no-legacy-load ratchet remains outside this review and requires its own recorded
  approval before task 3.3 closes.

## Standards

No documented-boundary violation was found in the Yii DB, canonical authorization,
standard session, trusted-host or safe-error composition. The new route uses the
existing `AuthorizeLocalActor` seam and exact permission facts; no legacy auth or
custom session adapter is loaded by the Yii composition. Focused authentication and
late persistence-fault suites are green on the rebased source.
The root-authored `urlManager.cache=false` correction is consistent with this minimal
composition and removes the observed attempt to resolve an unconfigured cache service.

Judgement call: `AuthController::actionLogin()` combines admission policy, rate-limit
mutation, credential verification, session login and redirect selection in one dense
method. This is possible Divergent Change and makes the ordering defect below hard to
see. It should delegate one explicit login application operation once the behavioral
defects are corrected.

## Spec

1. **Resolved — login-attempt state now clears only after session persistence.** Root,
   a different author from the authentication-core author, reordered the accepted
   path to populate Yii User/return state, explicitly close the Yii session, then
   clear attempts and create the redirect. The independently authored late-write
   fault fixture seeds two failed attempts and now proves a 503 preserves both while
   reaching the native session write boundary. Normal login still clears the bucket.
   `yii2_authentication_001_test.php` and `yii2_session_failure_001_test.php` both pass.

2. **Excluded completion evidence — task 3.3 claims a no-legacy-load architecture ratchet, but the
   reviewed production commits add only a narrow scanner exception for
   `ReliableSession`.** Existing broad checks may still pass, but the reviewed diff
   does not add an executable assertion that `/pilot/login`, `/pilot/logout` and
   `/pilot/admin/roles` load neither `rapid-pilot/LocalAuth.php` nor legacy custom
   session composition. Root has stated registry/architecture evidence is being added;
   This core verdict does not approve that separately authored ratchet; task 3.3 and
   slice closeout must cite its independent review and exact-source result.

The active-known identity proceeding to its named password form while
missing/invited/blocked/disabled identities remain on the neutral email response is
the inherited, independently approved two-step LocalAuth contract. Neutrality applies
to the reason copy, not identical form structure; no new account-enumeration policy is
introduced by this migration, so the earlier finding on that behavior is withdrawn.

Summary: Standards 1 non-blocking judgement-call finding; Spec core behavior has no
open finding. The previously blocking attempt-clear ordering is resolved. Separate
architecture-ratchet evidence remains explicitly excluded from this verdict.
