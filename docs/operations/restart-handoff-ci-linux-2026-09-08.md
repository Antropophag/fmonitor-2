# Safe restart: clean Linux CI repair — 2026-09-08

## Resume here

Read `AGENTS.md`, `current-delivery-goal.md`, this handoff, then the product/pilot
contracts and `development-process.md` before resuming work. The owner requested
this safe session restart. No agents or local test processes are running. Both
initial GitHub jobs are terminal FAIL; do not resume old process/session IDs.

Main worktree: `/Users/antropophag/code/fmonitor-2`, branch
`codex/remove-pilot-work-navigation-v2`. It contains operations documentation only
since the installed pilot. Preserve unrelated untracked `.DS_Store`, `docs/.DS_Store`
and `docs/architecture/audit-and-target-2026-09-07.md`.

CI worktree: `/Users/antropophag/code/fmonitor-2-quality-current-20260908`, branch
`codex/quality-governance-current-20260908`, exact clean HEAD
`62fd8ba5ac3ac63172b0335bb737fa68ccc1f8dc`. This is the new fourteen-test RED v5;
CI tooling implementation has NOT begun. Remote canonical branch is stilla2413da.

## Preserved working pilot

URL: http://127.0.0.1:8092/pilot/objects ; existing owner login, no passwords in repo.
Installed source `4990cf1afd90813c60f155297f427eb822ae78e9`, immutable image
`sha256:2bc0b0803182e0ac522fdfb6a90f60d8d4ed5ac0a90cdbdaf601e77cd78977ac`.
Container `fmonitor2-manual-pilot-1` was rechecked running/healthy at restart.
Only it and `fmonitor2-manual-mariadb-1` remain running. Test DB was torn down.

Literal VERIFY_OK, migration19, business-data/artifact preservation, old-session
continuity after update/restart and exact-image golden41/7/85/100 are proved in
`stabilization-after-sleep-2026-09-08.md`. No runtime/app/Dockerfile changes have
been made during CI work. Owner objects1450/966 remain real data: read-only.
The pre-existing nonblocking OTIZ fund-track CSP limitation remains recorded.

## Completed local CI chain

In the CI worktree, read `quality-graph-governance-final-verification-2026-09-08.md`.
Canonical executable spec hash is now
`5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b`.
Commit4558778 moved only the existing H1 after the metadata fence; the inverse
permutation exactly restores owner-approved189111 bytes. Independent reviewers
confirmed this routine formatting correction needed no new owner decision or parser waiver.
Historical records remain unchanged; refreshed records explicitly reuse genuine RED.

Canonical implementation `d7edbc4185bbd73103915897a819a36a561bd47a` passed all9stages,
literal VERIFY_OK,1193.45s. Private `verify-d7edbc4-001.json`/`.log`; log SHA-256
`357df9d92c0f5e49ee5bdd28ebb256682115bb590790d7bc438ab89e92432ad8`.
Earlier ae8cb07 and3f9514b full runs also passed and remain separate execution receipts.

Independent Gate5v3 APPROVED at0f26076, record
`reviews/code/QUALITY-GRAPH-GOVERNANCE-CURRENT-v3-2026-09-08.md`, SHA-256
`39db9593785c105913eb096dd8c0fffb063a5f56c6a2b601cd6e7755e581dda0`.
First immutable receipt-v3 committed2fa68e8; actual delivery checker PASS at5a09256
and a2413da. The latest delta is truthfully empty; reviewedCommit binds the whole tree.
New test changes at62fd8ba require fresh downstream gates and a superseding receipt;
do not modify or reuse the old receipt as approval of these changes.

## Actual GitHub evidence — FAIL, not positive parity

