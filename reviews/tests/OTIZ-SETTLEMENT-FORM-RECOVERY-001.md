# Gate 3 — OTIZ-SETTLEMENT-FORM-RECOVERY-001

- Issue: #249; roadmap #169; retained history #248.
- Spec/test author: root Codex session.
- Implementation author: not yet assigned.
- Base: `d9dddb31f9c6e07092bcf6d4c04df761a1a13ccd` (`origin/main`, includes merged #260).
- Planner: CRITICAL; required reviews `gate3`, `final`.
- HTTP RED: record `1790275705036109000-07c9e576f78f498f9f72cd21ea24a4b4`, intended failure `1000` redirects to legacy closure error instead of exact cents success.
- Browser RED: record `1790275705035923000-9a6c10724d2744ef8ae18f28ac856271`, intended failure loses object context (`?error=closure` instead of `?error=closure&object=7940`).
- Setup note: a first run was invalid because the separate worktree had no local Composer tree. Dependencies were copied from the checkout cache and `composer dump-autoload --no-scripts` regenerated local absolute paths; retained RED records are after that correction.

## Independent verdict

**RETURNED — blocking findings.**

Reviewed exact candidate source `18208c7b0ee1c1997e05ab11ed2a217ef2bf0979fbb9937134108dfb44a5f7c9` from reviewer package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T185052Z-3ac3c19428/package.json`, against spec digest `cc5ce9078449ded92029cc16b6d165c579c88129f987a024b4aa6e806ef698bf`. The package is fresh and its bound test/spec digests match the checkout. I authored neither the spec nor the tests or implementation.

The two retained REDs are valid and attributable to missing behavior rather than fixture failure: HTTP fails first at accepted `1000` being sent to the legacy closure-error redirect (record `1790275808792022000-2f40d2d867db4733bab93db2eab5cd79`), and browser fails first because validation recovery loses `object=7940` (record `1790275835669142000-a51ca39c8b7c40998b60a8dd62e0d58e`). The isolated `Yii2AuthFixture`, per-run prefixes/resources, hard-coded expected cents, and before/after inventory checks are otherwise suitable foundations. Retained #248 history and settlement checks are exact-source GREEN.

### Blocking findings

1. **HIGH — A03 has no executable acceptance coverage.** Neither new test induces a confirmed owner/domain refusal after syntactically valid input, checks that the UI presents only the safe owner reason in the original snapshot/object context, or proves no-write for that path. `tests/Yii2/yii2_otiz_settlement_form_recovery_001_test.php:45-63` covers parser/field rejection and `STALE_CALCULATION`, but those are not a substitute for the separate known-domain-refusal contract in section 4. Add a deterministic owner-refusal fixture and assert status/return context, safe bounded message, retained allowlist/operation ID where intended, and byte-equivalent financial inventory.

2. **HIGH — recovery-state confidentiality and log absence are not proven.** The test creates a server log at `tests/Yii2/yii2_otiz_settlement_form_recovery_001_test.php:40` but never inspects it, despite A02 explicitly forbidding submitted values in logs. It also never stages recoverable state and then exercises snapshot/object mismatch, guest, revoked/denied permission, invalid CSRF, unknown snapshot, or unknown object to prove the state is not disclosed. The old GREEN checks only generic authorization/CSRF no-write behavior; they do not exercise the new server-side state. Add distinct actors/sessions and mismatched object/snapshot requests, assert the established 303/403/400/404 outcomes, absence of amount/basis/artifact/operation ID/extra secret in bodies, headers and logs, one-time consumption, and unchanged inventory.

3. **HIGH — replay/conflict and UNKNOWN recovery stop before the financial invariant.** The browser scenario at `tests/Yii2/otiz_settlement_form_recovery_browser.mjs:11` proves one intercepted request, disabled controls, an alert, and the old operation ID. It does not prove retained allowlisted form values, an explicit user decision with no automatic POST/poll/new UUID, exact replay with the same payload/ID, corrected payload with the same ID producing `OPERATION_CONFLICT`, or at-most-one append-only closure. The mapped legacy settlement test likewise does not execute exact replay/conflict. Extend the browser/HTTP flow through those observable outcomes and independently assert closure/event/receipt/job/outbox counts and the #248 history projection.

4. **MEDIUM — the strict decimal grammar oracle is incomplete at material boundaries.** `tests/Yii2/yii2_otiz_settlement_form_recovery_001_test.php:36,53` omits explicit valid minimum/sub-ruble input (`0.01`), canonical `1000.00`, dot-grouped `1 000.50`, leading-zero fractional forms such as `0,5`, and lower/upper adjacent boundaries; it also does not assert the pre-submit help text. A parser special-cased to the current examples could pass while rejecting contract-valid values or mishandling the owner limit. Add table-driven valid/invalid boundary cases with independently stated cents, including `0.01` -> `1`, `10000000000` -> max, and one-cent overflow, plus the rendered help and disabled-JS parity.

### Required correction

Return the complete corrected candidate with all four findings dispositioned and fresh intended RED evidence. Do not assign an implementation executor or mark Gate 3 approved until these acceptance gaps are closed. No production code was reviewed or changed in this Gate 3 pass.

## Correction review — exact source `38b4e00178e103c3e81e94e4bc8125641ff022f917aa1a2aae4e52aa1abdb16d`

**RETURNED AGAIN — one prior blocking cause remains incomplete.**

Reviewed the complete corrected tests and delta from package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T185506Z-1e06767347/package.json`. Spec digest remains `cc5ce9078449ded92029cc16b6d165c579c88129f987a024b4aa6e806ef698bf`; corrected HTTP test digest is `12b487863941a70022233a6b4025403624032ed0d1eebd37ffc8c4bb387284f8`. Fresh HTTP RED record `1790276084190671000-a042eb982ce4499d9b6c1d43202b5c21` fails at contract-valid `0,5`; fresh browser RED record `1790276084191703000-fbd4154da2714ee081cd1fd1faaf69ca` fails at the missing object-context redirect. Both remain attributable intended failures. Retained settlement record `1790276084191921000-362aa5d07ff34018ac5c39473eb71d5b` and #248 history record `1790276084199563000-dd5bdc7787904c40abd1ed81e2f19d10` are GREEN on this exact source.

### Prior-finding dispositions

1. **FIXED — A03 known domain refusal.** Snapshot 921 deterministically gives the owner 100 cents and submits 101 cents, asserts exact object-context recovery, safe user-facing reason without the raw code, and byte-equivalent no-write inventory (`yii2_otiz_settlement_form_recovery_001_test.php:68`).

2. **OPEN — recovery-state confidentiality remains only partially covered.** The correction adds guest, denied, invalid-CSRF, membership-mismatch, log, and one-time-consumption probes (`:59-64`), but the guest and denied assertions search only for `$basis`. They do not prove absence of the retained amount, artifact, operation ID, or extra secret as required by A02 and by the prior correction request. The suite also still does not exercise the explicit unknown-snapshot and unknown-object 404 paths required by A03; the valid snapshot 921 / foreign object 7920 membership mismatch is only one distinct case. This leaves implementations able to leak other allowlisted fields or mishandle unknown resources while passing. Add a shared forbidden-value assertion over body plus all response headers for amount, basis, artifact, operation ID and extra secret for guest, denied, bad-CSRF, mismatch, unknown snapshot and unknown object; assert the exact established status for each and unchanged inventory. Because this is the second return for the same foreseeable confidentiality cause, rebuild that complete rejection matrix before redispatch rather than adding another narrow assertion.

3. **FIXED — replay/conflict and UNKNOWN recovery.** The browser now preserves amount, basis, artifact and the same operation ID after an aborted single in-flight closure POST, with no retry/poll. HTTP then proves explicit corrected submit, exact replay with byte-equivalent inventory, changed-payload conflict, one closure/receipt, and the #248 history projection (`otiz_settlement_form_recovery_browser.mjs:11`; HTTP test `:66`).

4. **FIXED — decimal grammar boundaries/help.** The independent table now covers one cent, sub-ruble comma input, canonical `.00`, dot/comma grouping, the maximum, and overflow/precision rejections, while the rendered examples are asserted before submit (`:36,52-54`). No new blocker was found in the corrected parser/replay/domain-refusal additions.

### Verdict

Gate 3 is **NOT APPROVED** for exact source `38b4e00178e103c3e81e94e4bc8125641ff022f917aa1a2aae4e52aa1abdb16d`. One HIGH confidentiality/rejection-matrix finding remains open; production implementation must not start from this candidate.

## Complete rejection-matrix review — exact source `23ea3d8a454dbc01bc5ab7f17e65714a4079ebb390c14dee5f66825e5d65e89b`

**APPROVED — no blocking findings remain.**

Reviewed package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T185746Z-c0f158d1b4/package.json`, its complete delta, and the full corrected HTTP/browser tests. The bound spec remains digest `cc5ce9078449ded92029cc16b6d165c579c88129f987a024b4aa6e806ef698bf`; the rebuilt HTTP test matches package digest `bace89fcfc289c8ff3b7e7d72c2dd26133dabd7815eb2cb29dbf7d12fa2eee68`.

The remaining confidentiality finding is **FIXED**. `osfrAssertAbsent` checks response body plus every response header against amount, basis, artifact, operation ID, and extra unknown field. The matrix now covers guest 303, denied 403, invalid CSRF 400, valid-snapshot/foreign-object mismatch without disclosure, unknown snapshot 404, and unknown object 404. It proves the whole matrix leaves the financial inventory byte-equivalent, preserves the authorized one-time recovery state after rejected reads, excludes all submitted sensitive values from the server log, and consumes the state once. Distinct sentinels prevent one path from accidentally satisfying another path's oracle.

All earlier findings remain fixed: deterministic known owner refusal/no-write; explicit corrected submit, exact replay, changed-payload conflict, at-most-one closure/receipt and #248 history; UNKNOWN browser state with one in-flight request, same operation ID and retained allowlisted values; strict decimal boundaries and rendered help. Fixtures remain isolated and expected cents/statuses are independently stated. The adapter is exercised only through public HTTP/browser seams; the tests neither introduce another financial writer nor derive available money in presentation code.

Fresh exact-source RED evidence is valid: HTTP record `1790276246254607000-599af17b3d8c4cd7b3a10c1158c7df4e` fails at contract-valid `0,5`, and browser record `1790276246254622000-a31d7f4deee9414faeb8b4da9c84c280` fails at missing object-context recovery. Retained settlement record `1790276246254168000-5aa999f35e224b2ab8d937e5ac96246b` and #248 history record `1790276246270860000-f334413ec2cc4e75a33074c93cc358a6` are GREEN on the same source.

Gate 3 is approved for implementation against exact source `23ea3d8a454dbc01bc5ab7f17e65714a4079ebb390c14dee5f66825e5d65e89b`. This verdict approves the test/spec gate only; implementation, focused GREEN, independent final review, exact-source CI, and owner before/after acceptance remain required.

## Gate 5 correction test delta — exact source `d86c56680c463f56a10f5a24e053c4f3e0a9314165d9e444df699f8d19f15f95`

**APPROVED — the delta closes Gate 5 findings 1–3 without weakening prior coverage.**

Reviewed package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T193218Z-29da6bb3ba/package.json`, its delta from the Gate 5-reviewed implementation, the complete browser scenario, and the retained HTTP/security matrix. The new browser source is bound through the packaged browser wrapper; the unchanged HTTP recovery test matches digest `bace89fcfc289c8ff3b7e7d72c2dd26133dabd7815eb2cb29dbf7d12fa2eee68` and remains GREEN.

The delta gives each final-review defect a direct public-browser oracle:

1. A closure transport failure preserves the original operation ID and allowlisted values, performs no retry/poll, then an explicit submit reaches the normal `?closed=1` success outcome and MUST remove every UNKNOWN notice. This distinguishes confirmed server success from a genuinely unconfirmed response and closes final finding 1.
2. Payment completion now has its own aborted-response scenario. It asserts exactly one attempted POST, no automatic retry/poll, an explicit UNKNOWN notice, the exact original payment operation ID in both notice and recovered form, then explicit same-ID submit to `?paid=1` and UNKNOWN clearance. This closes final finding 2 and exercises the second financial public seam rather than inferring it from closure behavior.
3. The two scenarios are executable regression oracles for the previously missed behavior, while the synchronous two-submit guard remains asserted. This closes final finding 3.

The sequence is deterministic and isolated: request interception is scoped by exact route patterns and removed before explicit submission; operation IDs are read from server-rendered forms rather than predicted; navigation and success redirects are awaited; request counts prove absence of background retry. The submitted browser values are synthetic, artifacts remain in the fixture's private temporary directory, and the delta adds no URL/log assertions that expose credentials or financial secrets. It does not modify or bypass the previously approved guest/denied/CSRF/mismatch/unknown-resource non-disclosure, no-write, log-absence, replay/conflict, decimal, or #248 history matrix.

Evidence is coherent at candidate source `d86c56680c463f56a10f5a24e053c4f3e0a9314165d9e444df699f8d19f15f95`: browser record `1790278312940798000-e9828886fb7c4d7c89d6460e648c46da` is a valid intended RED at “confirmed closure success clears UNKNOWN”; HTTP recovery record `1790278312938448000-41c80792d36644e99151fe08cf335b0d`, retained settlement record `1790278312947954000-dfd84b46fb86484eb50ce4098dabf3f5`, and #248 history record `1790278312949695000-11b0a5f94bc640a0b74e73b02d5ab16e` are GREEN on the same source.

No new blocking finding was found. Gate 3 approves this correction-test delta for executor implementation; fresh focused GREEN and a new independent Gate 5 review remain required.
