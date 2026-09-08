# ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 v0.6 — независимый review typed flow

Дата: 2026-09-05.  
Reviewer task: `/root/selection_v04_readiness`.  
Reviewed commit: `af42f053a5a14c44eee381352ec2e729ea57387e`.  
Bounded verdict: **APPROVED для v0.6 typed-flow delta**.  
Full Gate 1: **NOT READY FOR RED** из-за ранее зафиксированных P0 dependencies.

Это независимый ограниченный rereview только трёх findings из v0.5:
invocation clock placement, typed rollback cause и exhaustive
stage→decision→UoW→external mapping. Reviewer не автор reviewed artifacts.
Review не утверждает весь executable candidate, не разрешает Gate 2 и не меняет
code/tests/spec/OpenSpec.

## Exact reviewed hashes

- `specs/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001.md`:
  `1535240c50376ee6eb90102725cb6a9def41288127f8280945d72677ff567f1b`.
- OpenSpec design:
  `3b73161fcc34cf8017358f72ec41bca71eb63f0a791a820049b5b4d5cf6d7e19`.
- OpenSpec proposal:
  `7e77908a8e58f609c43bec1e457a9915a9d0e0aff2f099d5c11950e93d054c7a`.
- OpenSpec delta spec:
  `7dcc635034d558af4996a9ecc45ef4abd644ea6d4f6e9b11e1797cda5b829e30`.
- OpenSpec tasks:
  `f3dedcbb10be3ce131512f801dcdb053065a3f9674956acf880df956038b71a6`.
- v0.5 bounded review:
  `414e59c9119a5130d63b6e93570a00eab9f9313da81c188bf4ab89e2c0f659b1`.
- v0.4 independent review:
  `7df4a3e6df3b688330de08a4be30b98c8f3b069f51c2f27d7b2915378f721acb`.
- Writer/reader inventory:
  `86896f3f16faf5a9f33276b8dc975cbb0d04c013044c80d7b444262927d1f299`.
- Owner decision record:
  `915d4f4ac14906ce3cee9cb2b6fa8dc02fb990a5501063555ec9ec5e3b757e0c`.

## Findings disposition

### v0.5 P1: timestamp до persisted terminal outcome — RESOLVED

Precedence теперь явно получает один immutable invocation-owned instant перед
первым outcome, которому требуется audit/terminal fact. Authorization denial и
changed-request conflict получают instant непосредственно в своих ветвях;
доказанное отсутствие terminal request получает его до object/business checks.
Matching replay, authorization/terminal lookup unavailable и invalid shape не
читают clock. Clock NOT_FOUND/unavailable/malformed даёт
`failed/dependency_unavailable` до любых writes.

Все `attemptedAt`, `terminal_at_utc`, `selectedAt`, `occurredAt` и `allocatedAt`
одной invocation равны этому instant; selection date выводится из него. Unknown
recovery не читает clock второй раз и использует сохранённое значение только
если требуется новый denial/conflict audit. Это закрывает прежнюю невозможность
построить ранние audit/terminal DTO и сохраняет observable zero-clock replay.

### v0.5 P1: payload-free rollback — RESOLVED

`SelectionRollbackCause` закрывает ровно dependency unavailable, persistence
failure и allocation capacity exhausted. И callback decision, и UoW result
переносят cause типизированно; getters других variants возвращают null.
Dependency/state/case failure теперь доходит до
`failed/dependency_unavailable`, storage failure — до
`failed/persistence_failure`, capacity boundary — до nonretryable exhaustion без
mutable closure capture или exception side channel.

`requestRace` и `observedTerminal` сохранены отдельными variants. Commit принимает
только selected/rejected/conflict exact staged result; replayed/failed нельзя
передать как commit payload.

### v0.5 P1: неполный stage/UoW mapping — RESOLVED

Раздел 9.1 исчерпывающе связывает:

- accepted/terminal STAGED receipt с exact commit result;
- PERSISTENCE_ERROR, invalid generated ID и wrong receipt shape с confirmed
  persistence rollback;
- REQUEST_RACE с отдельным rollback outcome и fresh authorized lookup;
- typed dependency/capacity causes с соответствующими public failures;
- pre-stage observed terminal с read-only release;
- unknown commit или неподтверждённый rollback с outcomeUnknown recovery.

Stage adapter больше не владеет commit/rollback. UoW проверяет соответствие
decision единственному stage и staged terminal result. Independent audit writer
имеет собственную transaction, проверяет generated ID до commit и различает
committed, confirmed rollback и unknown outcome. При unknown acknowledgement
нет повторной audit mutation. Внутри bounded flow нового противоречия не найдено.

## Consistency checks

- Request race использует уже полученный invocation instant, не создаёт второй
  allocation и проходит ту же authorized fresh-lookup mapping, что unknown
  recovery.
- Matching stored selected возвращается как `replayed`; matching stored
  rejection/conflict возвращает exact stored terminal outcome без нового clock
  и audit, что согласовано с result factories v0.5.
- Invalid generated receipt не признаётся staged/committed success. Confirmed
  rollback и unknown acknowledgement остаются различимыми.
- Closed flow не меняет owner-approved `replace_pending` policy, composition
  facts, authorization ordering или no-safe-log boundary.

## Bounded verdict и Gate summary

Все три v0.5 typed-flow findings исправлены согласованно. Для exact v0.6 bytes
bounded verdict — `APPROVED`; дополнительных typed-flow findings нет.

Это не full Gate 1 approval. Candidate по собственному разделу 14 остаётся
`NOT READY FOR RED`, пока отдельно не утверждены exact migration/backfill/
receipt и prefix contract, all-writer/N-1 cutover, original-reader amendment и
same-identity optional-render contract. Их подробный rereview не входил в эту
задачу. После закрытия P0 dependencies нужен fresh independent review всего
Gate 1 batch at exact hashes. Срок не отменяет gates и не сужает scope.
