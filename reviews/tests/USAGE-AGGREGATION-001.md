# Test review: USAGE-AGGREGATION-001

- Reviewer: root (independent of usage_baseline author).
- Test author: usage_baseline, gpt-5.6-sol / low.
- Base: d9811cdd5e4521457a1d61277069fe3d0793f3fe; uncommitted new spec/test.
- Specification: tests/Usage/USAGE-AGGREGATION-001.md.
- Public seam: python3 tools/usage/aggregate.py --input PATH.
- Initial RED reported by author: missing aggregate.py; no implementation exists.
- Verdict: CHANGES_REQUESTED.

## Complete findings — pass 1

1. Missing/unrecognized role must be unknown, not implicitly main.
2. Prove mixed modern/legacy records are not double-counted; add malformed usage
   and unknown-role cases with safe output.
3. Flag legacy cumulative counter decrease/reset as incomplete instead of
   silently treating component maxima as exact complete usage.
4. Legacy distinct snapshots estimate requests; report this measurement basis.

Public CLI, independently calculated expected values, duplicate/canary and
input-preservation checks are appropriate. Implementation awaits revised RED.

## Revised Gate 3 — APPROVED

Reviewer root independently read revised spec/test and ran the RED command above: exit 1, assertion at line 73; missing aggregate.py. Fixture reaches intended public CLI, not a fixture setup failure. All four findings addressed: explicit unknown role; mixed format precedence and invalid counters; reset/incomplete marker; measurement basis. Independent expected totals and privacy/read-only assertions retained. Implementation may proceed.

Reviewed bytes:
- tests/Usage/USAGE-AGGREGATION-001.md: `8b1ca5e40925ad0faaaf7ddfb85ceeb310e525085c9c68cff3690ab38c4eebe0`
- tests/Usage/usage_aggregation_001_test.py: `75c8887cae79538f7974fc38ee35826cb5990d3d485ca7568c94fd7a880ac00c`

## Supplemental Gate 3 - APPROVED

Root independently reviewed new expectations for per-sample context windows, missing legacy last usage and empty usage rejection. Ran public test: exit 1 at line 80 because existing implementation accepts empty usage rather than counting it as skipped. Later expected peak is independently 140/200 = 0.7; cumulative-only snapshot adds no context sample. These correct misleading metrics, not arbitrary internals.
- tests/Usage/USAGE-AGGREGATION-001.md: `8ba47a8f4149b2922067d4f10dcdc3fdcddd5d9507261f0d313adee71da401b3`
- tests/Usage/usage_aggregation_001_test.py: `355d11e5342febb802485aacc8939fdd6b98667a29b600885f5fea301500b37c`

## Missing-data Gate 3 - APPROVED

Root independently inspected supplemental expected totals and observed RED at line88 (total omits input/output-only record). Correct independent total is 250+32=282. Legacy known per-call input100 remains a context sample with unknown window; peak among known windows is80/400=0.2. Implementation correction approved; all earlier canary/deduplication checks retained.
- tests/Usage/USAGE-AGGREGATION-001.md: `2641a858c8751a4b395c5ca571372625d3e15843e90e875195739c48a80410e6`
- tests/Usage/usage_aggregation_001_test.py: `3bb968e67898173d647f4708dbd4d9c60ff979691cf8ef5ddb64a0211a236cf3`

## Discovery privacy and identifier fallback Gate 3 - APPROVED

Root independently reproduced committed6f8fecb8 CLI failures: locked directory returned exit0; child path through locked parent returned exit1 and leaked canary path in traceback (uid501). Revised public tests retain null/empty call_id fallback and exercise directory, blocked-parent child, nested traversal, and direct unreadable file with effective-permission checks/restoration. Root observed RED at line95: expected four unique valid tool IDs, actual two. This is a new confirmed privacy/measurement risk after the earlier successful CI; a corrected candidate requires fresh CI.
- tests/Usage/USAGE-AGGREGATION-001.md: `3f361a7a22191c4f5a078eafffa80376c04c0affe3e489bbe08d21232078861d`
- tests/Usage/usage_aggregation_001_test.py: `9c7ea1f5f7cf4ce12756b68b6743283d040f1b80f0bc8d78f510b192e12ed7da`
