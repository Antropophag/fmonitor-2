# Code review: ARCHITECTURE-DEBT-FINGERPRINT-001

- Reviewer: separately tasked Codex agent `/root/architecture_fingerprint_final_acceptance_review` (independent Gate 5 reviewer; did not author the implementation or approved test)
- Reviewed commit: working tree at HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`
- Reviewed at: `2026-08-31T21:09:13Z`
- Normative specification: `ARCHITECTURE-DEBT-FINGERPRINT-001` contract supplied with the review handoff
- Approved test review: [`reviews/tests/ARCHITECTURE-DEBT-FINGERPRINT-001.md`](../tests/ARCHITECTURE-DEBT-FINGERPRINT-001.md)
- Approved executable test: [`tools/architecture/tests/test_debt_fingerprint.py`](../../tools/architecture/tests/test_debt_fingerprint.py), twelve tests
- Verdict: `APPROVED`

## Standards

`APPROVED`. The checker delegates PHP lexical classification to `token_get_all()` and performs one narrowly scoped normalization: a leading global qualifier is removed only from a single-segment fully qualified name in direct function-call position. Constructor class names are excluded by the preceding `T_NEW`; attribute class names are excluded while bracket-balanced `T_ATTRIBUTE` context is active. Whitespace and comments between a callable name and `(` are handled without treating them as semantic predecessors.

This remains a deterministic architecture-ratchet concern inside `tools/architecture`. It does not move domain behavior, persistence, authorization, audit/history, or integration ownership. Subprocess input is passed directly, output is captured, PHP failure is fail-closed, and no shell interpolation is used. The implementation has no documented-standard violation or material Fowler smell. Depending on the installed PHP CLI is appropriate because the repository is a PHP application and using the language lexer avoids maintaining a divergent parser.

The authorized baseline update is inventory-preserving. Fresh `collect()` output equals the baseline byte-for-byte by category: 40 DDL findings, 221 SQL findings, 7 dependency-direction findings, 66 rapid-pilot-boundary findings, 18 hotspots, and 3 public seams. A second collection is identical, `compare()` returns no errors, and the baseline therefore records neither added debt nor hotspot/seam growth.

## Spec

`APPROVED`. The approved twelve-test suite is green and retains the complete reviewed boundary. Actual executable PHP direct calls are qualifier-neutral. Constructor class names, attribute class names, nested/comma-separated attribute arguments, SQL/DDL literals, quoted and backtick strings, heredoc, nowdoc, comments, inline HTML, invalid PHP open-tag prefixes, namespace identity, and non-call class contexts remain fingerprint-sensitive. Valid lowercase/uppercase open tags, close/reopen regions, attributes preceding ordinary code, and close tags inside both supported line-comment forms are covered.

Independent probes additionally passed for a constructor separated by a comment, multiple consecutive attributes, a direct call separated from `(` by whitespace/comment, a multi-segment namespace call, and a static class call. These probes confirm that the corrected predecessor and attribute-depth state do not collapse class or namespace identity while genuine function calls still normalize.

The production `collect()` seam remains meaningfully exercised: temporary production-shaped PHP fixtures must reach all requested ownership buckets before complete finding lists are compared. Relational equality/inequality expectations contain no copied digest and reject constant, never-normalizing, blanket-backslash-removal, and text-only implementations. Fixture cleanup left no `rapid-pilot/FingerprintFixture*.php` files.

Failure classification is explicit and reproduced: a new finding returns exit 1, a missing baseline returns exit 2, and an unavailable/failing PHP tokenizer raises `RuntimeError: PHP tokenizer unavailable for architecture fingerprinting` instead of silently changing fingerprints. A normal architecture run completed in 4.81 seconds with 36,788 KiB maximum RSS on the review host; the focused twelve-test suite completed in 1.926 seconds. This is acceptable for the repository verification gate and avoids per-line lexer startup by tokenizing each source file once.

## Verification evidence

```text
$ python3 tools/architecture/tests/test_debt_fingerprint.py
............
Ran 12 tests in 1.926s
OK

$ python3 -m py_compile tools/architecture/check.py tools/architecture/tests/test_debt_fingerprint.py
exit 0

$ make architecture-check
ARCHITECTURE CHECK PASSED (6 rules)

$ tools/architecture/check --json
{"errors": [], "ok": true, "rules": 6}

$ make unit-test
32 focused unit verifier files passed
exit 0

$ git diff --check -- tools/architecture/check.py tools/architecture/tests/test_debt_fingerprint.py tools/architecture/baseline.json reviews/tests/ARCHITECTURE-DEBT-FINGERPRINT-001.md
exit 0

$ /usr/bin/time tools/architecture/check
ARCHITECTURE CHECK PASSED (6 rules)
4.81 seconds; 36788 KiB maximum RSS
```

Independent collection/baseline evidence:

```text
COLLECT_DETERMINISTIC True
ddl_ownership 40 40 equal=True
sql_ownership 221 221 equal=True
dependency_direction 7 7 equal=True
rapid_pilot_boundary 66 66 equal=True
hotspots 18 18 equal=True
public_seams 3 3 equal=True
COMPARE_ERRORS []
```

Review runtime: Python 3.14.4; PHP 8.5.4 CLI.

## Reviewed-input SHA-256 manifest

```text
228ca72315ee3bd8d5e2be2c66e8e858e0d421ab70a3993d8ddb8fa6446fd4ed  tools/architecture/check.py
51a78d95e39b2f5d9f44a3d3afd648e6f78416dd3016329f8156906e4cb0e27b  tools/architecture/tests/test_debt_fingerprint.py
3db49fd60fe2c854778502a929387e999fbfacb3c05ae55628116537ec910f3e  tools/architecture/baseline.json
095180fc1d6793052116dbe41cdbefe5073d4d76ff6371aacde721ed4cb2e983  reviews/tests/ARCHITECTURE-DEBT-FINGERPRINT-001.md
```

Any byte change to a reviewed input invalidates this approval and requires a fresh independent review.

## Findings

None.

## Required changes

None. Gate 5 for `ARCHITECTURE-DEBT-FINGERPRINT-001` is `APPROVED`.
