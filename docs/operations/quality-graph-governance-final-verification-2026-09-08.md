# Current-line Quality Graph verification

## Reviewed source and preserved pilot

The installed manual pilot remains source `4990cf1afd90813c60f155297f427eb822ae78e9`,
image `sha256:2bc0b0803182e0ac522fdfb6a90f60d8d4ed5ac0a90cdbdaf601e77cd78977ac`.
Its literal VERIFY_OK, deployment, restart, data preservation and exact-image
full golden are recorded in [the stabilization report](stabilization-after-sleep-2026-09-08.md).
CI development uses a separate worktree and does not deploy product changes.

Current CI implementation: `3f9514baf53851029a7a39af5c94348c70d634b7`.
Approved specification: v0.6 SHA-256
`189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859`.
Current RED: `83ff618956d13cbffceddd62feb332a5bf22b9e6`;
[test review v3](../../reviews/tests/QUALITY-GRAPH-GOVERNANCE-CURRENT-v3-2026-09-08.md)
and [focused GREEN v2](quality-graph-governance-current-green-v2-2026-09-08.md)
contain the exact eight-test set and raw Git implementation delta.

## Intermediate full run — not the final reviewed candidate

Source `ae8cb079520470afb02d39e4c41e54fcb4f5bcd1` passed all nine stages with literal
`VERIFY_OK`, exit0, 1333.14seconds. Start `2026-09-08T00:33:55.781054+00:00`,
finish `2026-09-08T00:56:08.863278+00:00`.
Private log: `~/.local/state/fmonitor2/quality-graph-current-20260908/verify-ae8cb07-001.log`;
SHA-256 `6c7aaab19db5d7b7120569446b7bab4365e3719038e3dee862af26dfb9a50b0c`.

The independent [Gate5 record](../../reviews/code/QUALITY-GRAPH-GOVERNANCE-CURRENT-2026-09-08.md)
remains CHANGES_REQUESTED: a net Git diff hid intermediate source edits followed
by reverts. A full test PASS did not waive that finding. A new isolated Git test
first reproduced RED, was independently approved, then passed with the 3f9514b
history-aware fix. The synthetic merge onto an ancestor base also passes.

The checker deliberately inspects all new reachable history. Unreviewed governed
source commits arriving from an advanced base/side branch require new review even
if a merge discards their final effect. This conservative policy follows the
allowed evidence-commit envelope; no advanced-base waiver is implied.

## Final run status at this checkpoint

The clean detached verification checkout is running `make verify` at exact
`3f9514baf53851029a7a39af5c94348c70d634b7`. Final full-run result, independent Gate5v2,
first immutable receipt and actual representative PR evidence are pending.
No remote mutation, merge, branch-protection change or trusted publisher parity
has been claimed. Subsequent completed results are appended below.

## History-fixed full run and canonical binding

Exact3f9514b passed all nine stages with literal VERIFY_OK, exit0,
1288.17seconds. Start `2026-09-08T00:56:12.230278+00:00`, finish
`2026-09-08T01:17:40.366377+00:00`. Private `verify-3f9514b-001.log` SHA-256
`357df9d92c0f5e49ee5bdd28ebb256682115bb590790d7bc438ab89e92432ad8`.

Before issuing the first receipt, the original approved specification was found
to put its H1 before the required metadata fence. The strict checker was retained.
[Canonical format reconciliation](quality-graph-governance-canonical-format-2026-09-08.md)
moves only that heading; reversing the permutation exactly restores approved189111.
The canonical digest is `5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b`.
[REDv4](quality-graph-governance-current-red-v4-2026-09-08.md) explicitly reuses genuine
historical RED, and [independent Gate3v4](../../reviews/tests/QUALITY-GRAPH-GOVERNANCE-CURRENT-v4-2026-09-08.md)
approves the unchanged eight tests and new binding. No fresh RED execution was invented.

Current exact canonical GREEN is `d7edbc4185bbd73103915897a819a36a561bd47a`;
[GREENv3](quality-graph-governance-current-green-v3-2026-09-08.md) truthfully declares
an empty latest implementation delta. Its commit binds the entire existing tree.
All six governance suites/compiler validation pass; executable files are byte-identical
to3f9514b. A fresh full make verify is running at exactd7edbc4 before final Gate5v3.
No real receipt, remote publication or publisher parity has yet been claimed.
