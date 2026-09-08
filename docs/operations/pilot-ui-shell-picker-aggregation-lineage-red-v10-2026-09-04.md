# PILOT-UI-SHELL-001 — picker aggregation lineage correction v10

- Date: `2026-09-04`
- Current evidence HEAD: `f1eb2693d11337dc42c38e7c685b4c1d87447469`
- Recorded production candidate: `6bf97f72254fb96849c1ef4322325a2e0b997ee4`
- Production comparison: `git diff --quiet 6bf97f7..f1eb269 -- app/PilotHttp` — exit `0`
- Scope: evidence-only Gate 5 correction
- Verdict: **RED — enlarged picker footer misses padded containment**

No production, test, specification or review file was changed. Current
production bytes are identical to exact candidate `6bf97f7`.

## Consolidated v10 report

```text
/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-consolidated-v10-6bf97f7/
```

The directory contains one `report.json` and 23 screenshots. The canonical 12
cases and configured-consumer 8 cases are the freshly measured exact-candidate
data from the v9 consolidated run. All three picker cases were freshly rerun
against the same exact production bytes; their screenshots and report entries
replace the earlier picker aggregation inside this v10 directory.

```text
5f0e36c23d3ec0ae526a69cc61901c7c2ab03afc7d2d23f6abee6dcad2f5fa47  report.json
```

Final counts:

```text
canonical layout cases: 12
canonical layout failures: 0
picker cases: 3
picker failures: 1
configured consumer cases: 8
configured consumer failures: 0
verdict: RED
```

## Corrected picker aggregation

Every required child is checked independently:

```text
heading
body
selection
search
meta
results
result
footer
```

For each child the runner performs controlled internal dialog scrolling, then
requires all of:

- DOM descendant of the picker dialog;
- full containment inside the padded visible content box;
- no horizontal or vertical text clipping.

Any `paddedVisible=false`, including footer, enters `failures.picker`.
Dialog close evidence is no longer collapsed into one boolean: the report
stores and independently requires `close.hidden` and `close.focusReturned`.

## Exact RED

At `320x568` with 200% root text, footer measurement is:

```text
dialog padding bottom: 24
dialog scrollHeight: 1017
dialog clientHeight: 536
footer offset: top=949 bottom=992
controlled scroll requested=480 actual=480 max=481
footer rect: left=40 top=485.40625 right=280 bottom=528.59375
padded content: left=40 top=40 right=280 bottom=528
descendant=true
unclipped=true
paddedVisible=false
```

The miss is `0.59375px`. The screenshot appears visually close because the
white dialog background continues behind the footer, but the executable exact
padded-containment condition is false. Per the requested rule this is a real
RED and is not rounded away.

Other required facts remain true:

```text
close.hidden=true
close.focusReturned=true
page horizontal overflow=false
result count=1
heading/body/search/meta/result/footer font ratios=2.0
```

The normal 320px and desktop picker cases pass every child. The enlarged case
alone enters the failure array.

## Lineage effect

The v9 report asserted picker GREEN using an aggregation that required padded
visibility only for search/meta/results/result and treated footer as merely
scroll-contained. It also stored close state as a combined boolean. Those
checks were insufficient for the stricter Gate 5 v3 requirement.

Therefore v9 remains immutable historical evidence but is superseded as
sufficient Gate 5 browser proof by this v10 RED. Gate 5 must not approve the
UI-shell candidate until a separately gated production correction makes the
footer fully padded-visible and a fresh exact-candidate consolidated report has
empty failure arrays.

The isolated browser database was dropped, task-owned `.test-artifacts` was
removed, and current working-tree/`git diff --check` checks were clean before
adding this append-only record.
