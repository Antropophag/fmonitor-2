## Context

См. `proposal.md` — Why и delta spec. T05.1 уже добавил узкий authoritative FAST class в verification planner. Однако public `harness.py prepare` принимает OpenSpec-local `verification-input.json`, package context считает `openspec/changes/**/tasks.md` lifecycle input, а process text говорит, что каждый slice создаёт structured OpenSpec change. Поэтому mechanical propose boundary расположен не в OpenSpec CLI, а на стыке verification input → planner plan → harness package, до выбора role package.

До кода проверены три historical cases:

| case | evidence | текущий обязательный lifecycle |
| --- | --- | --- |
| A: presentation maintenance candidate | #151/#160 replay, existing presentation requirement и public HTTP oracle | issue → новый executable spec → proposal + delta + design + tasks → verification input/plan → RED → Gate 3 → implementation → final review → exact-source CI → delivery record; historical candidate был STANDARD до T05.1 |
| B: small-looking semantic change | #153 Slice A / `require-semantic-integration-closure` | issue → executable policy spec → полный OpenSpec → verification plan → RED → Gate 3 → implementation → final review → exact-source CI → delivery record; CRITICAL из-за policy/semantic integration closure |
| C: sensitive change | #132/#156 / `protect-sensitive-offline-fast` | issue → executable security policy spec → полный OpenSpec → verification plan → RED → Gate 3 → implementation → final review → exact-source CI → delivery record; CRITICAL для offline/session/cache/security semantics |

Для historical T05.1 #160 replay test сначала доказывает current planner FAST для `feedback-confirmation.php` с `FEEDBACK-001`/registered HTTP oracle, затем materializes disposable before/after artifacts одной и той же maintenance identity. Before содержит 9 mandatory created lifecycle artifacts; existing executable regression переиспользуется и не считается созданным. After содержит compact record и final review. Test вычисляет bytes/chars этих exact fixture bytes. Это proxy старого полного lifecycle, не оценка tokens.

## Goals / Non-Goals

**Goals:**

- Добавить один детерминированный routing decision после authoritative planner result и до role package construction.
- Переиспользовать verification input/package и существующий external delivery record как compact record; repository delivery note остаётся human-readable projection/closeout, не вторым registry.
- Bind canonical references по path+SHA-256 и проверять freshness в prepare/state/apply.
- Сделать public route доступным без OpenSpec directory: verification input получает собственный стабильный task path/CLI input, а harness не предполагает proposal artifacts для FAST maintenance.

**Non-Goals:**

- Менять rules, coverage или implementation T05.1 classifier; решать semantics через LLM; автоматически писать OpenSpec.
- Менять full lifecycle, Gate 3 или CI для STANDARD/CRITICAL; мигрировать history; вводить новый documentation platform.
- Менять product/runtime/persistence; `rapid-pilot` не является target или adapter этого tooling slice.

## Decisions

1. **Routing owner — harness поверх planner result.** Planner остаётся владельцем `verification_lane`; harness применяет закрытые eligibility assertions только после `FAST`. Альтернатива встроить lifecycle semantics в classifier отвергнута: это расширило бы T05.1 и смешало risk classification с artifact routing.

2. **Typed maintenance declaration во входе.** Existing verification input расширяется полями lifecycle intent, `semantic_change`, canonical references и regression reference. Для non-FAST/legacy input default — `OPENSPEC_REQUIRED`. Альтернатива вывести semantics из paths/текста/LLM отвергнута как небезопасная и недетерминированная.

3. **Closed escalation assertions.** Любой sensitive/semantic/policy boundary из authoritative plan, non-FAST lane, missing/conflicting reference, отсутствующий explicit false или owner-decision marker запрещает shortcut. Existing planner reasons/boundaries переиспользуются; новый classifier/registry не создаётся.

4. **Record integration.** Prepared package и retained external record получают `lifecycle` section; state проецирует route/freshness/disposition. Review/CI references заполняются существующими harness evidence events/closeout update seam либо остаются explicit `PENDING`/`UNKNOWN`. Альтернатива отдельный FAST manifest отвергнута как второй registry.

5. **Freshness by content digest.** Canonical refs — repository-relative regular files из разрешённых canonical roots, каждая получает SHA-256. Package state recomputes them; executor-role prepare запрещает stale active binding до root rebuild. Source digest сам по себе недостаточен: lifecycle-only files могут иметь отдельную executable identity, а requirement freshness должна быть объяснима по конкретной ссылке.

6. **Historical replay as disposable fixture.** Test materializes minimal repositories/candidates based on preserved #151/#153A/#132 shapes and invokes public prepare/state routing. Measurement counts an explicit closed list of mandatory created artifacts and byte lengths. It reports review dispatches and proposal-confirmation stops separately.

7. **Architecture impact.** Ownership остаётся в `tools/delivery`; `tools/verification` предоставляет authoritative plan и не зависит от lifecycle routing. Architecture check получает только focused tooling regression/inventory registration, если требуется. Persistence owner отсутствует; никаких schema/backup/restore/runtime changes.

## Risks / Trade-offs

- [Caller falsely declares `semantic_change=false`] → shortcut всё равно требует planner FAST, closed eligible boundary, canonical references и negative escalation; final reviewer проверяет claim. Не доказанное состояние fail-closed.
- [Canonical requirement path формально существует, но неоднозначен] → multiple refs разрешены только без conflict marker; ambiguity/owner decision routes normal discovery.
- [Package prepared до изменения spec] → per-reference digest recomputation blocks state/apply.
- [Compact record разрастается] → хранить ссылки/digests/status, не acceptance prose и не full logs.
- [Legacy inputs ломаются] → отсутствие lifecycle declaration сохраняет `OPENSPEC_REQUIRED`; explicit OpenSpec route работает как раньше.
- [Bytes proxy сравнивает разные semantics] → replay фиксирует один и тот же existing requirement/regression/review/CI guarantee и отдельно маркирует telemetry `UNKNOWN`.

## Migration Plan

1. Ввести additive schema и fail-closed default; existing OpenSpec inputs продолжают normal route.
2. Добавить public fixture cases A–N и получить intended RED.
3. Реализовать package/state freshness и compact record projection.
4. Выполнить focused checks, independent reviews и один exact-source CI run.
5. Rollback — удалить additive routing fields/logic; historical OpenSpec artifacts и normal lifecycle не требуют migration.
