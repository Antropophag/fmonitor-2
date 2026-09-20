# Gate 5 review — MINIMAL-OPERATIONAL-DASHBOARD-001

- Verdict: **CHANGES_REQUESTED**
- Reviewer: Codex, `gpt-5.6-sol`, reasoning `low`, task `/root/issue21_gate5`
- Independence: reviewer authored none of the specification, tests, implementation, or Gate 3 record.
- Reviewed exact candidate source: `2f4b931bd92db37b99bf944fc32c493958330887bb777563f80372dc69eec135`
- Reviewed executable source: `5bf9a7fdd2e8dd221ff33c47c7c75c66f2fec42c510a8a0bbcb80532cbdf201e`
- Base: `f145e3e00f25644f5c4e32f7c2f3e8bba4f624a3`
- Role package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T221951Z-6b04d4fc26/package.json`
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T221951Z-6b04d4fc26/snapshot/source.patch`, recorded SHA-256 `3353a60bab5662dcc525848c9594eef2dd80836826b7581569c782fd26556723`
- Contract: `specs/MINIMAL-OPERATIONAL-DASHBOARD-001.md`
- Gate 3 authority inspected: `reviews/tests/MINIMAL-OPERATIONAL-DASHBOARD-001.md`, final Gate 3 verdict `APPROVED`, approved RED test blob `3f96040da5f43c7a7ae4d64084e6032042d55e22884d4ff0a38dc6eeb18a1ce9`, browser helper blob `79d2031e31024bc92cc63b91abfbf0acda518e1903727ed55a33753acfb22701`.

## Evidence inspected

All five package records are GREEN and bind both the reviewed candidate and executable source:

1. `php tests/Yii2/yii2_minimal_operational_dashboard_001_test.php` — record `1789942698969242000-150d08b959e248febd5597d579f32eea.json`, exit 0, current test blob `53b5b0b181001789d8b845153a39dfe87df1ec9baa8787b8bfd70ce3a1739c87`.
2. `php tests/Yii2/yii2_object_queue_001_test.php` — record `1789942698973683000-674b2e263adb4eb389c856c872937c64.json`, exit 0.
3. `php tests/Yii2/yii2_object_card_001_test.php` — record `1789942698984989000-8ef70fde3c8a4ca18caf473f482dbcf3.json`, exit 0.
4. `python3 tests/Verification/change_verification_001_test.py` — record `1789942698995714000-e6fd16152f144592a0812a1d9d19dc9c.json`, 18 tests GREEN.
5. `python3 tests/Verification/architecture_guard_001_test.py` — record `1789942698985834000-b89059e3a9ef4e7bba8606c9e579396c.json`, 59 tests GREEN.

The retained populated/empty/error screenshots at 1440 and 390 CSS px and their JSON geometry/hash records were inspected. They show the expected four metrics, two bounded lists, zero/empty state, safe unified error state, single-column mobile flow, and no horizontal overflow. Exact-source CI is still a later delivery step and remains `UNKNOWN`; this review does not claim CI GREEN or Done.

## Complete findings list

1. **[BLOCKING][security/regression] Dashboard response determinism was implemented by bypassing Yii's normal per-render CSRF masking for the shared logout form.** `app/YiiRuntime/ViewSupport.php` adds `stableCsrf`; when enabled by `dashboard.php`, it reads the stored session token directly and emits that stable value into HTML instead of using `request->csrfToken`. This changes a security primitive in the shared shell solely to make complete response bodies byte-identical. Yii's masked token output is intentionally allowed to vary while representing the same CSRF secret; exposing the stable stored token repeatedly weakens that protection and is not required by acceptance K, whose domain requirement is deterministic dashboard data and unchanged facts. Remove the production `stableCsrf` path and make the replay witness compare the dashboard semantics after excluding/canonicalizing expected CSRF masking variance. Because that correction changes the approved executable test, recompute the plan and obtain the required independent Gate 3 approval before implementation resumes.

2. **[BLOCKING][process/test lineage] The GREEN acceptance test is not the Gate 3-approved test and no approved test-delta record is present.** Gate 3 approved blob `3f96040d…`; the package executes blob `53b5b0b1…`. The post-approval delta changes the owner connection to enable query logging and relaxes the initial materialized-row assertion from exactly ten to at most ten. The first change is a reasonable fixture correction and the second better matches the normative `≤10` requirement, but delivery rules require test changes discovered after Gate 3 to restart at Gate 2 and receive fresh independent approval. The package has `test_delta_lineage: null`, contains only the stale Gate 3 review bound to earlier candidate `a375038e…`, and supplies no approval for this exact test blob. Current GREEN therefore cannot close the A–N executable lineage until this delta is independently approved.

