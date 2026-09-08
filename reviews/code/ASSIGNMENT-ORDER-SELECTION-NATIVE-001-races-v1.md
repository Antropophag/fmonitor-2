# Independent Gate 5 review — ASSIGNMENT-ORDER-SELECTION-NATIVE-001 races/interruption v1

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification, tests, or implementation)
- Reviewed commit: `e5b7c048e6771010fa777bd9144d66bc6343fca4`
- Test source commit: `50e44bd1037beb76a8c7265e1db32ef19a8eb53f`
- Approved Gate 3 record SHA-256: `51bf84f7b48791f8f6f106d4afebca01f0eca3fa9648ad5fe24868038b3b81cd`
- Inherited production commit: `c93c27a3480492c16c19edcfb2068d7d8e2276cd`
- Prior native-41 archive: `/Users/antropophag/.local/state/fmonitor2-verification/selection-native-persistence-green-mqw2elh6`
- New six-case archive: `/Users/antropophag/.local/state/fmonitor2-verification/selection-native-races-evidence-kpmr7mpc`
- New archive manifest SHA-256: `82a752e5c2b60ae87954ad679dd52e8719c0de8a7f4818802cd2519af78422e4`
- Review date: 2026-09-06

## Findings

No blocking finding was found in this verification-only Gate 5 extension. `git diff --exit-code` from the inherited production commit proves application source, architecture policy, and shared native/schema fixtures unchanged; Gate 4 is correctly a no-op.

The four concurrency cases demonstrate real same-case row-lock serialization, same request-key collision across cases, distinct-request/global allocation across cases, and a committed result lost before worker delivery followed by fresh-process silent replay. The two interruption cases kill a dedicated connection after real staging but before commit under mysqli exception and boolean modes, require `outcomeUnknown`, prove fresh read-only absence and no partial facts, and require a later explicit retry to allocate ID 82.

The response-loss case is evidence for delivery loss after an acknowledged public return inside the worker; it is not treated as native commit-acknowledgement loss. Other unknown-commit nuances remain outside this approval.

## Verification and decision

The unchanged-source archive supplies 41 prior native cases plus architecture/lint/diff checks. The exact clean new archive adds four concurrency and two interruption cases, all passing. Together they provide 47 native cases without an unnecessary repeat run.

Gate 5 is **APPROVED** for these six race/interruption cases only. Prefix/readiness, locked accepted-root behavior, remaining recovery nuances, and final full-binding audit remain pending and are not approved here.
