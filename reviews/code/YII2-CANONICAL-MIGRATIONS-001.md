# Independent Gate 5 code review — YII2-CANONICAL-MIGRATIONS-001

- Date: 2026-09-11
- Reviewer: independently tasked agent `/root/gate3_migrations`
- Implementation author: separate executor agent
- Frozen package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T131643Z-31af35a515/package.json`
- Exact source digest: `49007c89a7edefd1e1d7cecce0e58a030004f0f37f4339c12c5ee579e20ef788`
- Base commit: `a567818d8355c719047f9396f044a4b4a3b442d3`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T131643Z-31af35a515/snapshot/source.patch`
- Snapshot patch SHA-256: `499eb1ff92a98cdc4c57ce60603e145d36cfcae06b9a32c7975d16606eff8ac9`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T131643Z-31af35a515/verification-plan.json`
- Verification plan SHA-256: `1e38120c96caa3759756548b76380f64df0831887bc3cb9b0a57f90e55bc0e03`
- Gate 3 delta verdict: `reviews/tests/YII2-CANONICAL-MIGRATIONS-001.md`
- Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the production candidate nor its specification or
tests. No production, specification, OpenSpec, task or test file was changed.

## Findings

### G5-1 — HIGH — a successful connection is not explicitly closed on charset failure

`app/YiiRuntime/CanonicalMigrationConsole.php:20-25` constructs mysqli and calls
`set_charset()` inside one `try`, then immediately returns
`DATABASE_UNAVAILABLE` from the `catch`. The only explicit close is the `finally`
at lines 47-49, which belongs to the later catalogue/application `try` and is
never entered when `set_charset()` throws or returns false.

This violates A2's requirement that the adapter own one complete mysqli
lifecycle per invocation. It is observable with the existing charset-fault
proxy: the server-side socket is left to PHP process teardown rather than being
closed by the application seam. It also creates an asymmetric resource path in
the new shared long-lived-capable service even though all ordinary catalogue
paths close explicitly.

Correction: initialize `$connection` to null before the connection attempt and
place connection plus charset negotiation and the complete application work
under one outer `try/finally`, closing whenever construction succeeded. Preserve
the existing mapping: connect/charset failures remain redacted
`DATABASE_UNAVAILABLE`/69, catalogue/setup unknowns remain `SOFTWARE_ERROR`/70,
and owner outcomes pass through unchanged. Add a deterministic assertion that
the charset-failure peer observes application-owned connection closure, or an
equivalent injectable lifecycle assertion; because that changes a test, obtain
Gate 3 delta review again.

### G5-2 — HIGH — required Gate 4 verification obligations are absent from the package

The bound verification plan requires the acceptance commands plus these focused
obligations before Gate 5: the changed registered production runner,
`tests/Deployment/pilot_jobs_compose_001_test.py`,
`tests/Verification/change_verification_001_test.py`,
`tests/Runtime/runtime_storage_001_test.php`, and
`tests/Verification/architecture_guard_001_test.py`. The package contains GREEN
records only for the six acceptance tests and the harness migration-stage test;
it contains no records for those five required focused commands.

This is consistent with
`openspec/changes/yii2-canonical-migrations/tasks.md:17`: Gate 4 task 3.4 remains
unchecked. UNKNOWN or omitted verification is not GREEN, so the reviewer cannot
conclude caller closure, adjacent runtime compatibility, Quality Graph
consistency or architecture compliance from this package. The owner prohibition
on local full `make test`/`make verify` does not waive these bounded focused
checks; exact-source full CI remains a later separate requirement.

Correction: after the lifecycle fix and any required test delta approval, run
every focused command in the regenerated exact-source plan through the delivery
harness, retain complete records, and prepare a new Gate 5 package containing
the full inventory. Do not run the forbidden local full suite.

## Conformance assessment without additional findings

The CLI controller at
`app/YiiRuntime/Commands/SchemaMigrateController.php:10-23` enforces the exact
route/option vector, prints one JSON line and returns the service sysexit. The
`bin/yii` wrapper exits with the bootstrap result, while the compatibility alias
rewrites both `$argv` and `$_SERVER['argv']` once and exits through the shared
bootstrap; no recursion or independent migration composition is present.

Apart from G5-1, `CanonicalMigrationConsole` preserves the old environment
grammar, one mysqli boundary, catalogue ordering and identity-access preflight.
It delegates lock, restart, suffix application, conflict and owner exception
mapping to the existing `CanonicalMigrationApplication`. Connection failures map
to 69, unexpected pre-owner failures to redacted 70, and established owner
outcomes—including busy, conflict, database unavailable and migration
failure—pass through without remapping. No test-only environment switch or
fixture class is referenced by production code; the Throwable substitution is
provided externally through PHP's test process.

Make and Compose use the canonical Yii command. Console configuration disables
the Yii DB component, and the checked built image contains Composer/Yii/mysqli
and passes its required/forbidden include trace. No web, worker or scheduler
caller owns migration facts. Output/error paths contain no secret coordinates,
exception details, SQL or stack traces in the reviewed tests.

## Exact GREEN evidence supplied

- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132507301253000-3c3c29a9c9534314b2c089c14785ed15.json` — CLI matrix and Throwable redaction.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132507272664000-991371a8fd424b1bb868d33a0673bf13.json` — single-owner structural boundary.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132507269879000-a274f04ade8f4e2b827bff65401454f9.json` — full fresh/repeat/upgrade/restart/conflict oracle through Yii.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132507263880000-52129240d6db4bd3ba9760a5f0ce30ff.json` — real-process canonical lock behavior.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132507284853000-eddab2aa3b7d4f1b9b09a81d0c98ae73.json` — alias output and durable-state equivalence.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132507320461000-d3399873114647b7a080eadca47233fd.json` — callers and host/built-image package/load closure.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789132507321809000-8333d07f13704bc382d0577caf3cafb3.json` — harness migration-stage regression.

