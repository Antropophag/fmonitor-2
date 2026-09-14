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