3. **[MAJOR][authentication compatibility] The safe-return correction consumes but no longer clears the stored return URL after successful login.** `AuthController::actionLogin()` removed the prior `setReturnUrl('/pilot/objects')` after reading the return target, and its already-authenticated branch now also redirects to the stored value. Yii's `getReturnUrl()` reads the session value; it does not consume it. Consequently a dashboard return path can survive logout/re-login and override the established default landing on later sessions, an unrequested behavior change adjacent to the explicit non-goal of changing landing semantics. Preserve the new safe dashboard return once, then reset the stored return URL to `/pilot/objects` before closing the session and redirecting. Add a bounded regression for one-time consumption/default subsequent login; as a test change, route it through the required Gate 3 process.

4. **[MINOR][maintainability] `InstallationProcessFactory::dashboard()` declares an optional `$clock` callable that is never used.** The current acceptance test passes a closure to this parameter, creating the appearance that the owner is clock-controlled although the owner actually receives an explicit cutoff string and the controller owns the request clock. Remove the unused parameter and its test argument, or wire a real single owner only if the contract genuinely requires it. The simpler explicit-cutoff seam already satisfies the design.

## Conforming areas

Apart from the findings above, the reviewed implementation materially conforms to A–N: parameterized bounded SQL performs one aggregate plus two `LIMIT 5` reads; formulas, unknown-date handling, completed exclusion, stable ordering, and canonical queue parity are covered; access is checked before reading and forbidden/error bodies disclose no dashboard data; GET/HEAD/concurrent reads introduce no domain or audit writes; object-card authorization remains independent; links/navigation and root redirect are preserved; no DDL, cache, materialized summary, new dependency, `rapid-pilot` change, financial/chart scope, or private `shlz-ui` import was added. Public `shlz-ui` provenance hashes and the honest demo script are present. The queue/card compatibility changes pass their focused regressions, but findings 1–3 prevent final approval.

## Gate 5 decision

**CHANGES_REQUESTED.** Correct the CSRF masking regression and one-time safe-return behavior, remove the unused clock parameter, and independently approve the exact revised test lineage before preparing a new Gate 5 package. No local full suite was run. Exact-source CI, PR readiness, merge, deployment, and settings remain unclaimed.

---

# Gate 5 final rereview after corrections — 2026-09-21

- Verdict: **APPROVED**
- Reviewer: Codex, `gpt-5.6-sol`, reasoning `low`, task `/root/issue21_gate5`
- Independence: unchanged; reviewer authored none of the specification, tests, production implementation, or Gate 3 approvals.
- Reviewed exact candidate source: `814bb8052c205e30ea500e8c4dfdb0794a8f960e32474e8d39eaf942bac40296`
- Reviewed executable source: `200535dcf0d805ab97fb2e697b07ce2e581859c7451b7fa2f443d9b0e4f8a39e`
- Base: `f145e3e00f25644f5c4e32f7c2f3e8bba4f624a3`
- Exact role package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T223634Z-9da493e1d7/package.json`
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T223634Z-9da493e1d7/snapshot/source.patch`, recorded SHA-256 `906f6dd7a74570cdaf1f095e9d6cbf0f32596f7cc8790cd983a82daa9efce261`
- Controlling test/plan authority: `reviews/tests/MINIMAL-OPERATIONAL-DASHBOARD-001.md`, “Consolidated controlling Gate 3 test/plan delta approvals”, verdict `APPROVED`, current test blob `2bd9d4429a4b47af2536ffbc6a2305ad2a9537cfafb91c96d82d9462029a1dbb`, unchanged browser-helper blob `79d2031e31024bc92cc63b91abfbf0acda518e1903727ed55a33753acfb22701`.

## Exact evidence inspected

All five planner-selected records are GREEN and bind candidate `814bb805…` and executable source `200535dc…` at start and finish:

1. Dashboard A–N: `1789943671629190000-920796b84bbd4eedaae7e531e43a5d44.json`, exit 0, `PASS: MINIMAL-OPERATIONAL-DASHBOARD-001 complete A-N matrix`.
2. Object queue compatibility: `1789943671625143000-43104a0b56d244fab19854a518b80d9.json`, exit 0.
3. Object card compatibility: `1789943671625809000-c5ff9ec968ed41c79601d5dac1752499.json`, exit 0.
4. Verification governance: `1789943671636468000-c7e7d8d9eea14db2bba5b2310a8671b1.json`, 18 tests GREEN.
5. Architecture guard: `1789943671648533000-32ca1986680f4d789ea95cb99064cb46.json`, 59 tests GREEN.

