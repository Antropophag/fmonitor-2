# Code review: USAGE-AGGREGATION-001

- Reviewer: root; did not author the spec, test or implementation.
- Author: usage_baseline, gpt-5.6-sol / low.
- Base: d9811cdd5e4521457a1d61277069fe3d0793f3fe; exact uncommitted bytes below.
- Verdict: APPROVED for executable code/test; baseline artifact correction pending.

## Findings and correction history

Initial CHANGES_REQUESTED: per-call utilization must use associated windows;
cumulative fallback is not a per-call input sample; empty usage is not a measured
zero response. Supplemental RED/Gate3 approved before corrections.

Correction review found two missing-data errors: total for input/output-only
records defaulted to zero; valid legacy input sample was discarded with an
unknown window. Supplemental RED/Gate3 approved before minimal fixes.

Final executable review: corrections implement independent arithmetic; unknown
role remains explicit; modern response records take precedence over cumulative
snapshots; duplicate IDs and cumulative snapshots are not summed twice; resets
and skipped usage mark incomplete; commands read source without mutation and
emit aggregates only. No billing or provider-count claim is made.

## Verification

Root ran `python3 tests/Usage/usage_aggregation_001_test.py` ->
`USAGE_AGGREGATION_001_OK`. Reviewed corrected source and retained canary,
input-preservation and invalid-path assertions.

Root independently selected exactly one private log by aggregate tuple and
checkpoint timestamp, streamed the prefix into an external temporary directory,
and invoked the public aggregate CLI. Input62781751/cached62058240/output125468/
reasoning68047, toolcalls261, max-input438293 and peak0.529084 reproduced exactly.
No private content or paths were exported. Observed basis is **response_ids**,
261 unique logged responses, not legacy snapshot estimation. Requested correction
of the hand-written baseline artifact's basis label; code output is correct.

## Reviewed executable bytes

- tools/usage/aggregate.py: `f75a6fd526a697a2002395ce2c63f90770ceb8878954e4c17759333cd494029d`
- tests/Usage/USAGE-AGGREGATION-001.md: `2641a858c8751a4b395c5ca571372625d3e15843e90e875195739c48a80410e6`
- tests/Usage/usage_aggregation_001_test.py: `3bb968e67898173d647f4708dbd4d9c60ff979691cf8ef5ddb64a0211a236cf3`

## Final artifact review - APPROVED

Baseline now reports response_ids. Root executed the literal README reproduction snippet against private local logs, captured only aggregate JSON, and compared counts/tokens to baseline-78.json: README_BASELINE_REPRODUCTION_OK. All earlier executable findings resolved; no remaining blocking findings in this bounded tool. This does not establish savings or full candidate CI.

Final artifact hashes:
- tools/usage/aggregate.py: `f75a6fd526a697a2002395ce2c63f90770ceb8878954e4c17759333cd494029d`
- tools/usage/README.md: `1c8465f290fa1da8a9b1e7b6e6a1f41d0ec8d690f4bdbd7fef16acbad7f41fb4`
- tools/usage/baseline-78.json: `1935ee03ef3b7eb4135a925a70a697175aa67898133c4dd4b0b6199778ace098`
- tests/Usage/USAGE-AGGREGATION-001.md: `2641a858c8751a4b395c5ca571372625d3e15843e90e875195739c48a80410e6`
- tests/Usage/usage_aggregation_001_test.py: `3bb968e67898173d647f4708dbd4d9c60ff979691cf8ef5ddb64a0211a236cf3`
