# Current-line representative PR — phase A

[PR37](https://github.com/Antropophag/fmonitor-2/pull/37) is OPEN and DRAFT against
main `2bff0a0e6baaab61679321001c57cbc916609295`; it is a CI experiment, not a merge approval.
Canonical implementation: `d7edbc4185bbd73103915897a819a36a561bd47a`.
Initial positive PR head: `5a09256df11f8a62d965eb932db4cd04a9790207`.
PR API reported synthetic merge `73af27535c50ebbcf3e8a47fa38779c436329fde`.

The canonical reviewed branch is `codex/quality-governance-current-20260908`;
only disposable `codex/qg-parity-20260908` is the PR head. All intentionally invalid
fixture heads and executed merge heads must be archived before any exact-old leased
ref move. The final PR returns to a valid reviewed head; archive refs remain immutable.

## Initial actual runs — in progress

- [Retained baseline34178683041](https://github.com/Antropophag/fmonitor-2/actions/runs/34178683041), pull_request, head5a09256, attempt1.
- [Quality Graph34178683097](https://github.com/Antropophag/fmonitor-2/actions/runs/34178683097), same PR/head, attempt1.

Both clean Linux dependency setup steps completed successfully and both full
repository-verification commands are running. Graph declaration/publisher validation
and SSD/TDD delivery evidence jobs completed PASS. The first two downloaded Resultv0
artifacts were parsed and all provenance fields matched exactly:
repository `Antropophag/fmonitor-2`, PR37, head5a09256, run34178683097, attempt1,
graphDigest `95ab7381b6ce103c5ab3cce6fbb54826cf227470f4a7288894e30d74949ea325`.

- graph-validation: passed; JSON SHA-256 `8cbeb8f784b880c13cd8b921bbe455889c3619480ccbd5c217c22269f1c94f5d`.
- delivery-evidence: passed; JSON SHA-256 `a8766f9b5cea360485986a6f043466c5af24ff971f45faa305403709df7f548c`.

Primary snapshots, artifact JSON and parsed partial proof remain under private
`~/.local/state/fmonitor2/quality-graph-current-20260908/github-pr37/positive-5a09256/`.
Two passed nodes are not a completed graph/full-harness/aggregate result.

## Matrix status at this checkpoint

| Case | Actual baseline | Actual graph node | Trusted publisher/aggregate |
|---|---|---|---|
| Valid lineage/commands | full command running | validation/evidence PASS; verify running | unavailable on current base |
| Forced repository-stage failure | not proved | no CI fault seam yet; a source fault would block at lineage first | unavailable |
| Declaration/generated drift | pending isolated fixture | pending | unavailable |
| Current receipt absent | pending isolated fixture | pending missing_receipt proof | unavailable |
| Committed post-review spec mutation | pending isolated fixture | pending commit_mismatch history-guard proof | unavailable |
| Replayed provenance or omitted expected artifact | supporting local fixtures only | no actual trusted publication proof | unavailable |

The spec's raw hash/stale-spec example and committed post-review mutation are
reported separately: committed source/spec changes hit the stronger required
history guard. A skipped verify job is not a forced verify-stage failure.
PublisherphaseB requires topology onmain; no merge/protection change is authorized
by this experiment, and no missing/skipped result is converted to PASS.

Subsequent completed observations are recorded below without rewriting primary evidence.

## Linux findings и продолжение после restart

Оба initial run34178683041/34178683097 завершились FAILURE на unit-test;
остальные8verification stages PASS. Ранее отмеченный PASS setup step не означал
полноту зависимостей: отсутствовали rg и owned test PHP image. Graph validation
и delivery evidence действительно PASS; полной positive parity пока нет.
Source Linux repair3e1bfbb проходит новый exact-SHA make verify; remote refs
пока остаются прежними. Новая receipt-v4 должна supersede неизменную v3.

Read-only independent phase-A audit `agent:/root/linux_test_review` уточнил
negative fixtures для superseding chain:

- graph-drift: одна M quality-graph.yml, expected generated-drift validation FAIL;
- empty-receipt-root: удалить все receipt JSON (v3+v4), expected missing_receipt;
  удаление только v3 даёт invalid_history, только v4 не доказывает missing_receipt;
- committed spec mutation: expected commit_mismatch от history envelope guard,
  а не заявленный hash_mismatch/stale_spec. Последние остаются local fixtures.

Ни один новый negative fixture ещё не опубликован. Private generator v3 подготовлен,
но не выполнен. Архивирование exact positive/fixture/merge refs и сохранение actual
run provenance остаются обязательными до любых ранее разрешённых leased ref moves.

Forced repository-stage failure по существующим PR workflows ещё не достижим:
source/workflow fault нарушает lineage до запуска verify node. Нельзя записать
SKIP как propagated stage failure. Нужен отдельный reviewed fault-control seam
либо разрешённый способ задания test-only environment; текущий Linux repair
не добавляет его самовольно. Полная phaseA matrix поэтому остаётся незавершённой
даже после будущего positive PASS. Publisher phaseB также остаётся отдельным
незавершённым доказательством по pending owner proposal.

## Actual repaired-setup runs eacc4d4 — FAIL db-test

Same-head eacc4d42b8690f2529117e50aee7fd96658af566/attempt1:
baseline34199261119 и graph34199261130 завершились FAILURE только на db-test,
остальные8verification stages PASS. Graph declaration/evidence nodes PASS.
Обе полные repository команды действительно выполнялись; это наблюдаемая
одинаковая propagation реального db-test failure, а не skipped verify node.
Это не выдаётся за заранее управляемый forced-failure fixture из matrix.

Конкретная причина — inherited pilot_shlz_assets внутри pilot_demo_bootstrap:
child exit0, но cleanup временного root-owner-allowed выдаёт Permission denied
в stderr. Standalone CSS позже PASS. Old helper применял chown через undeclared
mariadb:10.11 и объединял transport diagnostics с mutation outcome; ранний return
обходил restoration. Реальные логи и private Linux real-chown reproduction
сохранены; correction REDv7/v8 и independent review следуют append-only.

Private directory `github-pr37/positive-eacc4d4` содержит baseline-failed.log,
graph-run-terminal.log, exact run metadata, positive merge archive и локальные
negative fixture evidence. Новые negative commits остаются local-only;
PR head не двигался на них. Publisher phaseB не выполнялась.
