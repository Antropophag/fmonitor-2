# Original command — acquisition and resource-failure contract audit

- Date: `2026-09-06`
- Reviewer: separately tasked read-only agent `/root/registry_engine_gate1`
- Reviewed HEAD/source: `bca89b4853a7106fac3194d3724f27cba38b3b2f`
- Scope: command stream/stage/inspector/finalize/lease acquisition, close and Throwable ownership only
- Verdict: **CONCRETE TOTAL-PORT AND LEASE-OWNERSHIP GAPS CONFIRMED**

No production source, test or specification was edited. No database, filesystem,
OS/native or permission probe was run. This append-only audit does not modify the
separate lifecycle/storage observer audit.

## Inherited contract

The parent specification requires one bounded acquisition owner from first
stream read through stage/stream closure. Every port call is total at the
application boundary. Stream failure maps to retryable
`FAILED/STREAM_FAILURE`; inspector and storage acquisition/finalize failure map
to retryable `FAILED/STORAGE_FAILURE`. An otherwise accepted candidate must
close stage then stream exactly once before commit; stage-close failure maps to
storage failure, stream-close failure maps to stream failure, and commit is
forbidden.

`OK|ALREADY_PRESENT_VERIFIED` finalize must carry exactly one lease with status
`OK` and content matching the requested digest/size/derived identity. Other
outcomes must carry no lease. Once acquired, the lease remains held through
close, commit, CAS rereads and unknown-outcome resolution, then receives exactly
one release attempt on every terminal path. Release failure preserves the
already selected result.

## Source findings

### ACQ-01 — generic port Throwables receive the wrong Result family

Runtime's broad catch at line 92 maps almost every unexpected Throwable to
`FAILED/PERSISTENCE_FAILURE`. It selects storage/stream reasons only by checking
whether the injected faults object is the concrete
`AssignmentOrderOriginalWorkerFaults` with target `STAGE_CLOSE` or
`STREAM_CLOSE`. Public port behavior therefore depends on one verification
implementation class instead of the operation that failed.

Concrete wrong mappings:

- `storage->beginStage()` Throwable → persistence failure; required storage
  failure;
- `stream->read()` Throwable → persistence failure; required stream failure;
- `stage->write()` Throwable → persistence failure; required storage failure;
- `stage->completedBytesForInspection()` Throwable → persistence failure;
  required storage failure;
- `pdfInspector->inspect()` Throwable → persistence failure; required inspector
  failure mapping, `FAILED/STORAGE_FAILURE`;
- `stage->finalize()` Throwable → persistence failure; required storage failure;
- accepted-candidate `stage->close()` Throwable from any ordinary stage →
  persistence failure unless the unrelated faults object has the concrete
  worker target; required storage failure;
- accepted-candidate `stream->close()` Throwable from any ordinary stream →
  persistence failure under the same condition; required stream failure.

The typed `StreamReadStatus::FAILED`, non-OK stage-write result and typed
`INSPECTOR_FAILED` branches are mapped correctly. Their presence does not make
Throwable from the same public port total.

### ACQ-02 — accepted-candidate close can be retried and later cleanup skipped

Runtime line 76 closes stage and then stream after finalize. If stage close
throws, control enters the broad catch, which calls stage abort, stage close and
stream close again. The stage close is attempted twice. If abort or the repeated
stage close throws, its local catch suppresses that failure and proceeds, but the
classification remains unrelated to the actual first failure.

If stream close is the first failure, the broad catch similarly aborts and
closes the already finalized/closed stage and calls stream close a second time.
This violates exact-once close ownership and can transform a well-classified
accepted-candidate close failure into persistence failure. It also relies on
adapters being idempotent even though the application contract assigns the
single attempt to its bounded acquisition owner.

### ACQ-03 — acquired leases leak on every outer-catch path

The local `$lease` is initialized at line 63 and assigned during the condition at
line 76. The broad catch at line 92 never reads or releases it. Consequently any
Throwable after assignment can return while the digest exclusion token remains
held:

- `lease->content()` during validation or its second fetch;
- accepted stage close or stream close;
- `AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT` lifecycle observer;
- correction lineage/fingerprint/current-evidence reads;
- `commitAccepted()`;
- fresh terminal lookup after `OUTCOME_UNKNOWN`;
- conflict fingerprint or fresh-lineage rereads;
- commit result/result object access before the normal release line.

The normal `finishLeased` and post-commit paths do release, but Throwable bypasses
them. A leaked lease can block maintenance indefinitely and violates release-
exactly-once ownership. The outer catch also attempts abort/close cleanup after
finalization without distinguishing the now lease-owned finalized content.

### ACQ-04 — finalize outcome/lease/content invariants are not validated

Runtime accepts `OK|ALREADY_PRESENT_VERIFIED` when `lease()` and `content()` are
truthy. It does not require:

- `lease->status() === OK`;
- one stable lease object from a single `lease()` read;
- one stable content object from a single `content()` read;
- content `sha256()` equal the acquired PDF digest;
- content `byteSize()` equal received bytes;
- content `opaqueIdentity()` equal
  `content-sha256-<pdfSha256>` and satisfy its exact grammar.

It calls `content()` in the condition and again afterward, allowing a stateful
port to return two different objects or throw on the second call. Conversely, a
non-success finalize outcome carrying a lease, a success carrying a non-OK lease,
or a success with malformed content enters ordinary cleanup without releasing
the supplied lease. Invalid content may reach `AssignmentOrderOriginalAcceptedCommit`
and be discovered only by repository validation, after stage/stream close and
with a lease already held.

