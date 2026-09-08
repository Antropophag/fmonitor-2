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
Exactd7edbc4 completed all9stages with literal VERIFY_OK, exit0,1193.45s
(2026-09-08T01:32:44Z..01:52:37Z). Private `verify-d7edbc4-001.json` and log SHA-256
`357df9d92c0f5e49ee5bdd28ebb256682115bb590790d7bc438ab89e92432ad8` are the final local proof.
The disposable test DB was torn down afterward; manual volumes remain untouched.

Independent Gate5v3 APPROVED by `agent:/root/migration18_collation`, committed0f26076,
record `reviews/code/QUALITY-GRAPH-GOVERNANCE-CURRENT-v3-2026-09-08.md`, SHA-256
`39db9593785c105913eb096dd8c0fffb063a5f56c6a2b601cd6e7755e581dda0`.
First immutable receipt `delivery/evidence/QUALITY-GRAPH-GOVERNANCE-001/qg-current-20260908-v3.json`
was committed2fa68e8; no older receipt existed. Actual `make delivery-evidence-check`
and graph validation PASS at `5a09256df11f8a62d965eb932db4cd04a9790207`.

## Actual GitHub phase A now running

Published canonical branch `codex/quality-governance-current-20260908` and separate
disposable PR branch `codex/qg-parity-20260908`; both initially5a09256.
[PR37](https://github.com/Antropophag/fmonitor-2/pull/37) is OPEN/DRAFT againstmain2bff0a0e.
No merge, protection change, PR10 action, issue edit/closure or runtime deployment.
Canonical branch may receive only permitted evidence commits while PR head stays fixed
for each run. The current full comparison includes accumulated pilot history; the PR
is explicitly a CI experiment and not approval to merge1130commits.

- Baseline run: https://github.com/Antropophag/fmonitor-2/actions/runs/34178683041
- Graph run: https://github.com/Antropophag/fmonitor-2/actions/runs/34178683097
- Same initial head5a09256/PR37/attempt1; PR API merge73af27535c50ebbcf3e8a47fa38779c436329fde.
- Both Linux dependency setup steps PASS; both full repository commands are running.
- Graph validation and real delivery-evidence nodes PASS; two Resultv0 artifacts
  downloaded and exact node/repository/PR/head/run/attempt/digest verified.
- Graph digest95ab7381b6ce103c5ab3cce6fbb54826cf227470f4a7288894e30d74949ea325.

Read CI worktree `docs/operations/quality-graph-representative-pr-phase-a-2026-09-08.md`
for subsequent results. Primary evidence is private under
`~/.local/state/fmonitor2/quality-graph-current-20260908/github-pr37/positive-5a09256/`.
Wait for actual completed baseline/graph/verify artifacts before claiming positive parity.
Then prepare negative heads with private `prepare-parity-fixture-v2.py` (guarded to
reviewedCommitd7 and receipt-v3; logic independently reviewed). It only prepares isolated
Git objects using a temporary index; do not run with Python optimization. Preserve each
positive/case/executed-merge head under a unique immutable remote archive ref before
any exact-old leased move of the owned disposable PR ref. Keep canonical/main/PR10
unchanged and restore the PR to a valid reviewed head. No fabricated fixture approvals.

PublisherphaseB remains unavailable while topology is absent frommain; local fixtures
are supporting evidence only. Forced-stage and publisher negative rows are unproved;
a source fault rejected by lineage is not a verify-node failure. Report partial rows
and concrete limits honestly instead of declaring full aggregate/cutover readiness.

Global goal remains ACTIVE. New owner manual findings are first priority. Once
current authorized CI work permits additional backlog work, issue27 (stale README)
is the smallest safe next task; its old manual12-Р/apply sequence is obsolete.
No issue has been edited or closed.
