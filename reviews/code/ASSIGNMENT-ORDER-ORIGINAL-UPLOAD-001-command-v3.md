# Gate 5 code review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 command v3

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Frozen reviewed SHA: `3583ef866be64765017b995f80d4d1c64b8db695`
- Current executable specification SHA-256: `d65470e2e1da510aa1ebe6d9cce6549b8fffcc6bf66035716623eb0cad61f890`
- Scoped opened-owner Gate 5: `APPROVED`, review SHA-256 `0262cad1d0424e0c5b09d1030bcadf6bf90b253025eaedc3bca6b63593cac673`
- Scoped diagnostic-isolation Gate 5: `APPROVED`, review SHA-256 `1d7d8d4685125136cb7fa92d2ebc65bdb215993dcf0ee6af6c9872554741e74f`
- Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the implementation nor its tests/specifications.
No production source, test or specification was edited by this review.

## Determination

The opened-file owner and diagnostic-isolation corrections conform within their
scoped approvals, and the prior command-v2 parser, persistence, composition,
barrier, schema and production-construction findings remain corrected. The final
28-command GREEN evidence is internally consistent and its archive hash matches.

The cumulative command nevertheless violates three explicit parts of the active
normative contract. The existing matrix does not exercise these dynamic calls or
public API alternatives, so scoped approvals and aggregate GREEN cannot support
a combined approval.

## Blocking findings

### G5-CMD3-01 — post-stream fingerprint `UNAVAILABLE` continues toward commit

The active contract places accepted-operation fingerprint lookup at execution
step 10 and declares repository lookup `UNAVAILABLE` a retryable
`FAILED/PERSISTENCE_FAILURE`. This must hold for the actual fingerprint lookup,
not only for an earlier connectivity probe.

At `AssignmentOrderOriginalRuntime.php:68`, the service calls
`findAcceptedFingerprint('')` before composition and handles `UNAVAILABLE`.
After consuming/staging/inspecting the PDF, line 74 calls
`findAcceptedFingerprint($fp)` with the real fingerprint but branches only on a
`FOUND` result containing a value. `UNAVAILABLE` falls through exactly like
`NOT_FOUND`: lifecycle continues, IDs are requested, private content is
finalized and `commitAccepted` is attempted.

The early empty-fingerprint call cannot establish availability of the later
dynamic call. A repository can succeed at step 5/availability and become
unavailable at step 10; the contract expressly treats each port call as total.
This branch can therefore create private/finalization work and a commit attempt
after the required fail-closed result was already known.

Required correction cycle:

1. Add a public application-seam test whose empty-fingerprint probe returns
   `NOT_FOUND`, whose real `$fp` lookup returns `UNAVAILABLE`, and whose other
   dependencies are valid.
2. Expect exact `FAILED/PERSISTENCE_FAILURE`, retryable, with one stage abort,
   stage close and stream close; no lifecycle fingerprint-miss event, ID call,
   finalize, lease, commit, domain event or accepted evidence.
3. Demonstrate RED, obtain independent Gate 3, then add the missing explicit
   `UNAVAILABLE` branch without changing approved expectations.

### G5-CMD3-02 — public ID contract and bounded collision protocol are absent

The normative declarations define
`AssignmentOrderOriginalIdStatus::GENERATED|COLLISION|EXHAUSTED|UNAVAILABLE` and
`AssignmentOrderOriginalIdResult(status, ?string id)`. They require
`UNAVAILABLE|EXHAUSTED` to return retryable `FAILED/PERSISTENCE_FAILURE` and
exactly eight consecutive `COLLISION` outcomes to exhaust the bounded retry.

Reviewed production instead declares only `GENERATED|FAILED` at Runtime line 15
and names the DTO field `identity` at line 31. Runtime line 75 calls each source
once, never checks status, never validates a non-null generated value and reads
`->identity` directly. Initial mode allocates root and revision once; correction
allocates revision once. No eight-attempt collision loop exists.

Direct public-API reproduction on the frozen bytes:

```text
AssignmentOrderOriginalIdStatus::cases() => generated,failed
```

Thus approved callers cannot even construct `COLLISION`, `EXHAUSTED` or
`UNAVAILABLE`. A `FAILED/null` current result becomes an empty string through the
later cast and proceeds through private finalize toward repository failure,
rather than failing at the ID port boundary. Collision behavior is deferred to
unrelated DB conflict handling and is neither bounded nor equivalent to the
specified source protocol.

Required correction cycle:

1. Restore the exact four-case enum and exact public DTO field `id`; update every
   production and verification ID source coherently.
2. Add public-seam cases for root and revision `UNAVAILABLE`, `EXHAUSTED`,
   malformed `GENERATED/null`, collision followed by generated, and eight
   consecutive collisions. Cover initial root/revision separately and correction
   revision allocation.
3. Fix exact call counts and require failure before finalize/commit. A successful
   candidate after fewer than eight collisions must use that generated identity;
   the eighth consecutive collision must return retryable persistence failure.
4. Demonstrate RED and obtain independent Gate 3 before implementation.

### G5-CMD3-03 — noncanonical clock values can be persisted as `uploadedAt`

The active contract states that `nowUtc()` must return canonical UTC seconds,
`YYYY-MM-DDTHH:MM:SSZ`, and that invalid or unavailable clock output maps to
retryable `FAILED/PERSISTENCE_FAILURE`. Moscow date conversion belongs to the
application.

