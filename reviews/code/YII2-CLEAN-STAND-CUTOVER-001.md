# Gate 5 — YII2-CLEAN-STAND-CUTOVER-001

- Reviewer: independent `gpt-5.6-sol / low` agent `/root/gate5_clean_cutover`
- Review package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T110639Z-802a48eda8/package.json`
- Reviewed HEAD: `95dd4429dec081ffbe798c2b70d0250f315f5e7f`
- Candidate source: `701dc38222e9597a46ce31222e4ba44677852acd96427f70c1787246cc587560`
- Executable source: `9c65fe721eec2f6d6f1656d6a54691af1a1ad9625c2dcb75d5f074def685a803`
- Base: `origin/main` (`f4dab7d6b576edc3bb6e9578a939e90b9a1f36ae` in the approved planning input)
- Real-evidence artifact supplied for review: `/Users/antropophag/.local/share/fmonitor-2/clean-stand/issue76-a9fd0c33-325e1f46/evidence/accepted.json`, SHA-256 `ce8df9cdbba9eeed458f6c96521834858db0213a8f3583cee569c87210b148a6`

## Scope and implementation assessment

The implementation remains within the clean-stand slice. The synthetic setup, enqueue workload, include probe, and deterministic transport are confined to `tests/Support/clean-stand` and an explicit acceptance Compose override. The production Compose file, public HTTP routes, public Yii console commands, and runtime image recipe do not acquire those acceptance interfaces. The mounted worker replacement passes `outbox.dispatch` through the existing `MariaDbOutbox` and `OutboxDeliveryHandler`; it maps the domain delivery result to the worker protocol's `completed` result while the existing owners write the outbox attempt and delivery facts.

The two production corrections are narrow and technically coherent:

- `MariaDbOutbox` normalizes MariaDB `DATETIME(6)` values to the canonical UTC-instant format before the existing `JobValues::date` validation.
- `YiiJobsRuntimeEnvironment` honors and validates an explicitly configured canonical table prefix. When the variable is entirely absent it retains the historical single-ready-manifest compatibility fallback; an explicit empty or malformed value fails closed. Normal clean production Compose supplies the explicit prefix and therefore does not inspect the legacy manifest.

The prepared focused package reports all mapped checks GREEN, including acceptance isolation, provisioning/runtime/golden/jobs/closure contracts, the DB-backed deterministic outbox-handler check, canonical jobs configuration, and existing jobs-console regression coverage. No test expectation was weakened to obtain those results.

## Findings

1. **HIGH — the supplied `CLEAN_STAND_ACCEPTED` artifact is not a valid exact-source result for the reviewed candidate, and its own evidence tree retains a contradictory failed substantive result.** The reviewed candidate is HEAD `95dd4429...` / source `701dc382...`, but `accepted.json` binds source `a9fd0c33dec2f522ece0d772d468f67aa7eb2707` and image `fmonitor2-runtime@sha256:325e1f...`. Two commits follow that accepted source, including the production change `95dd4429` to `YiiJobsRuntimeEnvironment`; therefore the live image is not the reviewed production candidate required by A1/A7 and Done 5.1. More importantly, the same evidence root contains `substantive-acceptance-result.json` whose terminal value is `CLEAN_STAND_ACCEPTANCE_FAILED`: construction-control returned HTTP 403, the outbox job remained `ready` with `JOB_HANDLER_FAILED`, closure was incomplete, and restart was not performed. `clean-stand-operations.jsonl` contains only `PREFLIGHT_OK` and `TARGET_ATTESTED`; it contains no append-only successful step sequence or terminal success fact that reconciles the later standalone `accepted.json`. The standalone artifact also does not have the shape emitted by the reviewed public seam (it lacks `authorizationDigest` and uses a scalar `target`, while the seam emits a target object). Thus the review cannot establish that the two authorized corrections were followed by the required single successful substantive run, nor that `accepted.json` was published by the reviewed acceptance owner after A1–A6. This is evidence integrity/exact-candidate failure, not an additional Docker-attestation or harness-hardening request.

   Resolve without adding any new acceptance primitives: either produce the already-required successful substantive acceptance record for one exact source/image that contains the final production delta, or make the reviewed candidate exactly the already accepted source and provide the missing successful append-only evidence for that run. The failed record must remain historical and must not be rewritten. A new standalone summary cannot supersede it without attributable successful observations.

## Verdict

`CHANGES_REQUESTED`

The code and focused verification are suitable, but Gate 5 cannot approve a candidate whose claimed real acceptance is bound to a different source/image and whose evidence tree's only detailed substantive result is failure. No new harness guard, polling, attestation primitive, OpenSpec change, production interface, or product decision is requested. Full exact-source CI and production-cutover handoff must wait for a reconstructible successful exact-candidate acceptance record. Production cutover remains unauthorized.

---

## Correction review under owner evidence waiver — 2026-09-14

- Review package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T112530Z-1c25dd37b8/package.json`
- Reviewed HEAD: `ae63b87cf8b8d89317c3c596b644f89397820642`
- Candidate source: `460fbaf7b320b82f69e871eeacb34791fb9a7f3874e41768f9ccd9b8daa70e4d`
- Executable source: `67c15db0fe6819b741ac78ad075676b4921738303c11dfb07461e7cd09fffae8`
- Verification plan SHA-256: `bbf7ce20c720aa359650c84704258f05a4423cd3a4ef98c69694b99ff7bcd52c`
- Controlling waiver: `docs/operations/yii2-clean-stand-cutover-owner-waiver-2026-09-14.md`
- Reviewer independence is unchanged; no implementation or stand action was performed.

