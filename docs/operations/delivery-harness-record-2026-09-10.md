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

## First full CI — observed failure, 2026-09-10

PR89 candidate `98c6eceeac8d606e0215b1d59354d2d2c9f5d8b4` ran full
[CI34496967157](https://github.com/Antropophag/fmonitor-2/actions/runs/34496967157).
Plan, fast, unit, governance, E2E and Integration(2/2) passed. Integration(1/2)
reported two failures among 125 tests, so final verify correctly failed:

- `assignment_order_unknown_employment_schema_collation_001_test.php`
- `characterize_object_detail_import_001_test.php`

Complete failed-job/REGRESSION_FAILURE inventory and full failed logs were saved
in `/tmp/fmonitor-harness-ci-34496967157.json` and `-failed.log` before correction.
Both child streams reported successful behavior; the harness incorrectly treated
UNKNOWN inside domain identifiers as its control outcome. The correction
distinguishes explicit line markers from ordinary domain text. A public CLI RED
replays both actual strings and protects late real setup/unknown markers, their
precedence and complete logs. Gate3 delta approved; corrected source requires
new independent Gate5 delta and a new full CI. The failed run is retained and
is not reclassified as successful; product code/tests and the stand are unchanged.

## Owner follow-up — bounded PR89 corrections

Mixed Gate3 expectations are attached to the existing acceptance mapping, while
Gate5 remains all GREEN. Prepared external plans are supported by the existing
verification commands, preserving strict repository paths. Mutable bindings and
active plans are isolated per concrete worktree. GREEN output contains only
identity, outcome and retained-record navigation; metadata and streams stay local.

On the same six archived streams (SHA-256 equality checked), the actual updated
planner/runner delivered 1,504 bytes versus 8,547 before, for 6,860 raw bytes.
Program/report: `/tmp/pr89-measure-short-replay.py` and
`/tmp/pr89-short-replay-measurement.json`. Original product tests were not
re-executed for this formatting replay. Token usage remains UNKNOWN.

First complete local `make test` on artifact source
`ee0a059d04c4a78816ba664922c77bb84e7aabc94f2fecfbc68ec20aea1d613b`
passed 359 leaf checks plus architecture/lint and literal VERIFY_OK in 1976s.
Record: `/tmp/pr89-local-full-first-result.json`. Pinned runtimes and isolated
Compose project `fmonitor2-harness-pr89`, port23307 preserved the existing23306 DB
and stand. This is evidence for that snapshot, not the final corrected commit.

Independent Gate5 subsequently found the generated namespaced active-plan name
was outside the resolver's filename allowlist. The new public-seam RED uses each
worktree's actual returned state plan with `check --plan`; Gate3 approved that
correction. Only the exact generated 20-hex name grammar is admitted, retaining
containment and source-path guards. A final local full run and full GitHub CI on
the final committed source remain required; no merge/deployment is authorized.

## Final correction checkpoint — before final committed runs

Gate5 delta APPROVED the final source
`62d35eb0c17a7a1ab3a4fd73b04bc7dc25a6675241f6e7b0c76a43b48f020584`,
package `~/.local/share/fmonitor-2/delivery-harness/packages/20260910T172504Z-d7d428e534/`.
Final focused evidence `/tmp/pr89-final-fixes-focused.json`: planner16, harness24,
CI contracts16, native10, inventory16 and architecture guard all GREEN. The real
current worktree's returned active plan passed refresh/check in
`/tmp/pr89-real-active-plan-check.json`. Final native startup/resume/worktree
binding proof is `/tmp/pr89-final-native-smoke.json`. Gate3 approvals are retained
for the exact final contract/tests; package preparation itself remains NOT_REVIEWED.

Only the appended independent code review, this dated delivery record and task
metadata differ after the reviewed snapshot. Code/tests are compared byte-for-byte
before the final commit. The subsequent full local run and GitHub CI report their
results outside this commit; this record does not predict their outcomes.
