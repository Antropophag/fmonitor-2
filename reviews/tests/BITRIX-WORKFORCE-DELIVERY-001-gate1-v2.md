# Independent Gate 1 amendment — BITRIX-WORKFORCE-DELIVERY-001

- Verdict: **APPROVED**
- Reviewer: `/root/original_gate1_v3`, separately tasked agent; not an author of the specification, tests or implementation.
- Date: 2026-09-07
- Reviewed HEAD: `f1f0b824cba97ff7b95582b98e5284295ce5d479`.
- Specification: version 0.2, SHA-256 `136c20f8a3d167cda67414198acaad605ec933f1b4bc76fd6188e723a949e7fc`.
- OpenSpec design SHA-256: `83e19186187c3fddd1f791cb09638d8ba066b611398f2f8e641d393aaaa7d7c4`.
- Prior record: `BITRIX-WORKFORCE-DELIVERY-001-gate1.md`.

## Amendment and disposition

All material v0.1 findings are resolved. The normative contract now requires asynchronous DNS, millisecond request/connect timeouts, pretransfer timing and effective-UID capability at fetch time before credentials or network activity. An unsupported runtime returns configuration_unavailable with zero pages/attempts. NOSIGNAL is explicit. This makes the supported transport boundary consistent with the resolver limitation documented in [libcurl NOSIGNAL](https://curl.se/libcurl/c/CURLOPT_NOSIGNAL.html).

The monotonic deadline starts after credential/CA preflight, includes attempts, retry waits and response validation, floors remaining time to milliseconds and refuses an attempt below one millisecond. Native request/connect timeouts are capped by that positive budget, avoiding the zero-means-unbounded behavior documented for [TIMEOUT_MS](https://curl.se/libcurl/c/CURLOPT_TIMEOUT_MS.html). Checks after each attempt and page, including final success, and the explicit limit→deadline→ordinary failure precedence remove the prior outcome ambiguity. Page counters advance only after complete validation and its deadline check.

Accepted origins with or without the optional trailing slash now yield the same single `/rest/` separator, with DNS host case normalized. Overfull/short page length belongs explicitly to pagination_invalid, preserving body/person-limit precedence. The worked UF_XING value is explicitly the decimal string of N, consistent with raw string/null delivery and the separate normalization boundary.

The aligned OpenSpec design preserves the same scope. All nonblocking v0.1 assessments remain applicable: exact selected-field requests, no partial batch, raw immutable values, safe JSON summary, TLS/no-redirect/no-proxy rules, synthetic-only native evidence and no DB, cron, grants or publication. The prerequisite decision does not claim that a particular live portal or credential is ready.

## Evidence and limits

Independently read the complete amended specification and design and confirmed their hashes. Reused the official documentation inspected in v0.1. Local/preview capability observations reported by the author are context only, not reviewer-reproduced native test evidence. Gate 3 must demonstrate the supported runtime and actual bounded TLS/request/retry outcomes, rather than infer success from capability flags alone.

Only this review record was added. No test, implementation, secret or runtime resource was changed or executed. Gate 1 v0.2 is **APPROVED**; native RED, independent Gate 3, minimal GREEN, regressions/architecture checks and independent Gate 5 remain required. The initial CHANGES_REQUESTED record is preserved.
