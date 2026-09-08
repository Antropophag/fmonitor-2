# Independent Gate 3 review — SELECTION-CANONICAL-REGISTRATION-001 v1

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification or reviewed test)
- Reviewed commit: `fbbb41e54aedf240211ba56041269e8d2264cf63`
- Test SHA-256: `b1cfba8f45f7c496d8abc29d36d1387e7b1b8a825182f329e0eae5be3adbbaf6`
- Gate 1 spec SHA-256: `727be0b6302f6b33458201025263480b64cf5ddaf7c47f3f137218dbe58d9add`
- RED archive: `/Users/antropophag/.local/state/fmonitor2-verification/selection-canonical-red-bs93mb9p`
- RED manifest SHA-256: `6831dd497669040032fa251dbf1486c1c29da53d6c14604f944213652f1ef097`
- RED log SHA-256: `e844ccdbdf8bad893f0d321d273ca71584f012e44f84fab4e20928d24dffaa80`
- Review date: 2026-09-06

## Findings

No blocking test finding was found.

The test invokes the actual migration CLI through an isolated complete environment, including the empty prefix value, and builds the explicit approved v1–v13 catalogue for predecessor setup. It covers fresh install, v13 upgrade, registry-only recovery, registry conflict, selection conflict, and maximum prefix. Every successful axis also executes a full repeat.

Expected exit codes and JSON objects are literal: fresh applies 1–15, v13 and prefix-25 upgrades apply 14/15, registry-only applies 15, and repeat applies none. Conflicts must stop at and report exact version 14 or 15. Public registry completion and selection readiness, empty selection/domain facts, predecessor-row preservation, and repeat row/receipt identity make the test sensitive to wrong order, missing registration, lazy repair, or non-idempotent engines.

The selection-conflict fixture damages only the selection audit table after establishing a complete registry, isolating the v15 failure. The registry conflict prevents selection invocation. Engine-level compatibility matrices are reused rather than duplicated. Databases and CLI processes are synthetic, bounded, and cleaned for every axis.

## Reproduced RED

The exact clean run exited `1`. All six axes reached `SETUP_OK`, failed because the current public runner still advertises v13 and lacks registrations 14/15, and completed `CLEANUP_OK`. The earlier environment construction that omitted an empty prefix was corrected before this capture and is not product RED.

## Gate decision

Gate 3 is **APPROVED** at the exact commit and hashes above. Gate 4 may add only the v14 registry and v15 selection imports/ordered entries. Scoped existing consumer expectations that advertise the canonical final version must be updated and verified before overall Green; this focused approval does not claim those updates already exist.
