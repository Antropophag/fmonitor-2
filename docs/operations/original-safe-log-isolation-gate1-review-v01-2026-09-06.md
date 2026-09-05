# ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-ISOLATION-001 v0.1 — independent Gate 1 review

Дата: 2026-09-06.  
Reviewer task: `/root/selection_v04_readiness`.  
Reviewed HEAD: `30b98d5e46c8465691fd7f3a56a9e0076ae308b4`.  
Verdict: **APPROVED**.

Reviewer не автор reviewed specification. Это независимый Gate 1 review только
safe-log isolation behavior. Parent/OpenSpec linkage, tests, implementation,
shared opened-file owner Gate 5 и combined original-command Gate 5 остаются
отдельными gates.

## Exact reviewed hashes

- `specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-ISOLATION-001.md`:
  `ef245ec06ad9ed0a7d9c365386077a22be5547eb625ba276ba4f603d7b310942`.
- Parent `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md`:
  `d23b9cd924be6ce9deb905a0c742e7b0449eb8755fdb5af8d9caac094934fbf3`.
- Source audit
  `docs/operations/original-safe-log-best-effort-audit-2026-09-06.md`:
  `f2af8fc92141a10bd8f0cc82e0c5bc6dbcc0b4949937e55c48970846a80cd675`.
- Current implementation evidence
  `app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php`:
  `4c893c34377546ded04fc094bf5cbfd8dd5647655416ec25a8e6e28c65ef114d`.

## Review

### Public seam and construction — PASS

The specification retains the existing public seam
`AssignmentOrderOriginalApplication::submitAssignmentOrderOriginal(Command)` and
the existing dependency property type. One guarding adapter is composed around
the supplied diagnostic observer when public Dependencies are constructed, so
production factory, verification factory and direct Service construction receive
the same total boundary. The guard owns no file, DB, PDF bytes or business state
and exposes no new selector or raw-observer API.

The construction rule is compatible with both existing observer shapes:

- a request-aware observer receives one `useRequest(exact requestId)` attempt per
  invocation;
- a generic observer receives no new underlying callback;
- a failed request binding disables subsequent underlying `record` calls only
  for that invocation;
- the next invocation resets the guard state and attempts binding again.

This prevents stale correlation without inventing fallback identity, logger or
persisted state. The request-binding worked example starts with an old request,
requires zero record calls after binding failure, then proves recovery on a fresh
request using the same application.

### Result and cleanup preservation — PASS

The matrix covers each source-audit failure site through the public command:

- abort typed FAILED and abort Throwable;
- stage-close Throwable;
- stream-close Throwable;
- content-lease release typed FAILED and Throwable;
- request-context binding Throwable.

For rejected validation, every applicable cleanup primitive is attempted once,
the selected rejection remains unchanged, and the required terminal attempt
transaction still follows cleanup. A throwing logger cannot re-enter the broad
command catch, repeat abort/close/release, or skip the audit.

For a committed acceptance, lease release remains attempt-always and exactly
once. Logging failure preserves the full durable accepted payload and delivery
runs once after the release attempt. No extra abort, close or repository commit
is permitted.

For a non-replay CAS conflict, release/logging failure preserves the exact
conflict, then performs the required terminal conflict attempt commit. If that
repository audit commit itself is not confirmed, the inherited
`PERSISTENCE_FAILURE` still applies; diagnostic isolation does not hide a real
repository failure.

### Diagnostic call semantics — PASS

With a valid request context, each required diagnostic performs one underlying
`record(event, safeFields)` call with unchanged arguments. Throwable is
suppressed without retry, alternate logger or synthetic successful record.
Separate cleanup failures retain separate one-shot diagnostic attempts. This
distinguishes two legitimate events from retrying one failed write.

The fixed examples pin exact event names and sole `phase` fields for abort,
stage close, stream close and committed lease release. They require the throwing
observer to emit no bytes. No exception, path, filename, request payload or PDF
content is added.

### Scope and inherited contracts — PASS

The slice changes only the application diagnostic boundary. It explicitly leaves
authorization, PDF/storage inspection, commit/correction/replay semantics,
opened-file owner/configuration, safe-log JSON schema and HTTP workflow intact.
The direct opened owner continues throwing its approved fixed errors; application
suppression cannot make I/O falsely report success.

The spec forbids a catch-all that returns a preselected success. Only diagnostic
Throwable is isolated, so underlying storage/repository failures keep their
existing typed outcomes. This is a technical correction of approved behavior and
introduces no product-policy decision.

### Acceptance observability and RED sensitivity — PASS

All required results and side effects are observable using the real public
verification factory/application with pure in-memory ports. The worked examples
independently pin full accepted/rejected/conflict results, cleanup/release/log
call counts, attempt/accepted commit counts, delivery count, evidence absence and
empty log bytes.

On current implementation these tests are sensitive to the actual defect:
unguarded `useRequest` escapes before the service try; unguarded cleanup logging
re-enters the broad catch and repeats cleanup/replaces the selected result;
unguarded release logging replaces durable/provisional results and can skip
delivery or conflict audit. RED therefore demonstrates missing behavior rather
than fixture setup.

## Gate disposition

`ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-ISOLATION-001` v0.1 satisfies Gate 1 at the
exact hash above and is **APPROVED** to proceed to a demonstrated public-seam RED.
Gate 2 evidence must then receive independent Gate 3 before minimal GREEN.
Independent Gate 5 must review every application construction path,
per-invocation reset, one-shot diagnostics, cleanup/audit/delivery preservation
and relevant regressions. This approval does not approve pending linkage text or
any later artifact by implication.

## Linkage addendum — commit f63a9f5

После behavioral review отдельно проверена linkage-only revision на commit
`f63a9f57d8cdde721fabaa8ceacaaabe8ec0eefa`. Reviewed isolation specification
не изменилась и сохраняет SHA256
`ef245ec06ad9ed0a7d9c365386077a22be5547eb625ba276ba4f603d7b310942`.

Exact linkage hashes:

- parent `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v61:
  `d65470e2e1da510aa1ebe6d9cce6549b8fffcc6bf66035716623eb0cad61f890`;
- OpenSpec design:
  `3e57a80322b81714466ab814092b761fd4b4c2f1aeb60f2bb49cf745854a7108`;
- OpenSpec proposal:
  `b4bd32a98b3d515da23c1d2d9575652872c21df9d4342b43ff0da13bb1f5c9c4`;
- OpenSpec delta spec:
  `75be177073551ff6cffd485dcb92c658dd70a29194549e763829a7f37c3988ec`;
- OpenSpec tasks:
  `c9a305f6283f43d4606e67678d144b44c00dc31977e1b4975c7753191ab01688`.

Parent v61 correctly makes SAFE-LOG-ISOLATION-001 normative for the application
diagnostic boundary and retains direct opened-owner failures, combined
G5-SAFELOG-2 and later review as separate obligations. OpenSpec proposal/design
repeat the same per-invocation binding, result/cleanup/audit/delivery preservation
and owner-scope boundaries. Delta scenarios are traceable to sections 2–4 of the
reviewed executable spec. Tasks create separate Gate1, public RED/Gate3 and
GREEN/Gate5 items without marking isolation implementation complete or treating
the scoped owner Gate5 as combined approval.

The linkage text still says independent Gate1 is pending because it predates this
review record; that is accurate historical gate ordering and is superseded only
by this explicit exact-hash verdict. No behavioral contradiction or scope
expansion was introduced. Linkage check: **PASS**.