All records name exact source
`49007c89a7edefd1e1d7cecce0e58a030004f0f37f4339c12c5ee579e20ef788`
and report no source drift. They report external fixture `UNKNOWN`; this review
does not reinterpret UNKNOWN as environmental approval. There is no exact-source
CI approval in the package, and none is inferred.

## Gate decision

Gate 5 is **CHANGES_REQUESTED**. Correct the charset-failure connection lifecycle,
obtain Gate 3 delta review for any test change, and supply every bounded focused
verification obligation from the regenerated exact-source plan. CI, deployment,
merge and completion remain unapproved.

---

# Independent Gate 5 correction review — 2026-09-11

- Reviewer: independently tasked agent `/root/gate3_migrations`
- Correction author: executor agent
- Frozen package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T134310Z-0beb5125cc/package.json`
- Exact source digest: `0007bc375902e33b6ec835a0843de23c98f8a86c83a5cf59c954f4f67f57ea22`
- Base commit: `a567818d8355c719047f9396f044a4b4a3b442d3`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T134310Z-0beb5125cc/snapshot/source.patch`
- Snapshot patch SHA-256: `ae43436aec6fe009fb52b2b8af6af58cfcf6c7ab0efae68d8f434c936d2a90af`
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T134310Z-0beb5125cc/delta.patch`
- Correction delta SHA-256: `7f60e2cd039e72ed8b038276a0373d866851a97b6fa2db80a6ae49e7aa95d495`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T134310Z-0beb5125cc/verification-plan.json`
- Verification plan SHA-256: `7aeb1ea1732bcff1d0c936902b4f225b6adb3730a89d856d86d36c4e224a7700`
- Approved lifecycle test delta: `reviews/tests/YII2-CANONICAL-MIGRATIONS-001.md`
- Verdict: **APPROVED**

The reviewer inspected the exact correction delta, complete reconstructed
candidate, approved tests and all twelve evidence records. The reviewer changed
no production, specification, OpenSpec, task or test file; this appended review
is the only change.

## Finding resolution

### G5-1 resolved — one outer lifecycle closes every constructed mysqli

