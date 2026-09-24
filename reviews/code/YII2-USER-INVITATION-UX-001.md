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

---

# Refreshed final review — CI correction delta v2

- Reviewer: `/root/issue250_final_review` (independent; authored none of the
  correction, its tests/contracts, verification mapping or Gate 3 restart)
- Supersedes the final verdict above for the corrected candidate only
- Review package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T193544Z-cb60d28f11/package.json`
  (`sha256:d7e042033f60c5a5cc27ed9d532fc91ff2e7cd4108b196a74836800588974008`)
- Exact candidate source:
  `be6b940f715d0597bc787ac502c3b8676a29052c640847109457d9cf379bf90f`
- Executable source:
  `68cc8c8aa58db3e1790387a58bcf6bcc3c58123ce35c5f56201599b2278eea84`
- Base/head snapshot commit: `025f3cada4afe8e18e082352b2c6f17b8dd24204`
- Delta from prior approved snapshot: package `delta.patch`
  (`sha256:3b5866a9b3222112a65719539d6be81c395aff85e8ffe85f4d171294e1acac08`)
- Reconstructible snapshot: package `snapshot/source.patch`
  (`sha256:5cd72b22d4e91a2c1659b9eba5ca75640be4a5ec970443b16e240a5231341a55`)
- Verification plan: package `verification-plan.json`
  (`sha256:a890115ac193c6e6a3843dc9b17763d9e12ed85f05d1fd93cec5fb02389635dd`),
  lane `CRITICAL`, required reviews `gate3`, `final`
- Verdict: **APPROVED**

## Correction and prior-CI inventory reviewed

I reviewed the complete refreshed package/context, its full delta against the
prior approved snapshot, the current complete candidate, the appended `Gate 3
restart — CI correction delta v5` (**APPROVED**), the verification mapping and
plan, all seven retained exact-source records, and the complete failed-job and
`REGRESSION_FAILURE` inventory for Quality Graph run `36046286695` on PR #262.

The first run's primary failures are complete and limited to:

1. `fast` job `107790614393`: the architecture scanner classified the DOM call
   `.select()` in `users.js` as a new SQL-ownership violation. Its other output
   was non-failing file-size advice.
2. `Integration (1/2)` job `107790614404`: exactly one
   `REGRESSION_FAILURE`, `tests/Runtime/yii2_production_web_cutover_001_test.php`,
   because the pinned `users.js` digest was stale.

`verify` and the top-level Quality Graph failed only as aggregate consequences of
those two jobs. Plan, unit, Integration (2/2), e2e and governance were successful;
Integration (1/2) completed its remaining inventory and reported exactly one
failure among 163 tests. No additional unresolved failure is hidden by the
aggregate.

## Delta findings and dispositions

No blocking findings.

`app/YiiRuntime/Assets/users.js` replaces only
`invitation.select()` with explicit
`invitation.setSelectionRange(0, invitation.value.length)` after the unchanged
`focus()`. This avoids the scanner false positive while preserving exact full
selection. The unchanged Chromium oracle independently requires focus,
`selectionStart === 0` and `selectionEnd === value.length`; its refreshed GREEN
therefore observes the behavior rather than merely the new spelling.

`tests/Support/yii2_production_web_cutover_contract.php` now pins `users.js` to
`13684f3f52349b8b9aca995af7c903f1ec2ae408db78e49377fcc117790f953b`,
which exactly matches the current asset bytes. MIME, cache, nosniff and
same-origin expectations are unchanged. `verification-input.json` adds the
existing runtime test and its support contract to the planned boundary and maps
`published-users-asset-contract` to the production Yii asset HTTP seam. The
regenerated plan binds both paths and selects the runtime test; this is the
missing consumer closure exposed by CI, not unrelated scope growth.

The checked compound task 5.1 records that the first independent review, PR and
exact-source CI execution occurred. It does not claim that the first CI was GREEN:
the append-only Gate 3 v5 record and this review explicitly keep corrected-source
CI pending. The checkbox therefore need not be rewritten merely to preserve the
historical correction cycle.

The prior LOW observation about duplicated trusted-origin validation is unchanged
and remains non-blocking. The correction does not touch trusted-origin handling,
token logging/storage, identity owner, TTL, one-time activation, reissue rotation,
roles, authorization, email behavior, form recovery or adjacent #249/#258/#260
scope.

## Refreshed exact-source evidence

All seven planner-selected records exit `0`, report **GREEN**, bind source
`be6b940f715d0597bc787ac502c3b8676a29052c640847109457d9cf379bf90f`,
executable source
`68cc8c8aa58db3e1790387a58bcf6bcc3c58123ce35c5f56201599b2278eea84`,
and environment
`c097b71807c953141825964edba292c021e615fbf4a878904de9e6cf9b56ab33`:

- production web cutover:
  `1790278415753482000-ab7080e82dee45788f83261a3d2221b2.json`
- user-access HTTP:
  `1790278415802571000-63e2c02a558341fda79cd11d489c9562.json`
- user-access browser:
  `1790278513627469000-15175eec335142e2aa3700dff807a2c2.json`
- deployment e2e category obligation:
  `1790278415811294000-307e5000ef5248c091cf754763a0f3a7.json`
- verification governance obligation:
  `1790278415817079000-08e2bc0df9494cd9ac9d9ab0e2e15c23.json`
- runtime integration obligation:
  `1790278530028976000-a28768dcfff04c559063a4b7713b869d.json`
- architecture unit obligation:
  `1790278415841282000-0d0c90986fb840dd8e49d986bd061199.json`

No full local suite was run.

## Refreshed decision

**APPROVED.** The CI correction is behavior-preserving, the published-asset
contract and verification mapping are exact, and the complete delta introduces
no new standards, specification, security or scope finding. This refreshed Gate
5 approval applies only to source
`be6b940f715d0597bc787ac502c3b8676a29052c640847109457d9cf379bf90f`.
The first CI run remains a recorded failure; corrected-source GitHub CI, merge and
deployment remain pending/`UNKNOWN` and are not implied by the seven focused
GREEN records.
