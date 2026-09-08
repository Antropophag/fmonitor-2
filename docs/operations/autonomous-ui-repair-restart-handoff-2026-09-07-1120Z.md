# Restart handoff — manual pilot UI repair, 2026-09-07

## Controlling instruction and goal

Owner requested a restart handoff **after finishing the current UI repair**.
Stop at that checkpoint; do not start another feature slice in this session.
Persistent goal remains ACTIVE, without a token budget. If absent after restart,
restore exactly:

> Довести портал FMonitor 2.0 от фактического состояния repository и remote до проверяемой готовности к запуску в тестовую эксплуатацию, соблюдая все delivery gates, append-only evidence, exact-SHA verification, CI, clean deployment/restart/golden-path и отсутствие launch blockers

Read AGENTS.md, PRODUCT.md, CONTEXT.md, pilot spec/data model, development-process
and **docs/operations/current-delivery-goal.md**. Current owner-approved manual
pilot mode overrides old full-gate prerequisites for the intermediate stand.
First stand target17:00 Moscow, usable manual pilot22:00 Moscow on2026-09-07.
Manual feedback and ordinary complete journeys take priority over rare-case gates.
All agents must use **gpt-5.6-sol / low**; parallel independent work is authorized.

## Critical owner feedback and new verification preference

Owner reported login403, then a broken sidebar and displaced/unstyled layout.
We prematurely handed off based on HTTP responses without a proper visual login
check. Root acknowledged responsibility. Do not blame owner changes to shlz-ui.
The primary causes were simplified canonical renderers replacing the existing
rapid-pilot shell/table/card, missing navigation capabilities on local profiles,
and object-details ES module loaded as a classic script. A Docker shlz pin mismatch
was also found, but was not sufficient explanation for the structural failure.

**Use headless Playwright for subsequent browser testing.** Owner explicitly
prefers invisible automation. Do not operate their real Safari/Chrome windows for
routine checks. They previously unlocked Safari autofill, but this does not waive
the newer headless preference. Do not ask them to repeat that step. Check real
clicks, screenshots, console/network errors; HTTP200 alone is insufficient.
Preserve and connect the already-designed rapid-pilot UI, not simplified templates.

## Repository and deployed stand

Repository `/Users/antropophag/code/fmonitor-2`, branch
`codex/remove-pilot-work-navigation-v2`. Deployed code source:
`09ec4bf57d37fd3fafc97f87eba01cde98672dac`.
Closing handoff commit is documentation-only on top; final answer gives that HEAD.