Runtime line 70 assigns the raw clock string to `$at` and passes it directly to
`new DateTimeImmutable($at)` only to compute Moscow `serverToday`. There is no
exact grammar or round-trip validation before `$at` is later written to accepted
and attempt DTOs. PHP accepts multiple forbidden or invalid-looking inputs:

```text
2026-09-02 09:15:30  => 2026-09-02T09:15:30+00:00
2026-02-31T09:15:30Z => 2026-03-03T09:15:30+00:00
```

In both cases the application retains the original noncanonical `$at` for
`uploadedAt`/`attemptedAt`; parsing success prevents the outer failure mapping.
Timezone-default behavior can also influence the first form, contrary to the
explicit UTC contract.

Required correction cycle:

1. Add public-seam clock cases for missing `Z`, space separator, fractional
   seconds, explicit offset, invalid calendar and clock Throwable.
2. Each must return exact retryable `FAILED/PERSISTENCE_FAILURE`, close the
   stream once and perform no stage/storage/ID/fingerprint/finalize/commit or
   domain mutation after clock rejection.
3. Prove one canonical boundary value remains accepted and Moscow date
   conversion/future-date behavior is unchanged.
4. Demonstrate RED and obtain independent Gate 3 before adding strict grammar
   plus calendar round-trip validation.

## Re-evaluation of prior findings

No regression was found in the previously corrected command-v2 areas:

- immutable PDF bytes are staged, flushed/verified and atomically published with
  a retained lease and restart coverage;
- the independently reviewed incremental PDF parser handles classic/stream xref
  identity, `/Prev`, object streams, dictionary/graph and active-content bounds;
- initial lineage is scoped to the assignment order and correction composition/
  lineage precedence remains before stream access;
- terminal lookup, authorization/composition unavailability, commit outcome,
  attempt audit and CAS conflict paths remain typed for the branches exercised;
- lifecycle barriers originate from real application stages, including after
  private finalize while the lease is held;
- schema v2, capability publication, evidence reader, maintenance/orphan and
  production storage/factory boundaries retain their reviewed implementations;
- production safe-log acquisition now uses the approved opaque retained owner,
  and the dependency wrapper isolates request binding and diagnostic recording
  Throwable without replacing selected results or required cleanup/audit/delivery
  sequencing.

These conclusions do not cure the three untested branches above.

## Verification evidence assessed

The private aggregate evidence exists and matches the supplied identity:

```text
d3d33705d66572b5b490134b44f103f06d2f00e0ee82d66f12cbba5bc3b843a5  /Users/antropophag/.local/state/fmonitor2-verification/original-combined-green-475ujgo9/evidence.json
```

It records 28 successful commands on frozen SHA
`3583ef866be64765017b995f80d4d1c64b8db695`, including all 20 current
assignment-order-original scripts, supporting authorization/migration/composition
checks, architecture, unit, lint, strict OpenSpec and diff hygiene. The new
isolation suite has 13 GREEN cases and unchanged approved test bytes.

The matrix has no case where the real post-stream fingerprint lookup alone is
unavailable, no four-status/eight-collision ID source, and no noncanonical but
PHP-parseable clock. Its success therefore does not contradict these findings.

## Required closure before command v4 review

Deliver each finding through the mandatory spec/RED/independent-Gate3/minimal-
GREEN/independent-Gate5 process. Because G5-CMD3-02 reveals a literal active-spec
versus public-API mismatch, reconcile executable test declarations and all
production/worker sources at one exact hash; do not silently preserve the
two-case legacy enum as an alternate API.

Then rerun the complete 28-command inventory plus the new focused regressions,
produce fresh sorted source/test hash manifests, run `make architecture-check`,
`make unit-test`, `make lint`, strict OpenSpec validation and cumulative
`git diff --check`, and request a fresh independent combined Gate 5 against one
frozen SHA. Full `make verify` with literal `VERIFY_OK` remains a later same-SHA
integration requirement and cannot replace code review.

## Exact reviewed identities

```text
8ea2b82300c157f2ca6c69b9b4eef5ab773d8e612d22f6725d4d88aa705c013f  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
0a9c81f0cd173ae1e75262bcae5e3ae88b6d662476eb284785564b5008a4456c  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
ce1e072b08705f6347de23e2867ee53c8e774c528b3abf5f1c5b121d9a21bc3c  app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php
1a59ecc5ec45470ff76a6c043e29c67bdb89b641b298851540a050b46d6fe394  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
fc24345234606a10c9175c340b7dc29b752131e0170447d4fd38f8f9dd150d75  app/AssignmentOrderOriginal/AssignmentOrderOriginalBestEffortSafeLog.php
d65470e2e1da510aa1ebe6d9cce6549b8fffcc6bf66035716623eb0cad61f890  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
482b5153e84e4dfe51ff75b526458fdd974e503a2d6f4b31db880a1f15c4ba54  specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001.md
1d7d8d4685125136cb7fa92d2ebc65bdb215993dcf0ee6af6c9872554741e74f  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-ISOLATION-001-v1.md
0262cad1d0424e0c5b09d1030bcadf6bf90b253025eaedc3bca6b63593cac673  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001-v1.md
2fc849d17f5c0afecbb4901148c9f06449d87ec218697d62b8e75aecb207b006  sorted 25-file app/AssignmentOrderOriginal PHP SHA-256 manifest at reviewed SHA
cc04ecba66133eeed52b5e0647799de6a8171e134410bc0c00c293f7e52b5f52  sorted 20-file assignment_order_original test SHA-256 manifest
```

This review omits its own circular hash. It does not approve deployment,
protected E2E, HTTP, selection application, opening, full verification or launch
readiness.
