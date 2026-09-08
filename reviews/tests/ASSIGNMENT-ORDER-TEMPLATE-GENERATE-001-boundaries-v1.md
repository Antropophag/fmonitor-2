# Independent Gate 3 review — ASSIGNMENT-ORDER-TEMPLATE-GENERATE-001 boundaries v1

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification or reviewed test)
- Reviewed commit: `9ba590ef0be5574f27babdded6afdce630a06c18`
- Test SHA-256: `510d8b471286dbbd3bd78b2357b5f630eddeb497e2c721816b95b89bcb197f7d`
- Gate 1 v0.2 SHA-256: `ec209b39d71e75f27b34b5c2c3027effc0ac472e694d2fccd336ae0ddf8b250e`
- Review date: 2026-09-07

## Blocking finding

1. **Clock placement and single-read behavior are not sensitive across the boundary matrix.** The specification orders shape, authority, and locked source/refusal checks before acquiring the one generation instant. The nine refusal cases assert no renderer/event but never assert `clockCalls === 0`, so an implementation can read the clock before invalid input, denial, absence, stale target, completion/PTO, or bad object data and pass. Cases that reach rendering/persistence also omit exact counts, allowing an extra clock read on malformed-envelope, audit rollback, capacity, or interruption paths. Add exact clock counts: zero for every pre-clock refusal and ambient-transaction rejection; one per attempted render/persistence path; cumulative two for the two-generation projection case; and one failed read for malformed clock. Preserve the already approved tracer counts.

## Passing review points

The 21 cases otherwise cover the required boundaries through real public/native seams: nine exact refusals, five closed renderer-envelope failures, greatest-event-ID projection with canonical matching corruption, a valid integer unrelated event ignored, adjusted-finish precedence, confirmed audit rollback, generated-ID overflow, killed owned connection with no bytes, ambient transaction preservation, and malformed clock before render. State/file preservation and closed no-byte failure envelopes are asserted.

The historical missing-owner RED remains valid for this verification extension. No production change is required for the currently green behaviors, but final Gate 3 and Gate 5 cannot be approved until the clock assertions make the full suite sensitive to the explicit ordering invariant.

## Gate decision

Gate 3 is **CHANGES_REQUESTED**. Add only exact clock-count assertions, recapture the boundary suite, and request focused rereview. Final template-command Gate 5 remains pending.
