# Design: полный реворк расчётов ОТиЗ v2

## Context

Канонический Yii runtime уже хранит snapshots и closure facts, но draft финансово не нейтрален в терминологии UI, settlement owner не моделирует recipient obligations, а object register выводит агрегаты от расчётов. Требуется чувствительное изменение денежных семантик без второго ledger и без writers в templates/rapid-pilot.

## Goals / Non-Goals

**Goals:** единый transactional owner; immutable calculation revision; exact claims; recipient obligations; full-portfolio economy; old-debt payment path; reversible audit facts; server-derived XLSX; #257 fail-closed admission; один UI и один PR.

**Non-Goals:** producer #257, массовая миграция legacy-истории, частичные выплаты людям, возврат реально перечисленных денег, новый SPA/BI/HR-каталог, изменение нормативных ставок, merge/deploy.

## Decisions

1. `MariaDbOtizSettlement` остаётся единственным public application owner. Новые команды принимают operation id, expected revision и actor; replay receipt и business uniqueness проверяются внутри одной DB transaction.
2. Draft хранит immutable revisions и редактируемые decision/deduction facts. Только acceptance создаёт claims и recipient obligations. Payment закрывает обязательства snapshot целиком отдельным fact; reversal деактивирует payment fact, но не claims.
3. Claim identity строится из устойчивой identity исходного права, object/case identity и entitlement kind; report date и новая версия накопительного snapshot сами по себе не создают новое право. Acceptance блокирует object baseline и повторно сверяет draft с активными claims, поэтому заранее созданный пересекающийся draft обязан обновиться, а доказанный прирост остаётся доступным.
4. Суммы — integer cents/basis points. Распределение использует largest remainder со stable employee identity. Исключение уволенного меняет payout allocation, но не исходный contribution.
5. Object economy начинается с canonical object catalog/overlay, затем batched joins агрегатов. Unlinked legacy totals показываются диагностикой и не входят в payable amount.
6. Admission — один owner-level интерфейс с `decision(objectId, phase, snapshotRevision)`, общий для acceptance, payment export и payment. UNKNOWN/blocked, смена incident/source относительно сохранённого основания и draft, подготовленный до такой смены, запрещают действующую операцию; historical read остаётся доступен. Resolution требует принятого replacement, а не reactivation старого snapshot.
7. Workbook читает только сохранённую revision. Draft, current-payment и historical modes различимы. Пользовательские строки принудительно записываются как text; external links/macros запрещены.
8. Старые URLs делегируют единому list/read owner. UI filters/grouping/search/page никогда не определяют mutation scope.
9. Replacement создаётся отдельным draft с `replacesCalculationId`. Он может включать claims исходника только в изолированном preview; исходник остаётся активным до явного acceptance. Acceptance replacement в одной транзакции проверяет полноту, admission, отсутствие payment/dependencies, освобождает старые claims и принимает новые; любое отклонение оставляет исходник без изменений.

## Data Model

- calculation + revision + audit/deletion/cancellation/replacement links;
- object rows, entitlement claims, original contributions, payout allocations;
- object/personal deductions and separated deadline reductions;
- employee decisions with workforce snapshot;
- recipient obligations and payment/reversal facts;
- operation receipts and admission snapshots.

Existing accepted snapshots lacking complete claims remain historical read-only and cannot be paid without a supported compatibility proof.

## Verification

Focused deterministic oracle tests cover M01–M24 and acceptance matrix E/D/C/H/P/A/R/I/U/X/S. DB tests cover fresh/upgrade schema, transactions, races and replay. HTTP tests cover authorization/CSRF/direct routes/revision/export. Browser tests combine main flows at desktop/narrow widths. XLSX is parsed by an independent reader. Full local suite remains prohibited; planner-selected exact-source CI runs once.

## Risks / Trade-offs

- Legacy field semantics may be ambiguous: preserve raw meaning in a verified field map and fail closed for financial interpretation.
- #257 producer may be absent: ship/test the real consumer contract and report producer as UNKNOWN, never GREEN.
- Large schema evolution: additive migrations and compatibility reads avoid destructive backfill.
