# DELIVERY-EXECUTION-107-I1

## Простыми словами

Harness должен честно различать состояние кандидата, допуск к публикации и
авторизацию действия. Этот самостоятельный срез не содержит container execution,
snapshot/review orchestration или resume/closeout.

## Public seam

Actor — owner или delivery agent. Public CLI: `harness.py admission`, live/replay
`state`, `wait`, `prepare-merge`, `run` и `report`.

Live `state` возвращает 0 после корректного status JSON даже при blocked readiness;
это diagnostic read, а не approval. Admission, replay state, wait и merge
preparation возвращают ненулевой exit при отказе.

Native GitHub evidence MUST быть связано с exact repository, PR number, head,
base, workflow, run id и attempt. Push/manual run, отсутствующая или чужая PR
association и неполный job set MUST давать UNKNOWN/отказ. Full, harness и docs
mode используют поставляемую policy ожидаемых SUCCESS/SKIPPED jobs; дополнительные
publisher jobs не заменяют обязательные результаты.

Publication readiness MUST требовать exact GREEN preflight и независимые
APPROVED Gates 3/5. Merge readiness дополнительно требует exact SUCCESS CI и
неизменившиеся head/base. Authorization проверяется отдельно. Owner exception
MUST быть ограничено actor/action/head/policy/reasons, сохранять original failures
и не покрывать CI, reviews, binding или race. Неизвестное enforcement MUST
нормализоваться в `ENFORCEMENT_NOT_CONFIGURED`; автономный merge запрещён.

Runner record MUST отдельно сохранять raw exit, `command_verdict` и applicability
`APPLICABLE|STALE|UNKNOWN` с причиной. Diagnostic exit 0 MUST иметь verdict
`DIAGNOSTIC`, не GREEN. `report --task --run-id --candidate` MUST одинаково
фильтровать records и event aggregates; отсутствующая telemetry остаётся UNKNOWN.

## Acceptance

- `AC01-I1`: все перечисленные команды используют один fail-closed evaluator и
  возвращают наблюдаемые поля `ci.status`, `publication_ready`, `merge_ready`,
  `action_authorized`, `enforcement`, `reasons` без подмены чужим или устаревшим
  успехом.
- Срез не подтверждает и не реализует AC02–AC12 прежней объединённой постановки.
