# Delivery harness — snapshot 2026-09-10

Owner authorized this tooling increment in an isolated branch/worktree, PR only.
Root owns specification/tests and decisions; `/root/runner_executor` and
`/root/codex_probe` (gpt-5.6-sol / low) implement separate tooling files.
`/root/consumer_evidence` independently reviews Gates 3/5 and authored neither.
No product migration #76, stand switch, merge or deployment is authorized here.

## Starting evidence

Fetched origin/main `4bab010f157d352f50d6b94050d80cb49d40a8a9`.
GitHub PR88 was MERGED when checked; stale committed goal text was not live state.
Historical checkout and every existing worktree/WIP were preserved.
Local baseline: `/tmp/fmonitor-harness-baseline/root-baseline.json` and `baseline.json`.
Codex CLI 0.154.0 exposes stable enabled hooks. Exact app-server schema generated
locally; hooks contain no token telemetry and this host's active transport is not
exposed. Current-session/root/subagent token totals and prior uninstrumented check,
agent and output counts are UNKNOWN. No LLM transcript audit, API billing estimate
or separate execution backend was used.

## Contract and review

- [Normative contract](../../specs/DELIVERY-HARNESS-001.md).
- [OpenSpec](../../openspec/changes/automatic-delivery-harness/tasks.md).
- [Verification input](../../openspec/changes/automatic-delivery-harness/verification-input.json).
- [Gate3 record](../../reviews/tests/DELIVERY-HARNESS-001.md).

RED snapshots/logs are retained outside the checkout under
`/tmp/fmonitor-harness-gate3*` and `/tmp/fmonitor-harness-baseline/`.
Two CHANGES_REQUESTED returns were resolved before implementation: full contract
coverage, then the single remaining mapped-evidence defect. Gate3 v4 APPROVED;
registration-only delta also independently approved. Legacy fixture preparation
passed native runner (9 tests) and CI contract (15 tests) before production edits.
Inventory registration RED also failed for the intended missing new suite; its one-line expectation was independently approved. These are dated observations, not an assertion about a future candidate or CI.

## Completion evidence

Implementation, focused checks, real Codex hook smoke, independent Gate5 and final
exact-source full CI are recorded separately as they occur. This record never
predicts a CI/merge result in its own checked commit. Actual state is computed by
`tools/delivery/harness.py state`; dated records remain historical evidence.

## Measured output experiment

Program compared the same retained streams with the actual emitted JSON summary:
240,028 raw bytes versus 3,637 summary bytes, 98.48% less runner output. Outcome
GREEN. Evidence `/tmp/fmonitor-harness-measurements-comparison.json`, emitted
summary `/tmp/fmonitor-harness-measurements-summary.json`; raw paths live in the
record referenced by that report. This is one synthetic same-log comparison,
not reduced total tokens, money or weekly quota. Actual current session/root and
subagent token telemetry remains UNKNOWN. Comparable completed delivery tasks
are still needed to measure total token impact; no duplicate product
implementation was run for benchmarking.

## Native integration diagnosis

The habitual interactive path reports CLI 0.154.0 with managed app-server
0.153.1. Project-local hooks were not discovered after project trust or a minimal
project config layer; a user-level hooks.json was discovered and became Active
through native `/hooks`. The implementation therefore adds the bounded additive
`install` fallback, scoped by registered Git common directory. A required runtime
upgrade was not established. No custom execution backend was introduced.

Baseline correction: root environment does expose session identifiers. The
read-only Thread/Turn schemas do not expose token counters, and the active host
notification transport remains unavailable to these tools. Thus actual primary
session/subagent token totals remain UNKNOWN; the earlier subagent inference
about missing identifiers is superseded by the programmatic
`/tmp/fmonitor-harness-baseline/host-interface-correction.json`.

## Actual native smoke

`/tmp/fmonitor-harness-baseline/native-hook-smoke-summary.json` programmatically
asserts startup, ordinary task prompt, resume and resumed prompt for one session
and integration fingerprint `5f597ee4afb1e27e44287b54c1d8e2b7de31b6f94b3f53cd6891f9327ac5eff9`.
Native `/hooks` showed five installed/active handlers after native trust.
`doctor` returned OBSERVED. Final smoke used ordinary interactive Codex, with no
hook-trust bypass or alternative backend; no product edits/checks occurred.
The one-time local install is `python3 tools/delivery/harness.py install`, then
native `/hooks` trust for the installed definitions. It has been performed on
this machine; unrelated hooks/settings are preserved. User-level hooks dispatch
only registered repository/worktrees. Managed runtime upgrade was unnecessary.

Before final retained run, executor focused results: harness 18, planner 14,
CI contract 16, inventory 16 and native runner 10 tests passed. Those direct
invocations are regression evidence, not retroactive instrumented measurements.

## Final local candidate — before publication

Gate5 found and closed one HIGH consumer-mapping defect: a fictional exact owner
was replaced with the confirmed `app/InspectionEvidence/**` pattern and exact
ChecklistSync owner. The real MariaDbYiiChecklist and Admission trait now drive
the five consumer obligations; local presentation remains negative. Independent
Gate3 correction and Gate5 delta are APPROVED in their review records.

Final evidence `/tmp/fmonitor-harness-final-focused-v2.json` contains six GREEN
focused commands on one source. Review package and reproducible delta:
`~/.local/share/fmonitor-2/delivery-harness/packages/20260910T153410Z-56c191dae3/`.
Only this delivery record, OpenSpec task metadata and the appended code-review
verdict are added after that reviewed snapshot; production/test bytes are checked
against it before commit. Full CI is still an external obligation on the final
committed candidate, not a predicted success in this document.

The earlier six short focused logs measured 6,860 raw stream bytes versus 8,547
bytes of diagnostic JSON including metadata. Thus metadata has overhead for
short logs; the verbose-log reduction is not universal output savings. Both
experiments are preserved in `/tmp/fmonitor-harness-measurements-comparison.json`.
