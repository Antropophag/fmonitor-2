# Quality Graph continuation checkpoint

Continue CI work in `/Users/antropophag/code/fmonitor-2-quality-current-20260908`,
branch `codex/quality-governance-current-20260908`. The canonical implementation
commit is `d7edbc4185bbd73103915897a819a36a561bd47a`; later commits may contain only
its permitted evidence envelope. Read that worktree's
`docs/operations/quality-graph-governance-final-verification-2026-09-08.md` first.

The manual stand remains source4990cf1, image
`sha256:2bc0b0803182e0ac522fdfb6a90f60d8d4ed5ac0a90cdbdaf601e77cd78977ac`,
healthy at http://127.0.0.1:8092/pilot/objects. Runtime files in the CI candidate
are byte-identical to4990cf1. Preserve owner data/volumes and the existing login.
Stabilization/deployment/restart/golden evidence is in
[the stabilization report](stabilization-after-sleep-2026-09-08.md).

## Completed CI preparation

- Reused approved v0.6 content and exact QG0.1.7 release pins on current pilot baseb5.
- Added complete Git-derived raw-byte checks, receipt immutability, failure aggregation,
  strict reviewed-history envelope, generated workflows and clean Linux dependencies.
- Six governance suites and architecture checks pass. Independent code review caught
  edit/revert history bypass; REDv3/G3v3/fix3f9514b closed it.
- Exact full `make verify` passed all9stages at ae8cb07 (1333.14s) and3f9514b (1288.17s).
  These are preserved intermediate proofs, not final canonical-chain approval.
- The approved spec incorrectly put H1 before its required metadata fence. Commit4558778
  moves only that heading; reversing the permutation recovers original189111 bytes.
  Canonical spec SHA-256 is `5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b`.
  Independent reviewers agreed no new owner decision or parser waiver was required.
- REDv4 explicitly reuses genuine historical RED, G3v4 independently approves unchanged
  eight tests/new binding, GREENv3 truthfully records empty latest implementation delta.
  The final code-review commit will bind the entire Git tree and cumulative review.

## Active work and next steps

Clean verification checkout: `/Users/antropophag/code/fmonitor-2-verify-stabilization`.
A full exactd7edbc4 `make verify` is currently running. Primary evidence stays under
`~/.local/state/fmonitor2/quality-graph-current-20260908/`; check
`verify-d7edbc4-001.json` for an actual completed result before claiming PASS.

After literal finalVERIFY_OK: independent Gate5 by `agent:/root/migration18_collation`
at `reviews/code/QUALITY-GRAPH-GOVERNANCE-CURRENT-v3-2026-09-08.md`, then first immutable
receipt `delivery/evidence/QUALITY-GRAPH-GOVERNANCE-001/qg-current-20260908-v3.json`.
Use private `derive-green-receipt-v3.py`, which derives raw committed bindings;
root never authors the independent approval. No earlier receipt was issued.
Run the real `make delivery-evidence-check` after committing the receipt.

No remote mutation has occurred at this checkpoint. SSH read access works and
new canonical/disposable PR branch names were absent. After approval, create one
unmerged draft representative PR againstmain, retaining baseline verification.
For negative cases use isolated commit trees and preserve every old/case/merge head
under unique immutable archive refs before moving the owned disposable PR ref with
an exact-old lease. Canonical branch, main, PR10 and branch protection stay untouched.
Private `prepare-parity-fixture-v2.py` prepares only isolated objects, never publishes;
its safety conditions and exact hashes are recorded privately.

Actual positive/negative Actions results must be recorded honestly. PublisherphaseB
is unavailable while its topology is absent frommain; local tests are not aggregate
parity. Forced-stage and publisher negative rows remain unproved. Do not invent
APPROVED metadata for faulty fixtures or call a skipped verify node a failure proof.

Global goal remains ACTIVE. New owner manual findings are first priority. Once
current authorized CI work permits additional backlog work, issue27 (stale README)
is the smallest safe next task; its old manual12-Р/apply sequence is obsolete.
No issue has been edited or closed.
