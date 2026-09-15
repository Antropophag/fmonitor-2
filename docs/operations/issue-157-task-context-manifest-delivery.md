# Delivery record — issue #157 compact task-context manifest

Owner authorization: issue #157 is the bounded T02 child of #145. Root authored scope, specification, baseline and executable tests. `/root/executor` (gpt-5.6-sol/low) authored the implementation. Independent Gate 3 reviews approved the original RED matrix and the two root-owned regression corrections before executor work resumed. Gate 5 and exact-source CI remain independent and pending.

Implemented boundary: the existing `tools/delivery/harness.py prepare` route now writes a digest-bound `task-context-manifest.json` and exact `required-context.json`, references both from `package.json` and the active binding/state, and leaves approval/admission unchanged. `tools/delivery/context-sections.json` contains stable locators only; canonical documents remain normative. Closed UI, persistence/current-state, auth/security and harness profiles use planned boundaries. Unknown applicability is conservative. Missing or invalid section locators fall back to available full canonical sources without an LLM, summaries, a second planner or a second policy engine.

The deterministic replay command is `python3 tools/delivery/measure-task-context.py --baseline docs/operations/issue-157-task-context-manifest-baseline.json`. On the implementation source it reported:

- bounded presentation/UI: 78,637 -> 13,764 mandatory content bytes and 19,291 total serialized delivered bytes; 56,113 -> 12,825 characters; 11 -> 2 whole documents;
- persistence/current-state: 138,336 -> 103,577 mandatory content bytes and 118,469 total serialized delivered bytes; 98,763 -> 67,869 characters; 13 -> 6 whole documents; all ten reviewed sensitive/general rule identifiers retained;
- harness/verification: 54,275 -> 14,579 mandatory content bytes and 20,974 total serialized delivered bytes; 46,275 -> 14,134 characters; 9 -> 2 whole documents; all four reviewed governance/general rule identifiers retained.

The replay also reports serialized manifest bytes, required-context artifact bytes and their combined `total_delivered_bytes`. Large historical trees are represented by three compact reconstructible collection references rather than a 1,207-file inline roster; current-task review references are limited to the matching contract identifier. These package-overhead fields, rather than mandatory content alone, are the honest delivered-byte comparison.

These are exact UTF-8 byte/Unicode-character context proxies. Token usage is `UNKNOWN`; the result supports only reduced mandatory context bytes and expected token-pressure reduction.

Focused GREEN evidence:

- `python3 tests/Verification/delivery_harness_context_manifest_001_test.py`: 4 tests GREEN (cases A–L);
- `python3 tests/Verification/change_verification_001_test.py`: 18 tests GREEN;
- `python3 tests/Verification/delivery_harness_001_test.py`: 25 tests GREEN;
- `openspec validate compact-task-context-manifest --strict`: valid;
- `make architecture-check`: GREEN, including PILOT-HTTP-AUTH-001 global-call qualification;
- `python3 -m py_compile tools/delivery/harness_context.py tools/delivery/measure-task-context.py` and `git diff --check`: GREEN.

The constitutionally prohibited local full `make test` / `make verify` was not run. No product code, FAST classifier, verification coverage, Gate, CI performance, rapid-pilot code, merge/deploy/settings or other #145 task was changed. Independent Gate 5 review, one exact-source GitHub CI run and PR preparation remain root-owned next steps.
