# YII2-USER-INVITATION-UX-001 — independent final / Gate 5 review

- Reviewer: `/root/issue250_final_review` (independent; authored none of the
  reviewed specification, OpenSpec artifacts, tests, production code or Gate 3
  records)
- Review package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T190545Z-fccefd924b/package.json`
  (`sha256:ee9d43d70d54fc990c256645069e5f8083e2ec1f18f9925f3c9ecd28fc321468`)
- Exact candidate source:
  `69cbb364dd94da11b9402fdcd17cfc3e3ec9aff71a0e3d5bf5980d73906444ce`
- Executable source:
  `3cad3fe9fdc89c0575e5b0548b2265f56506e58db82fa083b009c720e44c9232`
- Base commit: `d9dddb31f9c6e07092bcf6d4c04df761a1a13ccd`
- Reconstructible snapshot: package `snapshot/source.patch`
  (`sha256:b39cc71c448b0ca143de550f79012d2458453a549c653df3af4434c2340c6c4a`)
- Verification plan: package `verification-plan.json`
  (`sha256:b9b4547376ef5a1bd7679c78b76253e8423e3ae444f1a65faa5c2b123fbdc474`),
  lane `CRITICAL`, required reviews `gate3`, `final`
- Verdict: **APPROVED**

## Authorship and review independence

The declared and observed route is `separate_executor`: root authored the
normative specification, OpenSpec scope and acceptance tests; a separate executor
authored the controller/view/asset implementation; `/root/issue250_gate3`
independently reviewed the tests and their later setup correction; this reviewer
authored none of those bytes. The latest Gate 3 decision is the append-only
`Gate 3 restart — test setup delta v4`, **APPROVED** for the two root-authored
test corrections on source
`d98dbf27c288edb4e4e1a59c7aed10758af9d59e8ca8a31efd29da0ae57eae7b`,
after the earlier v3 approval of the complete corrected pre-production tests.
That restart explicitly reserved production judgment for this final review.

## Evidence reviewed

I read the complete prepared package and mandatory context, normative contract,
all four OpenSpec planning artifacts and verification input, the complete Gate 3
record including its historical returns, v3 approval and v4 restart approval,
the full production and test diff, the snapshot manifest, and all six retained
records. `harness.py state` reports the current dirty worktree at exactly the
package source and executable source; every package-bound file digest matches the
verification plan, and the snapshot patch digest matches its manifest.
This review record is the sole post-package addition and is not represented as a
change to the reviewed production/tests/specification snapshot.

All six planner-selected focused records are exact-source **GREEN**, exit `0`,
and bind the same candidate source, executable source and environment
`c097b71807c953141825964edba292c021e615fbf4a878904de9e6cf9b56ab33`:

- `php tests/Yii2/yii2_user_access_001_test.php` —
  `1790276678641362000-f87cf04e3b544fd99b1577f58b730fe5.json`
- `php tests/Yii2/yii2_user_access_browser_001_test.php` —
  `1790276678626510000-2dbe1d2b37c1465dbeb2e1c138067e19.json`
- `python3 tests/Deployment/pilot_jobs_compose_001_test.py` —
  `1790276678645815000-5e3179f0d7464f7791be455318d535d6.json`
- `python3 tests/Verification/change_verification_001_test.py` —
  `1790276678671179000-f9913457431941a485e26c27f76f384f.json`
- `php tests/Runtime/runtime_storage_001_test.php` —
  `1790276732312807000-33c19625acb9495484bc05d97fed53d2.json`
- `python3 tests/Verification/architecture_guard_001_test.py` —
  `1790276678661533000-090e3d16fb744b1492ac91eef4282afc.json`

No full local suite was run. The plan-selected `make test` exact-source GitHub CI
remains pending and is not represented as GREEN by these focused records.

## Standards

### LOW — duplicated trusted-origin validation (non-blocking)

`app/YiiRuntime/Controllers/UserAccessController.php:106-108` repeats the scheme,
host grammar, port, IPv4/domain and length validation already owned by
`app/Runtime/RuntimeConfiguration.php:44-63`. This is a judgement-call
**Duplicated Code / Shotgun Surgery** smell: a later trusted-origin policy change
could make runtime bootstrap and invitation presentation disagree. The validators
are byte-for-behavior equivalent for the currently supported host forms, so this
does not create a present conformance or security failure and does not block this
bounded slice. A later cleanup should expose one validated origin/value seam
rather than broaden this candidate.

No other documented-standard violation or baseline smell was found. Domain
mutation remains behind `YiiUserAccess`; controller/view/asset code owns only
HTTP presentation and progressive enhancement.

## Spec

No findings. Create and reissue construct the absolute activation URL solely from
validated `FMONITOR_TRUSTED_REQUEST_SCHEME` and
`FMONITOR_TRUSTED_REQUEST_HOST`; request `Host`, `Forwarded` and
`X-Forwarded-*` do not become the origin. Missing, unsupported or malformed
configured origins fail with the specified safe `503` before owner mutation.

The delta adds no token log call or durable storage. The raw token remains only in
the inherited one-read invitation/session presentation path; the focused oracle
checks application/process output before token-bearing navigation. Identity owner,
schema and invitation persistence code are untouched. Existing TTL, token hashing,
one-time activation, reissue rotation/revocation, roles, authorization, activation
and no-email behavior are preserved and exercised by the inherited public-seam
matrix.

Rejected invitations retain escaped email and full name, identify and focus the
actionable field with a real ARIA description, and do not invent a duplicate
diagnosis. Clipboard success is announced only after Promise resolution; rejection
and absent API select/focus the readonly field and retain manual instructions.
Keyboard use, narrow viewport, no-JS operation, clean recipient context and the
pending double-submit guard are covered by the exact-source browser GREEN.

The candidate changes only the permitted user-access controller, view, local
asset, lifecycle/specification and focused tests. It contains no behavior from
adjacent issues #249, #258 or #260 and no changes to shared shell/navigation/CSS,
deployment, email delivery or IdentityAccess ownership.

## Decision

**APPROVED.** There are no blocking findings on the exact prepared source. The
single LOW maintainability observation does not alter current trusted-origin
security or acceptance behavior. Gate 5 is approved for this source only.
Publication, the required exact-source GitHub CI, merge and deployment remain
`UNKNOWN`/pending and require their normal subsequent gates.
