# Independent Gate 3 review — ASSIGNMENT-ORDER-TEMPLATE-GENERATE-001 tracer v1

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification or reviewed tests)
- Reviewed commit: `19df05cba6377f3c46404326a8ca5b3f35b9e724`
- Test SHA-256: `67d9234e1ed1d7ac9d41e0c37235911b7e42d46b727eaaa12d04c88d3ca67d7e`
- Fixture SHA-256: `6aee9dfb6796e40e06107a8ef55773400091673aa9105fadcc9f7c2aca7effb3`
- Gate 1 v0.2 SHA-256: `ec209b39d71e75f27b34b5c2c3027effc0ac472e694d2fccd336ae0ddf8b250e`
- RED archive: `/Users/antropophag/.local/state/fmonitor2-verification/template-generation-red-ahrzq7kj`
- RED manifest SHA-256: `6ae076965d67152ea90c1f8ea83675cd757f023bb7ff3e186974ffaea00a243e`
- RED log SHA-256: `4810482d5033afe37abe7a1e5acaab7a577284e0c463f51f648bc1ba60d7d6c8`
- Review date: 2026-09-06

## Blocking finding

1. **The required one clock read per generation attempt is not asserted.** `TemplateGenerationFixture::$clockCalls` counts verification-clock reads, but the test never checks it. Because the clock always returns the same instant for each configured attempt, an implementation may call it twice or more and still satisfy every Moscow date and event-time assertion. Add exact cumulative counts after the first success, next-day repeat, and renderer-failure attempt. The failure attempt should still consume exactly its one instant before rendering while leaving the last successful projection unchanged.

## Passing review points

Both constructor cases reach the missing owner only after real native selection and real TCPDF generation with semantic PDF markers. Downstream assertions cover the closed response, two Moscow dates, canonical metadata-only events, latest-date projection, renderer-failure preservation, direct-original compatibility, no template files, and no database changes outside `fm2_process_events`. The renderer is real rather than a fake PDF header. Production date is bounded around the real Moscow date.

The exact clean RED reports `SETUP_OK`, the intended missing-owner assertion, and `CLEANUP_OK` for both verification and production constructors. Resources are owned by the existing selected-original fixture.

## Gate decision

Gate 3 is **CHANGES_REQUESTED**. Add only the exact verification-clock count assertions, recapture the same intended RED, and request a focused rereview. Other rejection, projection-integrity, rollback, event-capacity, and visual-QA cases remain later tranches and are not blockers for this correction.