The earlier direct-auth diagnostic that printed PASS is not a planner-selected record and is not used as approval evidence. The attempted nonexistent preopening test filename is likewise only a diagnostic invocation error, not a selected failure and not product evidence. No local full suite was run.

## Complete findings list

No unresolved findings.

1. **[RESOLVED][security] Normal Yii CSRF masking is restored.** `ViewSupport::begin()` again emits `request->csrfToken`; the dashboard no longer introduces a stable/raw-token path. Sequential and concurrent replay tests canonicalize only the `_csrf` hidden input value and compare every other HTML byte, while DTO equality and database fact fingerprints remain exact. This resolves the former security regression without weakening dashboard determinism.

2. **[RESOLVED][test lineage] The complete current executable delta now has persisted independent Gate 3 authority.** The consolidated controlling section approves the fixture aliases, normative `<=10` assertion, supported query logger instrumentation, DTO key correction, native curl HEAD behavior, bounded DB digest, public-provenance scope, CSRF-only normalization, one-time safe return, unused-clock removal, and verification ownership mapping. Current test blob `2bd9d442…` exactly matches the package command blob and is GREEN on executable source `200535dc…`.

3. **[RESOLVED][authentication compatibility] Safe return is consumed once and established landing semantics are preserved.** Anonymous dashboard access stores `/pilot/dashboard`; successful login reads that value, resets the stored return to `/pilot/objects`, and redirects safely. An already authenticated visit to `/pilot/login` again uses `/pilot/objects`. The focused acceptance test proves both the first return and the subsequent default landing.

4. **[RESOLVED][maintainability] The unused dashboard factory clock parameter is removed.** The controller captures one Moscow date per request and passes the explicit cutoff to the owner; the HTTP test fixes `FMONITOR_NOW` and checks the displayed cutoff. There is no misleading unused injection seam.

5. **[CONFORMING][A–G data semantics]** The read model uses canonical current facts, validates prefixes, checks the active actor and exact `objects.read`, calculates the four specified totals with strict overdue and closed upcoming boundaries, preserves completed exclusion and canonical queue status parity, and orders each `LIMIT 5` list by date, binary registration number, and object ID. Unknown dates are excluded. The normative `5/2/1/1`, all status branches, equality/+13/+14 boundaries, ties, and full-vs-top-five totals are executable.

6. **[CONFORMING][H–J public UI states]** `/pilot/dashboard` accepts only GET/HEAD, preserves the root landing route, places the permission-hidden dashboard link first without reordering prior links, and uses exact registry/card destinations. Forbidden access returns 403 without dashboard values/navigation; object cards retain their own authorization. Empty success renders four zero metrics and one honest empty state; incompatible schema produces one 503 safe error with no partial values, identities, SQL, or internal details.

7. **[CONFORMING][K–L read-only and bounds]** The owner performs one aggregate query and two bounded list queries; query count remains constant and at most ten rows enter PHP/HTML on 30,000 fixtures. GET, HEAD, repeat, and simultaneous reads preserve domain/audit fingerprints and deterministic semantics. No DDL, cache, summary table, state write, full-registry materialization, or hidden dataset was introduced.

8. **[CONFORMING][M–N UI provenance and demo]** Dashboard, Chart Widget, status, link, button, and empty-state bytes are pinned to reviewed public `shlz-ui` exports; no private import or alternative chart dependency exists. Retained populated/empty/error browser witnesses at 1440 and 390 show a readable single-column mobile flow without horizontal overflow or lost semantics. The demo names cutoff/formulas/navigation and clearly separates customer questions and future ideas from delivered behavior.

9. **[CONFORMING][scope, history, compatibility, security]** The candidate adds no new permission, domain fact, DDL, cache, financial/trend/personalization feature, `rapid-pilot` change, landing-route change, external dependency, or mutation seam. The object queue/card/schema compatibility corrections remain within existing owners and their focused regressions are GREEN. SQL values are parameterized; dynamic identifiers are bounded by strict prefix validation. Error logging records only the exception class, not data or infrastructure details.

## Final Gate 5 decision

**APPROVED.** The corrected exact candidate satisfies MINIMAL-OPERATIONAL-DASHBOARD-001 A–N, resolves every prior Gate 5 finding, preserves security and compatibility boundaries, and has current independently approved executable lineage plus five exact-source focused GREEN records. Exact-source GitHub CI is still the required post-review delivery step and remains `UNKNOWN`; this approval does not claim CI GREEN, PR readiness, merge, deployment, or settings changes.
