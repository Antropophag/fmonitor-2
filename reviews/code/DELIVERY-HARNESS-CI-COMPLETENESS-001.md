# Code review: DELIVERY-HARNESS-CI-COMPLETENESS-001

- Reviewer: Codex agent `/root/issue99_gate5` (independent Gate 5 reviewer)
- Implementation author: separate executor Codex session
- Reviewed commit: `43adc836e5b92fc320b6163f7ef2a75db72e043c`
- Candidate source: `5e2333c63d81b2d2298222ba48907ccbbdfd45b06867b665ca74872aff5e2111`
- Executable source: `8b4bfdf96198fb58d71d1d8095deaac0454d76f97d13b99c89c3f0a473f3086a`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T135934Z-9fcb8ead3d/package.json`
- Package evidence: record `1789221470895609000-373eb9f557c64512ba44bbe207d6c6e8`, acceptance GREEN; record source `63a71214e39debc6e07e78fecedc15d9bb3292c92a4dccab040bb9ca2efa7c29`, executable source `8b4bfdf96198fb58d71d1d8095deaac0454d76f97d13b99c89c3f0a473f3086a`
- Scope: issue #99, OpenSpec `delivery-harness-first-pass-ci-completeness`, normative specification, approved Gate 3 tests, complete implementation diff and bounded evidence
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Blocking — the exact prepared plan drops a required generated-source consumer.** `tools/delivery/change-verification.py:410-443` deduplicates commands by argv without upgrading provenance, then the harness-only filter retains only selected rationales. In the reviewed package, `tests/Verification/verification_ci_001_test.py` is absent: the plan contains only the acceptance command and `render-dependencies.py --check`, even though `.quality-graph/verification-policy.json` declares that test as the generated runtime Dockerfile consumer. This violates R1 and issue #99's requirement that all known adjacent consumers be planned. Preserve/merge obligation purposes and provenance during deduplication, and make the exact repository plan retain the generated consumer.

2. **Blocking — `publication_ready` is emitted without executing or requiring the plan-owned category/environment evidence.** `tools/delivery/change-verification.py:587-630` runs the generator check and a few source-text heuristics, but it does not execute the bounded plan commands in their declared profiles, compare observed services/dependencies with those profiles, or reject missing plan-owned evidence. Nevertheless line 620 sets `publication_ready` solely from that limited `failures` list. PHP dependency detection is restricted to the token `mysqli`, Node dependencies are not inspected, and the output does not record actually available services/dependencies. This does not satisfy R2/R7 and can repeat the original failure mode for any undeclared dependency outside the two fixtures. Admission must cover every selected bounded obligation, preserve typed environment evidence, and remain blocked/UNKNOWN when a profile cannot be reproduced.

3. **Blocking — reviewer evidence is not actually typed or bound to the command identity.** Runner records created by `tools/delivery/harness.py:305-322` have no `command_id` or `purpose`. `tools/delivery/harness_context.py:465-489` validates only normalized argv/outcome and then copies `purpose` from the current plan, without checking a record-owned id/purpose or the command's declared environment profile. Thus an ordinary record for matching argv can be relabelled as boundary/category evidence. R3 requires stable record-owned id and purpose with matching argv, source and environment; write those fields into the retained record and validate all of them fail-closed.

4. **Blocking — dependency workspace confinement and consumer authorization are incomplete.** `tools/delivery/harness_context.py:492-523` accepts any absolute existing directory from an external manifest; it has no configured allowed realpath/root boundary. It only checks that named consumer files exist, not that they are plan-owned commands authorized to consume that workspace. The caller-controlled `identity` string is not verified as a lock/source digest. An arbitrary external directory and any existing repository file therefore pass initial preparation. Enforce an explicit allowed-root/realpath policy, bind consumers to plan commands, and derive/verify the declared immutable lock/source identity as required by R6.

5. **Blocking — Gate 3 test-delta lineage does not prove an exact test delta or acceptance continuity.** `tools/delivery/harness_context.py:601-615` checks only that the supplied historical record is `INTENDED_RED` and its argv ends in the current test path. It does not capture or validate base/current test blobs or a delta digest, does not bind the historical record to the same acceptance id, and does not validate that the current GREEN is the successor of that exact test lineage. A different historical test revision with the same argv is admitted. Implement the exact base/current delta and acceptance mapping required by R4.

## Verification

- `python3 tests/Verification/delivery_harness_ci_completeness_001_test.py` — 12 tests GREEN in 23.937s. The suite does not detect the findings above; notably it checks the generated consumer only in a synthetic plan and infers evidence purpose from run summaries rather than validating retained record fields.
- `python3 tests/Verification/change_verification_001_test.py` — 16 tests GREEN in 16.119s.
- `python3 tests/Verification/delivery_harness_001_test.py` — 25 tests GREEN in 28.524s.
- `python3 tests/Verification/delivery_harness_hardening_001_test.py` — 9 tests GREEN in 35.136s.
- `python3 tests/Verification/verification_ci_001_test.py` — 16 tests GREEN in 36.337s.
- `python3 tools/delivery/render-dependencies.py --check` — GREEN.
- `openspec validate delivery-harness-first-pass-ci-completeness --strict` — valid.
- No local `make test` or `make verify` was run. PR/CI remain `UNKNOWN` and are not treated as approval.

## Required correction path

These findings expose missing normative behavior and test sensitivity, so corrections require returning to Gate 2, extending the acceptance tests, capturing a new intended RED for the exact corrected test source, and obtaining a fresh independent Gate 3 approval before implementation and Gate 5 rereview.
