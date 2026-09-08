# Code review: ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-ISOLATION-001 v1

- Reviewer: separately tasked agent `/root/selection_v04_readiness`
- Reviewer authored neither reviewed tests nor production implementation
- Reviewed commit: `3583ef866be64765017b995f80d4d1c64b8db695`
- Base commit: `f0862b08d1922f9e3ab2f31b39ac9c32078579e8`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-ISOLATION-001.md`
  v0.1, SHA256
  `ef245ec06ad9ed0a7d9c365386077a22be5547eb625ba276ba4f603d7b310942`
- Independent Gate 3:
  `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-ISOLATION-001-v1.md`,
  SHA256 `080c8a9e5ac8e2a6401dcb69d802d4ca1ed2490fb6503f9975e8fc073ae9e491`
- Verdict: **APPROVED**

## Exact reviewed implementation and test hashes

```text
8ea2b82300c157f2ca6c69b9b4eef5ab773d8e612d22f6725d4d88aa705c013f  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
fc24345234606a10c9175c340b7dc29b752131e0170447d4fd38f8f9dd150d75  app/AssignmentOrderOriginal/AssignmentOrderOriginalBestEffortSafeLog.php
e39895f27c9041d14d85e6f9d51ac1f411ef4e7c7178c165756dfbb66a2a2056  tests/InstallationProcess/assignment_order_original_safe_log_isolation_001_test.php
20cdecb500b01c721c90b7fb4fb95878f4b612fe0e73f850850da452b4fb18df  tests/Support/AssignmentOrderOriginalLogIsolationFixture.php
7a3c99f947c7c4e005b1ea1552d7caa331be7f8c65b5454a44645509d59b2b6f  docs/operations/original-safe-log-isolation-green-v1-2026-09-06.md
```

Private aggregate regression evidence:

```text
d3d33705d66572b5b490134b44f103f06d2f00e0ee82d66f12cbba5bc3b843a5  /Users/antropophag/.local/state/fmonitor2-verification/original-combined-green-475ujgo9/evidence.json
```

The evidence records identical before/after HEAD
`3583ef866be64765017b995f80d4d1c64b8db695`.

## Production diff and construction paths

The production change is minimal: one new 38-line guard and one dependency
composition change. `AssignmentOrderOriginalDependencies` retains the public
`AssignmentOrderOriginalSafeLogObserver` property type and wraps the supplied
observer exactly once. `AssignmentOrderOriginalService` and all cleanup/release
call sites remain unchanged; their existing calls now cross the total diagnostic
boundary.

Production factory, verification factory, verification worker, maintenance and
direct service construction all build the same Dependencies object. Runtime
eagerly requires the guard after declaring its observer interfaces, so direct
Runtime imports have the class available without relying on an environment
selector or service locator. Existing facade/production files require Runtime
through their established paths. No factory bypass or second raw observer was
found.

The production opened owner remains outside the guard during construction/open:
`ProductionAssignmentOrderOriginalFactory` first opens the configured owner and
still maps its fixed construction errors as before. Only the successfully
supplied observer is wrapped for command diagnostics. Direct opened-owner
`record` and `close` errors therefore remain observable under its own approved
contract; no I/O result is rewritten to synthetic success.

## Specification conformance

### Per-invocation request context

`useRequest()` resets `contextAvailable=true` at every invocation. A generic
observer receives no underlying request callback. A request-aware observer gets
one exact binding attempt; Throwable sets the invocation context unavailable and
suppresses all later records, preventing stale correlation. The next invocation
resets and retries binding. There is no sticky request set, fallback correlation,
alternate logger, bytes, persistence or global state.

### One-shot diagnostics and cleanup

`record()` returns immediately only when the current binding failed. Otherwise
it calls the supplied observer once with unchanged event and safe fields and
suppresses only its Throwable. It performs no retry. Because the exception no
longer reaches the service-wide catch, abort, stage close, stream close and lease
release retain their existing ordered single attempts. A failed diagnostic does
not suppress a later independent cleanup diagnostic.

The five invalid-PDF cases prove typed abort failure, abort Throwable, stage-close
Throwable, stream-close Throwable and simultaneous cleanup failures. Each keeps
the exact rejected result, executes every cleanup primitive once and commits one
terminal rejected attempt afterward. The audit-failure sensor separately proves
that a real unconfirmed repository audit still returns retryable
`FAILED/PERSISTENCE_FAILURE`; the guard does not hide business persistence
failure.

### Durable result, audit and delivery preservation

The four accepted-release cases cover typed release failure and release Throwable
for both request-aware and generic observers. The full accepted payload survives,
release and diagnostic each occur once, and delivery receives the exact accepted
result once after release. No abort, extra close, audit or repository commit is
introduced.

The CAS-conflict case proves fingerprint/lineage rereads, one release, one
diagnostic and one required terminal conflict audit in order. The selected
`CONFLICT/INITIAL_ALREADY_EXISTS` remains unchanged, no new accepted fact is
created, pre-existing evidence is byte-equivalent, and delivery remains absent.

The same-application binding-reset case proves that a failed binding suppresses
the first invocation without stale output, while the next request binds its new
exact ID and emits its own required diagnostic. Both terminal audits persist.

## Security, architecture and maintainability

The guard handles only the existing typed observer. It does not inspect or emit
request/PDF/file/database values, open files, touch storage, add selectors, expose
the raw observer, or catch non-diagnostic business dependencies. Comments state
the two non-obvious invariants: stale-context suppression and no retry while
allowing later independent diagnostics.

Composition at the Dependencies boundary removes duplicated try/catch risk from
every service cleanup branch and gives direct and factory callers identical
behavior. The implementation is small, final and type-compatible. Architecture
check passes all seven rules. No new rapid-pilot domain logic or runtime DDL is
introduced.

## Verification

Independent reviewer reran:

```text
php -l app/AssignmentOrderOriginal/AssignmentOrderOriginalBestEffortSafeLog.php  PASS
php -l app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php           PASS
php -l tests/InstallationProcess/assignment_order_original_safe_log_isolation_001_test.php PASS
php -l tests/Support/AssignmentOrderOriginalLogIsolationFixture.php              PASS
php tests/InstallationProcess/assignment_order_original_safe_log_isolation_001_test.php     PASS (13 cases)
```

The parent sequential run executed 28 commands at the frozen SHA, all exit 0:

- all 20 `assignment_order_original_*` scripts, including owner, production
  boundary, worker protocol/transport, remaining contract, domain/MariaDB,
  concurrency/lease, parser and schema suites;
- process-command authorization, production migration runner and production
  composition supporting checks;
- `make architecture-check` (7 rules), `make unit-test`, `make lint`, strict
  OpenSpec validation and `git diff --check f0862b0..HEAD`.

The reviewed test was unchanged from independent Gate 3 and its 13 named RED
cases now pass. Its assertions would catch the original plausible regressions:
escaped `useRequest`, stale or sticky context, duplicate record/cleanup/release,
changed selected result, missing rejection/conflict audit, skipped accepted
delivery, generic-observer binding and an overbroad catch hiding a real audit
failure.

## Findings and disposition

No blocking correctness, invariant, security, construction-path,
maintainability, architecture or regression finding remains.

**APPROVED** for scoped Gate 5 of
`ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-ISOLATION-001` v1 at reviewed commit
`3583ef866be64765017b995f80d4d1c64b8db695`.

This approval closes only the diagnostic-isolation slice. It does not by itself
approve the shared opened-owner component again, the combined original command,
protected E2E, deployment, publication or launch readiness. Those retain their
separate evidence and review requirements.
