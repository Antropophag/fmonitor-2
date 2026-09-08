# ATTEMPT-AUDIT: unapplied compatibility patch v1

Статус: UNAPPLIED / independent Gate3 required. Production не меняется этим patch.

Patch `original-attempt-audit-compatibility-v1-2026-09-06.patch`, SHA256
`f2fdb97f00eab364867f3e6af782ca0b40e972afa7b1e9a5553c04fa2df6b9c4`.
Пять real mismatches сохранены в completed focused archive
`/Users/antropophag/.local/state/fmonitor2-verification/original-attempt-audit-focused-green-vd0n3nsc`
на clean `dd15fe23c6fa2ea4901ee18e6e580a1ff2862137`.
Новые public/native/schema suites там проходят; старые ожидания остаются FAIL.

## Изменения exact candidate

- Denial больше не является deferred-policy исключением из clock: одна попытка
  теперь получает один instant, прочие early/replay paths сохраняют0.
- Lifecycle сохраняет весь прежний transcript и добавляет только обязательный
  последний diagnostic: file_failure для default unavailable audit writer,
  submission для persistence outcomes. Release/abort/close/commit/audit counts,
  порядок и все результаты неизменны. Logger-failure isolation сохраняется.
- Отдельный failed-terminal-audit case ожидает ровно один новый log:terminal.
- Authorization validation fixture получает explicit confirmed test audit port
  для двух one-shot denied examples; прежние denial/authorization/no-read
  expectations сохранены, audit count и caller/mode/request дополнительно проверены.
- Canonical runner oracle соответствует frontier13: полный40-table каталог,
  ASCII/utf8mb4 по старым независимым TEST-side original-v2 literals, audit-v3
  index/FK/CHECK delta, точные current capability-v5 literals/name. Все прежние
  near-match/operator/duplicate/extra-literal cases и byte-identical no-op
  assertions остаются, теперь на current completed-v5 schema. Standalone v3/v4
  migration/authorization tests не изменены. FK/CHECK metadata сортируется как
  полный multiset без удаления элементов или weakening boolean semantics.

Новый TEST-only `ProductionOriginalAuditCatalogV13` использует literal TEST-side
V2 manifest; production migration SQL/expected methods не импортируются. Boolean
normalizer взят из прежнего independent schema test с точечным сохранением
quoted space/backtick; собственные exact literal controls исключают обнаруженную
ошибку production normalizer. Root protected E2E и его helpers не меняются.

## Evidence

Final candidate archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-attempt-compat-v3-k5rspwyx`.
`manifest.json` pins source2470126 and before/after hashes всех6 files;
`evidence.json` SHA256
`05e641aaf4bdbe3dccf7390221048d6a51c6d1adeae1aae28b5058296097e521`.
Canonical runner candidate PASS, source bytes unchanged during run.

Четыре остальных after hashes byte-identical первому candidate:
`original-attempt-compat-v1-k36bi1g3`; его completed evidence показывает все4 PASS
на sourceDD15. Их PHP behavior dependencies не менялись в quote-only2470126;
финальный root regression после exact patch будет на одном clean SHA.

Первый canonical candidate FAIL был только metadata tuple ordering; он сохранён.
V2 canonical PASS после полного sorted FK comparison. V3 добавляет quote-preserving
TEST normalizer и его sensitivity controls; canonical PASS подтверждён заново.
Ни один FAIL не превращён в skip/allowed failure. Исторические evidence не стираются.
