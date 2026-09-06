# Конкурентный повтор и TCP-only: фактические gates

Первый пакет: вернуть идентичному конкурентному исправлению frozen REPLAYED до STALE; завершение — unchanged worker oracle, затронутые проверки и независимый Gate5. Оценка 30–45 минут, предел 60 минут. Следующий пакет TCP-only: исключить ambient Unix socket; оценка 30 минут, предел 45 минут. При превышении пересмотр подхода обязателен.

## Конкурентный повтор

Implementation `8415d1f67aab360076644115ba5da0ee2101ad8a` реализует только минимальную коррекцию, разрешённую `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-DATA-WORKER-CANONICAL-PATH-001-v1.md`: при step11 current drift повторяется тот же fingerprint; validated winner возвращает frozen replay с loser request echo, miss сохраняет STALE, malformed/unavailable даёт persistence failure. No-drift path без нового чтения; cleanup до return, до IDs/finalize.

Финальный focused archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-race-correction-bo_tdggm`.
Пять commands завершены: worker transport FAIL, lineage/values/attempt-clock/architecture PASS. Идентичные гонки прошли, включая прежний line41 и isolated full inventory/retry. Новое падение — isolated different race в Support line53: ожидается release log, actual empty. Эта same-PDF гонка отклоняется step11 до lease, как уже одобрено для основного companion test; остальные terminal/audit/domain/blob значения совпадают. FAIL сохранён, полного GREEN нет.

Unapplied exact patch `original-isolated-race-step11-oracle-v1-2026-09-06.patch`, SHA256 `a0f2d19a024c4e6a4e232940e7f038320f4a5f9f00bfb223f0bef387ffd52a28`, меняет только один logs expected value и текст assertion. Before/after hashes в isolated-patch.json. Требует отдельного independent Gate3; до него active helper не изменяется. Реальная post-finalize release failure остаётся в DATA-WORKER-001.

## TCP-only

Exact v0.7/parentv72 Gate1 APPROVED: `original-data-transport-gate1-review-v07-2026-09-06.md`. Новый public-factory test с bounded task-owned Unix listener и fixed child protocol демонстрирует intended RED: direct sensitivity принимает1; localhost/LOCALHOST/LocalHost каждый принимает1 вместо0. Production host validator ещё не исправлен.

Retained RED archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-tcp-red-eg6kpnwq`.
Evidence pins HEAD/source/test hashes и raw log. Preliminary duplicate-require setup diagnostic исправлен до retained run; он не считается intended RED. Pending Gate3 теста, затем минимальный GREEN и independent Gate5. Socket count не доказывает password read ordering; это отдельное обязательное source proof.

Эта запись не утверждает combined command, VERIFY_OK, CI или готовность портала.
