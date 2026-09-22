# YII2-CONSTRUCTION-CONTROL-SERVER-FILTERING-001

## Простыми словами

Поиск и фильтры очереди работают по всем доступным объектам, а не только по пятидесяти показанным. Счётчик, страницы и строки описывают одну выборку. Offline-синхронизация, фото и процесс завершения не меняются.

## Public seam и actor

Actor — Yii session user с exact `construction_control.read`. Seam — GET/HEAD `/pilot/construction-control` с `ownership`, `query`, `completed`, `page`. Default: mine, empty query, completed=0, page 1.

## Нормативное поведение

1. Server SHALL применить access, current native ownership, case eligibility, query и completion до COUNT/LIMIT/OFFSET. COUNT и rows используют один predicate и stable order.
2. `mine` — только текущее самостоятельное native-закрепление actor; historical order/legacy author не fallback. `all` снимает только ownership predicate.
3. Trimmed query ищет case-insensitive literal substring в displayed effective address или registration number; `%`, `_` и escape literal. После #226 select и predicate MUST использовать его единый effective-details mechanism.
4. Completed — existing canonical `pto_act AND declaration`. Default excludes completed; `completed=1` includes active and completed. PTO-only remains excluded.
5. URL/controls reflect filters; pagination preserves them. Filter change/clear submits page 1. Refresh identical. Invalid scalar/enum/page or query >160 Unicode chars returns 404 without facts.
6. Empty set: total 0, pages 1, no rows, visible empty state. Out-of-range page is controlled and never substitutes another page.
7. Current appearance, shipment indicator, checklist/photo navigation, IndexedDB sync and prefetch remain. JS does not filter server rows or rewrite total.
8. GET/HEAD are read-only; no facts, assignments, history, local operations or schema change. No migrations.

## Независимые примеры

- On 52 eligible objects mine object `OWN-TAIL` beyond all/page1 appears on mine/page1.
- `query=OWN-TAIL` finds it; combined mine+query gives the same one row; foreign query is empty.
- Completed neighbor appears only with completed=1; PTO-only never appears.
- Pagination retains filters; changing a control does not submit old page=2.

## Смежные границы

Authorization, safe errors, assignment fail-closed, preopening lineage and canonical completion are inherited. Schema, writers, replay, concurrency, backup/restore and deployment are inapplicable because this read-only slice stores no fact. If #226 merges first, corrected address/regnumber MUST participate in SQL search through its effective-details mechanism.