`app/YiiRuntime/CanonicalMigrationConsole.php:19-52` now initializes
`$connection = null` before acquisition and places connect, charset negotiation,
catalogue selection, preflight and canonical application execution inside one
outer `try/finally`. The `finally` closes whenever the value is a mysqli,
including a successful connection followed by charset failure. A connect failure
has no owned connection to close. Nested catches preserve the required mapping:
connect/charset failures return redacted `DATABASE_UNAVAILABLE`/69, unknown
catalogue or adapter failures return `SOFTWARE_ERROR`/70, and canonical owner
results pass through unchanged.

The independently approved lifecycle assertion is GREEN in record
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789134064570577000-2acd71abab6d484aa6d44882e8547b4f.json`.
The full CLI/charset, schema, alias and concurrency tests are also GREEN on the
same exact source. No test-specific hook was introduced into production.

### G5-2 resolved — every bounded focused plan command has exact-source evidence

The corrected verification plan contains twelve focused commands, and the
package supplies twelve corresponding GREEN records. Independent inspection of
each record confirmed exit 0, outcome GREEN, source
`0007bc375902e33b6ec835a0843de23c98f8a86c83a5cf59c954f4f67f57ea22`
and `source_drift=false`:

- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789134002704827000-5b1b415aa54644f2924ed2f7a1404c2e.json` — built package, callers and load closure.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789134012765861000-4b8065381415419390d83478bc124524.json` — migration-stage orchestration.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789134015656163000-f1325ac126a74be4b00d3b918766dd48.json` — CLI grammar, failures and redaction.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789134017676707000-34e5dc4cb7014a4f94acb4e5600c8954.json` — alias outcome and durable equivalence.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789134023267693000-3d269fdfbef849ea9295dbc17b64bb83.json` — real Yii lock serialization.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789134027551515000-2bb21f9ba9fa4c4e8796c535b2defa40.json` — complete canonical schema oracle through Yii.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789134064570577000-2acd71abab6d484aa6d44882e8547b4f.json` — owner and outer mysqli lifecycle.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789134065231988000-a95dc1c1d22842f19ec26295aa3eafc6.json` — unchanged legacy runner oracle.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789134096736264000-d87f7cfda89444c69e79decb92418318.json` — adjacent jobs/Compose behavior.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789134138284474000-2a71753402814953b10bd87a3fa7ab35.json` — verification-plan governance.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789134154219437000-3217a6f5307b40b689b3fd20797fa03d.json` — adjacent runtime storage behavior.
- `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789134156569281000-6c832f18a2fb4dafbd0a9cfafb3b9f50.json` — architecture guard.

The OpenSpec Gate 4 tasks are now marked complete consistently with this
evidence. The full exact-source CI command remains a later integration-phase
obligation and was correctly not run locally under the owner decision.

## Regression assessment

No regression was found in the previously reviewed boundaries. Exact route and
option grammar, controller exit propagation, single JSON output, alias argv
rewrite and non-recursive bootstrap remain intact. The adapter still uses one
mysqli boundary and the existing catalogue/application/lock/ledger owner with
the established identity-access preflight. Fresh, repeat, predecessor upgrade,
rows/history preservation, restart/conflict and lock contention remain covered.
Make and Compose callers, built-image Composer/Yii/mysqli presence, forbidden
transitive load closure, ordinary web/jobs non-ownership, and secret/exception/
SQL redaction remain GREEN. No production path references the test catalogue or
a test-only environment control.

All evidence records retain fixture identity `UNKNOWN`; this review does not
reinterpret UNKNOWN as deployment or CI approval. The source and record binding
is exact, and UNKNOWN does not negate the explicitly recorded focused command
results.

## Correction gate decision

Gate 5 is **APPROVED** for exact source
`0007bc375902e33b6ec835a0843de23c98f8a86c83a5cf59c954f4f67f57ea22`.
G5-1 and G5-2 are closed, and no new findings were identified. This approval
authorizes progression to the separately required exact-source GitHub CI and
publication workflow; it does not itself approve CI, deployment or merge.