The application boundary must classify every malformed combination as
`FAILED/STORAGE_FAILURE`, attempt release exactly once if any lease was returned,
perform bounded stage/stream cleanup, and forbid commit.

### ACQ-05 — stream-result progress and payload invariants are unchecked

The stream loop trusts the typed status but does not validate status/payload
pairing. `BYTES` with an empty string makes no progress and can repeat forever,
violating bounded acquisition. `EOF` or `FAILED` with nonempty bytes silently
discards those bytes, allowing different adapters to disagree on received-byte
count and digest.

The executable amendment should pin the inherited total-port rule: `BYTES`
contains `1..65536` bytes, while `EOF|FAILED` contains an empty payload. Any
malformed pair is `FAILED/STREAM_FAILURE`, followed by the same exact-once
cleanup. This is a technical encoding requirement for the existing byte-stream
contract, not a new product outcome.

### ACQ-06 — several post-lease repository Throwables bypass selected outcome

Typed lookup `UNAVAILABLE` and commit statuses are handled on the normal path,
but direct Throwable from post-lease repository calls falls into the leak path
above. The inherited total-port mappings require:

- commit Throwable/definite failure → retryable `FAILED/PERSISTENCE_FAILURE`,
  release phase `rolled_back`;
- conflict reread Throwable/unavailable → persistence failure after required
  rereads and one release;
- unknown-outcome fresh lookup Throwable/unavailable →
  `FAILED/PERSISTENCE_OUTCOME_UNKNOWN`, release phase `unknown_unavailable`;
- all selected conflict/replay/persistence results survive release failure and
  retain required attempt-audit/delivery ordering.

The broad catch currently loses this phase information, skips release and may
perform stage cleanup after the stage has already finalized.

## Bounded correction matrix

| Boundary | Injected outcome | Exact Result | Required ownership/effects |
| --- | --- | --- | --- |
| beginStage | Throwable | `FAILED/STORAGE_FAILURE`, retryable | stream close once; no stage/event/finalize/ID/commit |
| stream read | typed FAILED, Throwable, malformed status/bytes | `FAILED/STREAM_FAILURE`, retryable | abort if stage exists, stage close, stream close; each once |
| stage write | typed FAILED or Throwable | `FAILED/STORAGE_FAILURE`, retryable | same ordered cleanup; no inspector/finalize |
| completed bytes | Throwable or bytes inconsistent with acquired stream | `FAILED/STORAGE_FAILURE`, retryable | ordered cleanup; inspector not called |
| inspector | `INSPECTOR_FAILED` or Throwable | `FAILED/STORAGE_FAILURE`, retryable | abort, stage close, stream close; no finalize |
| finalize | FAILED/LOCKED, Throwable | `FAILED/STORAGE_FAILURE`, retryable | release any improperly supplied lease once, then bounded cleanup; no commit |
| finalize success shape | null/non-OK/unstable lease; null/unstable/wrong content identity/hash/size | `FAILED/STORAGE_FAILURE`, retryable | capture once, release once when present, close resources once, no commit |
| accepted stage close | Throwable | `FAILED/STORAGE_FAILURE`, retryable | no commit; stream close once; release lease once `rolled_back`; no repeated stage close |
| accepted stream close | Throwable | `FAILED/STREAM_FAILURE`, retryable | no commit; release lease once `rolled_back`; no repeated closes |
| post-finalize lifecycle | Throwable | `FAILED/PERSISTENCE_FAILURE`, retryable | lease release once `rolled_back`; no commit; no repeat stage/stream cleanup |
| commit | ROLLED_BACK or Throwable | `FAILED/PERSISTENCE_FAILURE`, retryable | release once `rolled_back` |
| commit OUTCOME_UNKNOWN lookup | FOUND / NOT_FOUND / UNAVAILABLE-or-Throwable | stored accepted / persistence failure / outcome unknown | release once with exact `unknown_*` phase |
| commit CONFLICT rereads | replay/conflict/unavailable-or-Throwable | selected exact replay/conflict/persistence result | retain lease through required rereads; release once; required audit/delivery order |
| lease release | OK / FAILED / Throwable | preserve already selected Result | one release attempt; exact best-effort diagnostic; never retry |

Storage and lifecycle event completeness belongs to the separate observer audit,
but the correction test should use those approved events to prove causal ordering
rather than concrete `WorkerFaults` type inspection.

## Required gated delivery

Before implementation, amend the executable contract only where malformed
stream-payload pairing, observer Throwable mapping, or finalize getter stability
is not already literal. Then demonstrate public-seam RED with generic typed spies
that do not subclass or expose `AssignmentOrderOriginalWorkerFaults`. Gate 3
must verify every row's Result family, exact call counts, no post-failure calls,
lease phase/release count and unchanged persistence/storage facts.

Minimal GREEN should introduce one bounded acquisition/resource owner or
equivalent operation-aware error classification. It must not infer Result reason
from a concrete fault-injector class, retry an application-owned close, or leave
lease release to the generic outer catch. A fresh independent scoped Gate 5 and
then cumulative command review are required.

## Exact reviewed hashes

```text
a7767a8bb6ae53baa87bdcc04f598f8b399a0411b0faff80e5ddee27c0665647  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
0a9c81f0cd173ae1e75262bcae5e3ae88b6d662476eb284785564b5008a4456c  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
d7113dcdf79915751f266f8026c5415fab8ec3a9878cd8c51ce43ddb1c7ab6a8  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

This audit is not Gate 1, Gate 3 or Gate 5 and does not supersede the dynamic-
ports approval or prior observer/shape findings.
