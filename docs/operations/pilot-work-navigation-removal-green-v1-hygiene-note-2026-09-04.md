# Navigation GREEN v1 evidence hygiene note — 2026-09-04

Append-only source record:
`docs/operations/pilot-work-navigation-removal-green-v1-2026-09-04.md`.

Source hash:

```text
c82bee3a3f27b44ac489b15227a32dfcd2341f4e7d94ff2f814df2bf597fb122
```

Exact historical range `ff55373594794b03a96480321d6bf581ec73beae..4f70942f3039ca4d91d24a441628be468098e342`
returns `git diff --check` exit `2` because lines 3–5 use Markdown hard-break
trailing spaces. The v1 statement `git diff --check: exit 0` referred to the
working production diff before this record was added and is not a clean-range
claim for the final evidence head.

The source record remains unchanged under append-only policy. This correction
changes no production, specification, test, review or verification outcome.
