# Code review: ERP-EQUIPMENT-FACTS-OPERATIONAL-001

- Reviewer: `/root/erp_gate5` (`gpt-5.6-sol/low`), independent of specification, test and implementation authorship
- Reviewed commit: `0c2ab9373974bd82f5916dac254de3bfe10d8ddf`
- Base: `6e6ccbdbd4fa4d676fe94e9244e44aaaf411a36d`
- Reviewed source: `35f406ad7e56000917aa0df24858802c98e9880f36e665a4f6c6dbcfc88646b1`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T124833Z-1772441b39/package.json`
- Specification: `specs/ERP-EQUIPMENT-FACTS-001.md` and `openspec/changes/operationalize-erp-equipment-sync/`
- Earlier review: complete Gate 3 record `reviews/tests/ERP-EQUIPMENT-FACTS-OPERATIONAL-001.md`, including all correction-delta approvals through canonical SMTP/runtime env
- Exact-source CI: [run 35600091262](https://github.com/Antropophag/fmonitor-2/actions/runs/35600091262), head `0c2ab9373974bd82f5916dac254de3bfe10d8ddf`, terminal `success`; plan, fast, unit, e2e, governance, both Integration shards, verify, quality-results and Quality Graph succeeded (`harness` was the workflow's expected skipped job)
- Live qualification evidence reviewed: automatic job `7` completed; safe run `5d8fd71e-c7f0-4469-9503-394dfbf84ad0`, matched `4`; cards `1226`, `1427`, `2238`, `2239` returned HTTP 200; object `1318` remained unmapped; identical repeat retained 9 history rows; object count 334 and database/session/artifact volumes were reported preserved
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **BLOCKER — unsupported ERP claims have the wrong worker-level terminal classification.** The normative contract requires an unknown ERP type/version to be rejected before source access as non-retryable `CONFIGURATION_INVALID` (`specs/ERP-EQUIPMENT-FACTS-001.md`, “Hourly native invocation”). `JobHandlerClaim` does reject an unsupported version, but the actual worker boundary does not preserve that contract: an absent registry entry becomes permanent `JOB_HANDLER_UNAVAILABLE` in `app/Jobs/JobWorkerProcess.php:23`, while a launched handler that exits 64 with the safe `CONFIGURATION_INVALID` JSON is converted by `app/Jobs/JobWorkerProcess.php:26` into retryable `JOB_HANDLER_FAILED`. The new regression asserts the direct handler process only; it never proves durable worker settlement for the unsupported ERP claim. Add a worker/queue public-seam regression and implement one exact non-retryable configuration outcome before ERP access.

2. **BLOCKER — SQL Server certificate authentication is disabled.** `app/InstallationProcess/MariaDbSqlServerEquipmentFactsTransport.php:11` uses `Encrypt=yes;TrustServerCertificate=yes`. Encryption without peer verification permits interception of the ERP password/source facts and injection of false equipment facts. Gate 3 recorded this as a live compatibility correction, but neither the normative contract nor the owner instruction authorizes disabling server authentication as the production/pilot trust model. Configure an explicit trusted CA/certificate path (or an equally authenticated SQL Server trust mechanism), retain encrypted transport, and cover rejection of an untrusted server without exposing DSN or credentials.

3. **MAJOR — ERP password and diagnostic-HMAC authority are injected into unrelated runtime services.** `deploy/runtime/compose.yaml:1-39` puts all ERP values into the shared `x-runtime-environment`, inherited by `prepare`, `local-integration`, `migrate`, and the HTTP `php` service as well as jobs. The accepted requirement is to wire ERP configuration to the jobs contour; the other services do not need this external read credential or HMAC authority. Split common configuration from a jobs-only ERP environment and preserve byte parity in `tools/delivery/compose.runtime.yaml.in`. Tests should assert that required jobs consumers receive the keys and unrelated containers do not.

4. **MAJOR — the mandatory manual operator command is not documented.** The implementation exposes `php bin/yii erp-equipment-facts-sync/run --interactive=0`, but no operator runbook documents invocation, safe result shape, failure behavior, or the requirement to use the canonical composition. This leaves the explicit “documented manual command” acceptance statement partial. Add the bounded operator procedure without credentials, DSN, SQL, source rows or raw `zavnumber`.

5. **MAJOR — the authoritative delivery record still reports completed gates and publication facts as UNKNOWN.** `docs/operations/issue-12-erp-operational-delivery.md` says Gate 3, focused GREEN, PR/exact commit/CI and Gate 5 are pending despite the committed Gate 3 approvals, PR #218 and the successful exact-source CI above. OpenSpec task 6.4 requires the actual PR, commit, CI, review verdicts and stand receipt. Update the record after correction/review; keep Gate 5 non-GREEN until every blocker is closed.

6. **MINOR (maintainability judgement) — sensitive chunk processing is unnecessarily opaque.** `app/InstallationProcess/NativeErpEquipmentFactsDelivery.php:14` uses `$a`/`$b` for the two ERP result sets and compresses fetch, normalization, duplicate detection and accumulation into one line. This is a possible Mysterious Name/readability smell around the whole-batch/no-partial-write invariant. Rename the values (for example, order rows and shipment rows) and split the control flow while making the required correction.

## Conformance confirmed

The reviewed implementation otherwise matches the accepted behavior for exact v1 ERP claim/handler success and retry, current-slot scheduler idempotency, local unique nonzero candidate selection, parameterized chunking, fully-qualified `[1c-erp]` joins and aggregate/sentinel semantics, authoritative explicit clear versus absent order, ambiguity/no-arbitrary-write handling, failed-batch atomicity, raw-order privacy, direct ERP `.env`, canonical SMTP/runtime validation, Linux `0600` Bitrix secret staging, process versus operator health separation, canonical/generated Compose parity, state-preserving startup/recovery, and protected card readback. Tests exercise these boundaries with disposable databases/fake sources and exact-source CI is GREEN, but CI success does not override the blockers above.

## Required rereview

Return one correction delta with every finding marked `fixed`, `open`, or justified `not-applicable`; recompute the verification plan for changed production/tests/spec/docs, obtain any planner-required Gate 3 delta review, run the bounded affected frontier, and then use the required exact-source CI/review process. Do not merge or externally deploy while this verdict remains `CHANGES_REQUESTED`.
