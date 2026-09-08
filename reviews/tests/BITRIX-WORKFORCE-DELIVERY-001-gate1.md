# Independent Gate 1 — BITRIX-WORKFORCE-DELIVERY-001

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `/root/original_gate1_v3`, separately tasked agent; not the author of the specification, tests or proposed implementation.
- Date: 2026-09-07
- Reviewed HEAD: `f1f0b824cba97ff7b95582b98e5284295ce5d479`.
- Specification: version 0.1, SHA-256 `fbcde7ac6691f5678286cd02fb6ca6ce6a9bbac604df3b4abc03b4974e8c576b`.

## Material findings

1. **The normative deadline needs an enforceable native capability boundary.** Section 3 requires native Curl and bounds every attempt by the remaining monotonic deadline, but does not specify which resolver builds can satisfy that guarantee or how unsupported builds fail. The design recognizes this risk without resolving the executable outcome. Official libcurl documentation states that synchronous name resolution cannot time out with NOSIGNAL enabled; asynchronous c-ares/threaded resolution is its documented alternative. Specify a supported bounded resolver capability checked at fetch time and a fixed `configuration_unavailable`, zero-attempt result when absent, or another explicitly bounded native mechanism. Keep the factory free of native activity. State millisecond handling for positive remaining time and deadline outcome after a timed native return so rounding cannot create a zero/unbounded timeout or ordinary transport failure after budget expiry. This is a transport prerequisite, not a relaxation of TLS or authorization. Sources: [libcurl NOSIGNAL](https://curl.se/libcurl/c/CURLOPT_NOSIGNAL.html), [libcurl TIMEOUT_MS](https://curl.se/libcurl/c/CURLOPT_TIMEOUT_MS.html).

2. **Close the overfull-page reason overlap.** Section 4 first requires a list of at most 50 rows, with invalid shapes mapped to `schema_invalid`, then maps an overfull page relative to `min(50,total-start)` to `pagination_invalid`. A 51-row otherwise-valid page meets both descriptions. Assign an explicit reason/validation precedence so independently authored tests have one expected result. The same clarification should preserve the already explicit total-above-20000 `limit_exceeded` case.

3. **Make endpoint construction exact for both accepted origin spellings.** Origin permits an absent path or `/`, while the request is described as origin plus `/rest/...`. State that these inputs both yield exactly one separator before `rest` (or narrow the accepted form). Otherwise the literal request path is ambiguous for an accepted configuration.

The worked example should also spell UF_XING as a decimal **string**, e.g. `"1"`, if that is intended: `UF_XING=N` currently reads as a numeric fixture value while its closed row type is string/null. This is a small example correction and does not call for field normalization.

## Scope otherwise coherent

The explicit scalar sort/order, select list, department filter and 50-row offsets are supported by the official user.get contract. Documentation also says unavailable/nonexistent selected fields can be omitted, making the specified fail-closed missing-field result a deliberate delivery policy rather than a claim that live payload readiness is known. The selected built-in fields appear in the documented user_basic field list. Sources: [user.get](https://apidocs.bitrix24.ru/api-reference/user/user-get.html), [User Scope](https://apidocs.bitrix24.ru/api-reference/user/user-scope.html).

Factory-only scalar validation, fetch-time credential rotation, strict secret metadata, TLS peer/hostname checks, no redirects/proxies/cookies/netrc, body caps and safe structured results establish a narrow readonly boundary. There is no DB, publication, normalization, cron wiring or grant. Batch access deliberately returns raw selected data only to trusted consumers; ordinary JSON excludes records and private configuration. Null dates and empty full delivery remain input facts for later policy, not eligibility/publication decisions.

Pagination binds total, numeric ID order, offsets and final unique count; later failure returns no partial batch and retains only completed-page/attempt counters. Retry scope is explicit and does not turn authorization, TLS, schema or API failures into retries. Duplicate decoded JSON keys, including equivalent escapes, must be rejected before information is lost through decoding. Native synthetic TLS fixtures, bounded cleanup and real TLS/redirect/deadline controls remain appropriate evidence; no real portal action is authorized.

## Review evidence and limits

Read the specification, all four OpenSpec artifacts and the official documentation linked above. Reused mandatory repository/product/delivery documents already read. Only this review record was added. No test, source, configuration, secret or runtime resource was changed, and no portal or fixture request was made.

Return to Gate 1 for the bounded clarifications above. This is not test or implementation approval; the existing normalization, employment-policy, publication and launch decisions remain outside this slice.