Local URL: http://127.0.0.1:8092/pilot/objects
New Docker project `fmonitor2-manual`, pilot container `fmonitor2-manual-pilot-1`,
MariaDB `fmonitor2-manual-mariadb-1`; image tag `fmonitor2-manual:ui-verified`.
Separate volumes `fmonitor2-manual_mariadb-data` and `fmonitor2-manual_pilot-state`.
Runtime compose override/env are private, outside repo:
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/`.
Use compose.yaml plus that directory's compose.override.yaml and --env-file
preview.env, project fmonitor2-manual. NEVER print env/password values.
Bootstrap account is the pre-existing configured preview owner account; no legacy
users were imported. Owner assigns roles to himself and invites colleagues himself.
Superadmin intentionally has no automatic FKR/engineer business powers. Do not
silently add roles; owner explicitly pointed to self-service role administration.

Owner authorized removal of the previous preview. Both old preview containers
were removed; old volumes remain detached recovery copies:
`fmonitor2-local-preview_mariadb-data`, `fmonitor2-local-preview_pilot-state`.
Do not attach these to the new stand. Synthetic test DB at127.0.0.1:23306 remains.
No broad Docker prune, remote mutation, PR10 merge or CI publication was performed.

Dockerfile now pins shlz-ui `9aaedf50eabf5f92e4af1cbc9c0f2a26a171b35b`, matching
the locally used public component exports. Consume only public shlz-ui APIs.

## Data actually published

381 source objects imported successfully into381 unopened installation cases,
381 technical-detail snapshots, one checklist template (8parts,42definitions;
41work items total85 and documentary item42 weight15). No users, open cases,
checklist history/photos, old assignments, payments or PDF originals migrated.
Source eligibility: no actual opening and no checklist activity/history, including
retractions/photographs/attribution; **no planned-date restriction at all**.
Source snapshot raw legacyFinish stays external evidence; operational mirror has
workdatefinish=NULL (not a newly asserted completion fact). Null planning dates
are permitted. All381 objects had required identifying fields.

Private snapshot: `~/.local/state/fmonitor2/manual-pilot-20260907/source-snapshot.json`
SHA256 `c64e8e8813c9e982ff3ca378a5dda79991bbfff31943ff79a776a2b9aafc6184`.
Native importer: bin/fmonitor2-import-pilot-snapshot.php, owner
MariaDbPilotSnapshotImporter. Actual import safe result is in private runtime.
Do not use old rapid-pilot/import-production-objects.php: it retains superseded
planned cutoff; the new private-snapshot route is the delivered migration route.

Authorized live readonly Bitrix fetch completed26pages/27attempts,1264records.
Source portal/config derives from legacy getinstallators integration; departments
1726/1766. No Bitrix writes or FMonitor account creation. Private batch:
`~/.local/state/fmonitor2/manual-pilot-20260907/bitrix-delivery.json`, SHA256
`44feeeda14201d0c2e5ca21ceb1076a59f0ad705e8853a58f4ec713f96915184`.
Native buffered publication run `aa830484-0bb2-4c1d-bc63-4464049b9dce` completed:
1264catalog entries,929employed/335dismissed, original observedAt
`2026-09-07T10:20:37Z`. All employment start dates unknown, preserved NULL.
Email tab/UF_XING numbers legitimately contain leading zeroes; normalization
removes numeric padding, preserves identity and rejects conflicts/overflow.
UF_XING alone may be absent; remaining required-field validation stays strict.
Hourly native scheduling is **not configured**; UI no longer promises hourly sync.
Private buffered runner: `.../manual-pilot-20260907/run-workforce-import.php`.

Legacy readonly credentials: `~/.local/state/fmonitor2/secrets/legacy-db-readonly-2026-09-07.json`
(mode0600). Actual MariaDB endpoint reachable directly over corporate VPN; the
restricted Git SSH host was a wrong route, not proof of DB inaccessibility. No
source writes. Bitrix token/config are in private `~/.local/state/fmonitor2-secrets/`.
Never copy primary records/secrets into repository, messages or review artifacts.

## Delivered code and approvals to reuse

Native Bitrix delivery full gates were previously approved; optional UF_XING v0.3
and later manual integration changes have focused evidence, not retroactive gates.
Do not restart the old0655Z handoff's Gate3 as the next step.
Canonical schema catalogue is18; repeat migrate/startup preserves data and invited
users. Startup mirror additionally has adjusted start + six technical fields.
Application and corrected-original reapplication are explicit, append-only before
opening. Separate opening stores actual date and binds template at opening instant.
Unknown employedFrom is allowed only with employed status/full successful snapshot
proof; freshness age warns but does not block. Never substitute observation date.

Checklist supports actual parts/definitions template, photos, item retraction,
section completion/reopening and append-only operations. Shared progress is
retraction-aware. Completion owner records/corrects PTO and mandatory declaration
with history,85% work +15% documents. Current authorization decisions:
- PTO/declaration record/correct: fkr_operator and manager.
- Photo upload/section completion: any active construction_control_engineer or manager.
- Item completion retains approved capability-only any-object rule.
- Photo revoke retains its exact capability + assigned-engineer + reason/confirmation
  rule; broader upload authorization did not automatically broaden revoke.
- Item retraction: original actor or currently assigned engineer.

See composition-reapply-unknown-employment-owner-decision-2026-09-07.md,
manual-pilot-roles-freshness-owner-decision-2026-09-07.md and pilot-data-transfer-plan.
Other approved functions, including OTIZ, remain pilot scope, not silently dropped.

## UI repair and evidence

Restored shared shell in ObjectListView/ObjectCardView/PrepareFormView/PilotShellView;
formatted incumbent table and rich card restored. ObjectDetails/CompletionFlow
receive their original expected layout markers. Native apply/open routes remain
current `/execution`; no restored legacy registration writer or old POST/open form.
Local principal and trusted-ID profiles both carry actual role permissions for
navigation. Original/unknown provenance labels are corrected for snapshot objects.
Object details script now loads with type=module so public SHLZ tabs initialize.
Stale preview cookie login recovery now issues replacement cookie when session ID
changes; CSRF validation remains intact. Native isolated lifecycle regression PASS.

Headless runner uses installed Playwright1.62.1 from sibling node_modules (public
Playwright package), Chromium headless cached under ~/Library/Caches/ms-playwright.
Private runner: `.../runtime/headless-ui.cjs`; reads credentials privately, closes
browser in finally. Screenshots/results stay in that private runtime directory.
Run: PATH=/opt/homebrew/bin:$PATH node <absolute-private-runner-path>.
See the appended final receipt below for final browser outcome and image digest.

Focused native application/reapplication/opening, unequal case/object IDs, HTTP
original/history/download/application/open/card, checklist/photo/retraction,
completion85/100/corrections, unknown employment, startup/restart and source import
checks passed during this session. Visual/focus contract checks pass after repair.
The broader protected E2E still expects old manual registration; do not restore
obsolete writers or silently rewrite protected tests merely to turn it green.
Protected test hash remains8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b.

## Honest remaining work

- Owner manual feedback is first priority. Full real-stand business journey has
  not been completed with invited FKR/engineer accounts. No real object was opened
  just for a UI test. Owner sets roles/invitations; preserve all their subsequent data.
- Check ordinary selection→PDF/original→apply→open→checklist→PTO/declaration on
  the populated stand with authorized test identities/data, using headless.
- Native hourly workforce worker wiring remains pending.
- Some broader shell/card tests still encode deliberately removed simplified UI;
  reconcile expectations to the restored incumbent UI with honest review evidence.
- Last architecture check after ObjectQueue projection change found two
  sql_ownership fingerprints in rapid-pilot/ObjectQueue.php. Extract that read
  projection behind its owning seam later; do not rebaseline debt silently.
- Full make verify/CI/Gates3&5 for manual changes are not complete. No VERIFY_OK
  or production-ready claim. Latest Impeccable launcher hit executable permission
  error; full detector was not run. Do not describe it as passed.
- Same-bytes photo reupload after revoke may still meet old uniqueness constraint;
  document/fix based on actual manual need, not an expanded rare-case matrix.
- Check OTIZ/other approved sections after the primary journey; do not narrow scope.

Agents manual_application/manual_checklist/manual_completion finished their bounded
work (sol low). Reuse if available in the new session, otherwise spawn sol low only.
Untracked `.DS_Store` and `docs/.DS_Store` are owner artifacts; leave unstaged.
No secret values in handoff. No goal completion; restart checkpoint only.

## Final headless receipt

Deployed code09ec4bf57d37fd3fafc97f87eba01cde98672dac, image
`sha256:85e10300a4520482698e7a75e3c33e15f49642fe20829831413a1fd6e8e4aa63`,
container healthy. Actual login →50table rows/381total →sidebar collapse260→72.75px
(animation approached72px) →Users(4navigation links) →Roles(table) →Objects →
imported card →Team tab aria-selected=true →dates tab →mobile390px: PASS.
No pageerrors, no network response>=400, no mobile document horizontal overflow.
Screenshots inspected for desktop table, user management, rich card and mobile table.
Native full business mutations remain unexercised on actual source objects.
Browser closed in finally; no real Safari/Chrome actions after headless preference.
This resolves the reported shell/list/card/navigation integration defects for the
checked route, not all remaining pilot work. Stand remains running for owner.