### Waiver disposition

The owner explicitly accepts the existing disposable substantive evidence for closure of issue #76 and waives a second exact-source live run after the separately verified absent-environment jobs compatibility correction. This supersedes the evidence-rerun remedy requested by the preceding Gate 5 finding. The contradictory historical failed record and standalone accepted-summary limitation remain preserved and are not reclassified as canonical exact-source proof; under the explicit waiver they are non-blocking evidence limitations rather than unresolved code findings.

The waiver is bounded: it does not authorize production cutover, legacy-stand deletion, another rehearsal, restore, reconciliation, or rollback. It also does not represent fixture-mode GREEN as a new real-disposable execution.

### Correction code review

No code findings remain.

- The only post-acceptance production behavior change is the compatibility branch in `YiiJobsRuntimeEnvironment`. An explicit `FMONITOR_PROCESS_TABLE_PREFIX` remains the authoritative normal-production path and is validated before command execution. Explicit empty/malformed input fails closed. Only complete absence selects the historical single-ready-manifest fallback, preserving existing offline/root-pilot compatibility without making the clean Compose path depend on it.
- The fallback requires exactly one readable manifest, valid JSON, `state=ready`, and the existing bounded prefix grammar. Ambiguous, malformed, unreadable, or invalid legacy state remains rejected.
- Production Compose supplies the explicit prefix, so the accepted clean topology's web/worker/scheduler composition is unchanged by this fallback.
- The acceptance-only deterministic handler remains isolated in the explicit test override and routes delivery through the existing `OutboxDeliveryHandler`/`MariaDbOutbox`; it returns worker protocol `completed` only after the domain owner records a delivered outcome. It does not forge terminal queue or outbox facts.
- The package provides fresh GREEN evidence for all 12 mapped focused commands on the exact candidate source, including acceptance topology isolation, runtime closure, admission/provisioning/runtime/golden/jobs/result contracts, deterministic outbox delivery, canonical jobs configuration, and existing jobs-console behavior. Missing tests are empty.

The owner-recorded checklist provenance/date, fixed-port collision, and Created-state runner assertions are acceptance-harness limitations in the backlog. They are not production/application defects and do not affect this code verdict.

### Final correction verdict

`APPROVED`

Gate 5 is approved for the exact reviewed candidate under the explicit owner evidence waiver. One exact-source full CI remains required before merge-ready publication. CI `UNKNOWN` is not GREEN, and production cutover remains a separately authorized action.