[PR37](https://github.com/Antropophag/fmonitor-2/pull/37) remains OPEN/DRAFT,
disposable head `codex/qg-parity-20260908` at
`5a09256df11f8a62d965eb932db4cd04a9790207`, base main2bff0a0e.
The PR compares accumulated pilot history and explicitly must not be merged.

- Baseline34178683041: completed FAILURE; unit-test FAIL; other8stages PASS.
- Graph34178683097: completed FAILURE; graph-validation/evidence PASS; verify FAIL
  at the same unit-test stage; other8verification stages PASS.
- Same PR37/head5a09256/attempt1; graph digest
  `95ab7381b6ce103c5ab3cce6fbb54826cf227470f4a7288894e30d74949ea325`.
- Actual Linux dependency setup steps initially returned success, but omitted rg and
  the required PHP test image. Do not mistake those setup-step successes for completeness.
- Two passed Resultv0 artifacts were downloaded and exact provenance checked.
- Raw primary logs: private `github-pr37/positive-5a09256/baseline-run.log`
  SHA8bad06918cffacce3330da284391474a0664b6e8404438b2f6c7fbd410efc156;
  `graph-run.log` SHA4f92eb26d5841b980da609cf454b42bcbf30bcee32c6ab2a0f9709b078db5ec3.

Synthetic merge73af27535c50ebbcf3e8a47fa38779c436329fde is saved locally at
`refs/qg-parity-archive/20260908/positive-merge-73af2753`; its tree equals5a09256.
No remote archive refs, forced PR-ref moves, negative fixture commits, merges,
branch-protection changes, PR10 edits or issue closures have been performed.

## Four diagnosed Linux problems and current repairs

1. `rg` absent:1005 command-not-found lines; run.sh silently classified DB tests as
   unit tests. New public precondition test proves this with valid fixture directories
   and every other required command present. Actual RED exits255: expected explicit
   `SETUP_FAILURE: required command unavailable: rg`, got exit0/misclassified list.
2. `fmonitor2-php-test:latest` was an undeclared ambient local image. The ownership
   boundary test correctly rejected missing-image infrastructure output. Its assertions
   stay unchanged. CI must build this tool image explicitly, separate from the pilot image.
3. Old CSS stress oracle did not witness a change after capture. The initial inode-ABA
   hypothesis was independently retracted: retained descriptors prevent inode reuse.
   Current production returned503 for a witnessed persistent change. Test-only helpers
   now establish the capture point in an isolated Linux container, preserve exact503
   headers/body and retain ordinary between-request replacement200 checks. Root authored
   shipped helpers independently of the reviewer's earlier private diagnostic.
4. Raw log substring4512 also matches legal correlation IDs/ports. The real production
   logger accepts valid hexadecimal4512abcdef00 without domain data. New strict test
   parser permits exact canonical events and known PHP transport grammar, rejecting all
   other stderr (including numeric/SQL/path/bracket leaks); production logging is unchanged.

At62fd8ba, six test paths changed in addition to the original eight: object-list test,
SHLZ asset test, `PilotSafeAuthorizationLog.php`, `ShlzManifestCaptureProbe.php`,
`shlz_manifest_capture_witness.php`, `quality_graph_ci_setup_001_test.php`.
RED record: `quality-graph-governance-current-red-v5-2026-09-08.md`, authoragent:/root;
metadata contains the complete14-path baseb5 set and acknowledges coauthorbootstrap_contract.
Raw RED: `ci-setup-red-v5-local.log` SHA
`a2fcdd6b8a1b10609bee9282d5ee285d0af23dbd7bf3e441c690024723139eb4`.
The downstream absent-tag image build case was not reached before this intended RED.
New CSS container wrapper ran3times successfully, each capturing on attempt1;
private `css-witness-formal-smoke.log`. No product code was changed.

## Next bounded work

1. Obtain independent Gate3 for REDv5/test repairs. Last request to bootstrap_review
   failed at the model service with capacity error; NO Gate3v5 record/approval exists.
   bootstrap_contract also encountered a Codex-service HTTP403. These are agent-service
   failures, distinct from GitHub CI failures. Do not invent approval or switch models
   contrary to the owner instruction: agents gpt-5.6-sol/low, independent reviews.
2. Review concern to resolve before approval: qcsRun in the new CI setup test drains
   stdout and stderr sequentially and has no timeout. A verbose Docker build may block
   on its stderr pipe. This is a code-review concern, not an observed build result;
   correct the test runner with bounded concurrent draining if confirmed. A test-byte
   change requires a new append-only RED binding, not editing committed REDv5.
3. After approved tests, implement only the tooling fixes: repository-owned PHP8.5
   test Dockerfile with mysqli/pcntl/util-linux; `make test-tools` with default required
   tag and isolated TEST_TOOL_IMAGE override/source label; CI installs rg and calls
   test-tools; run.sh fails clearly before classification when rg is absent.
   Preserve the current wrong-owner test and all runtime/product files.
4. Focused checks first, then fresh GREEN/Gate5/superseding receipt, exact final verify
   and actual PR37 baseline/graph rerun. Old receipt-v3 remains immutable. Private
   producer-v3 is for the OLD eight-test binding; adapt a new private version for the
   new RED author/test set/review/Green/code-review paths and supersedes:v3.
5. Record actual parity and any remaining limitations. Do not label setup failures
   domain RED, a skipped verify job a propagated failure, or fixture tests publisher parity.

## Separate publisher decision still required

Main lacks publisher topology (Contents API404 confirmed). Official workflow_run
behavior requires the file on default branch. Minimal runtime topology is TWO files:
publisher workflow and exact `quality-graph.yml`; no mass pilot-history merge is needed.

More importantly, pinned actioncaf5366... runs locked qg-github0.1.7, which unconditionally
creates/updates a PR comment before final check publication. Approved permissions are
only actions:read/contents:read/checks:write. Independent offline denial proof returned403
on comment POST and zero final check writes. This is not a real unauthorized API call.
Private `publisher-permission-denial-offline.json` SHA
`8e29fd9425b9c730d78533b045eddf4e8021abecea49bd53c549778e359948d7`.

Successful phaseB needs a reviewed specification decision for check-only publication
or a suitable pinned runtime. Do not grant issue/PR write, patch away failures, change
runtime pins, merge or declare phaseB complete under existing approval. The prior Gate5
explicitly left that phase unclaimed. Prepare a concrete proposal before asking for a
new decision; Linux fixes above can proceed within existing authorization.

## State preservation

Private evidence root: `~/.local/state/fmonitor2/quality-graph-current-20260908/`.
Restart snapshot and committed test-repair patch: `restart-20260908/`.
No uncommitted CI changes remain. Main's unrelated untracked files remain untouched.
The latest get_goal returned status BLOCKED, not COMPLETE; objective is preserved.
Do not replace or declare it achieved. On resumption inspect actual goal state and
continue from the owner's instruction with a fresh blocked audit where applicable.
