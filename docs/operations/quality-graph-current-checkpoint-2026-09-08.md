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

## Продолжение после CI/Linux restart

Owner возобновил работу по handoff CI/Linux. Stand4990cf1 и MariaDB healthy,
данные/volumes/runtime не менялись. `get_goal` в новой сессии вернул `goal:null`;
новая цель взамен сохранённой не создавалась, completion не заявлен.

CI branch: `codex/quality-governance-current-20260908` в соседнем worktree.
Reproduced qcsRun sequential-pipe deadlock на1MiB stderr; исправлено concurrent
nonblocking чтение с deadline. REDv6 `8519c555bc22fd0f7379c40f149e1316fe1a21ec`
содержит полный14-test set и actual exit255 на отсутствующем rg.
Независимый `agent:/root/linux_test_review` дал Gate3v6 APPROVED; commit535800a.

Implementation/GREENv4 `3e1bfbb` добавляет только CI test tooling: test-only PHP
Dockerfile, make test-tools, явную установку ripgrep и precondition runner.
Focused CI setup, governance, architecture, object-list, CSS, original boundary
PASS. Один первый ручной CSS запуск без test-local DB env был setup FAIL;
исправлен invocation, повтор PASS, оба лога сохранены. Production не менялась.

Exact clean checkout `/Users/antropophag/code/fmonitor-2-verify-stabilization`
запустил `make verify` на3e1bfbb; raw log `verify-3e1bfbb-001.log` в private root.
Итог ещё ожидается. Независимый `agent:/root/linux_code_review` выполняет Gate5;
нового Gate5 approval/receipt-v4/remote rerun ещё нет, receipt-v3 неизменна.

В source включено конкретное pending proposal check-only inline publisher:
`docs/operations/quality-graph-check-only-publisher-owner-proposal-2026-09-08.md`.
Это не изменение publisher и не разрешение новых permissions/pins/merge.
Owner decision запрошено отдельно; до ответа зависимая implementation не начинается.
Owner вопрос о классификации verify был только вопросом: объяснены9stages,
неточная unit/db классификация и отсутствие общего type/mutation analyzer;
реорганизация suites не выполнялась и не добавлена в текущий repair scope.


### Linux repair local chain завершена; real CI повторён

Exact3e1bfbb039b27fb99ffbc815f03417d8f40a6f1f получил literal VERIFY_OK:
все9stages PASS,07:00:16Z..07:21:18Z,1261.37s, private verify-3e1bfbb-002.log
SHA21a9c5e5c5d9e887b192d09c3d3d77864af86a9f7c94863a1d9c99baa398ba9f.
Предыдущий001 и concurrent reviewer run остановлены из-за общей testDB;
их logs неизменны, ни один не считается PASS. Тестовая БД после002 остановлена.

Независимый Gate5v4 APPROVED committed49a88b6. Новая immutable receipt-v4
committedeacc4d42b8690f2529117e50aee7fd96658af566 supersedesv3;
реальный checker PASS receipts=1. Raw evidence в CI final-verification record.
Обе разрешённые branch refs fast-forward опубликованы наeacc4d4; main/PR10/
protection/publisher permissions/runtime не менялись. PR37 всё ещё OPEN/DRAFT.

Новые same-head runs: baseline34199261119 и graph34199261130, attempt1,
оба выполняются. Graph validation и SSD/TDD evidence PASS; обе dependency setup
завершены, оба full harness запущены. Synthetic mergea46528ff7c59bf7f57b91aa721ceda5c61c93d69
сохранён в local refs/qg-parity-archive/20260908/positive-merge-a46528ff,
его tree равенeacc4d4. Final parity ещё не объявлена.

Три отрицательных fixture подготовлены и locally дали ожидаемые failures:
graph-driftd610400c, empty-receipt-roote289609f, post-review-spec30f2f073.
Local archive refs v4-* и private generator-v3/evidence сохранены. Ни один negative
fixture ещё не опубликован. Forced actual verify-stage failure требует отдельного
reviewed fault-control seam; SKIP не считается propagated failure.
Publisher proposal ожидает owner решения, реализация не начиналась.


### CSS fixture Linux failure — исправление опубликовано

Eacc4d4 оба real runs34199261119/34199261130 завершились FAIL только db-test.
Bootstrap запускал CSS child, который вернул exit0 с cleanup warnings Permission
denied root-owner-allowed. Остальные8stages PASS. Причина — test fixture ownership
helper: undeclared mariadb:10.11 и early return по Docker stderr после возможного
успешного chown. Standalone cached CSS PASS не отменяет этот реальный defect.

Test-only repair a18d798, независимый G3v7CHANGES_REQUESTED (5aeea65), уточнение
6b30c1b: restoration owner/modes завершается до diagnostic assertion. Private
Linux реальные chown/nonroot probes: exact old RED255, fixed quiet/noisy-provision/
noisy-restore PASS. HTTP assertions и строгий stderr bootstrap сохранены.
G3v8 APPROVED f430988; final CSS и полный bootstrap focused PASS, logs сохранены.
Нового full LOCAL verify не было: актуальный полный прогон будет в GitHub.

GREENv5 e29b4377d17fd511614170675775bf32536a0db0 честно имеет пустой latest
implementation delta; reviewer отдельно проверил cumulative test-only repair.
G5v5 APPROVED160721f. Receipt-v5 supersedesv4, обе старыеv3/v4 неизменны.
Checker PASS на9f530017ab769de4e6e1647cadb990281e34c0e9. Обе разрешённые ветки
fast-forward опубликованы на9f53001; PR37 OPEN/DRAFT, main/PR10/protection не тронуты.
Новые actual runs: baseline34202284141 и graph34202284144, head9f53001, ожидаются.

Рабочий stand4990cf1 сохраняется. Тестовая DB остановлена. После текущего CI fix
следующей задачей владелец назначил issue25 (GitHub body/title обновлены и readback
подтверждён); reorganization ещё не реализуется. Publisher proposal по-прежнему
pending, owner approval не получено. Negative fixtures v4 остаются local-only и
не подходят автоматически для новогоv5 receipt chain.
