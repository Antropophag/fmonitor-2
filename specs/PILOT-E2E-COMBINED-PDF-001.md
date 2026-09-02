# PILOT-E2E-COMBINED-PDF-001 — one combined assignment-order PDF in golden journey

Статус: **DRAFT / Gate 1**  
Версия: **v1**  
Дата: **2026-09-02**

## Простыми словами

Golden journey скачивает один PDF, внутри которого есть и распоряжение, и
приложение. Старые две HTML-ссылки больше не являются public contract. Ошибка
чтения PDF не должна менять процесс или раскрывать filesystem детали.

## 1. Controlling amendment

Нормативный journey/route/artifact/failure/oracle contract полностью задан
`PILOT-E2E-FLOW-001 v0.5`, section 12. Этот spec фиксирует самостоятельную
traceability slice к owner decision and `ARTIFACT-STORE-001 v0.3`.
При расхождении controlling full E2E amendment v0.5 имеет приоритет; оба exact
hash должны быть reviewed/owner-approved вместе.

## 2. Required acceptance inventory

Gate 2 SHALL prove through real HTTP/application seams:

- exactly one `order` artifact, Unicode PDF filename, `application/pdf`, `%PDF-`,
  persisted size/hash/bytes;
- card has one order link and no appendix link/type;
- independent decoded three-page semantic marker/order oracle;
- exact GET/HEAD parity and 403/404/503 mappings from v0.5;
- authorization-first/no-enumeration/redaction;
- separate digest and shard EACCES with restored fresh reload;
- sequential repeat and two-process concurrent reads with full public process/
  artifact/event/counter/storage snapshots unchanged;
- explicit task DB/user/server/session/artifact ownership and attempt-all cleanup;
- RBAC/setup prerequisites proven before artifact assertion.

Expected PDF byte hash MUST NOT be copied from production/current download or
fixed across independent renders. Within one prepared version, expected bytes
are the persisted immutable sequence and SHALL match its metadata hash/size.

## 3. Non-goals

No separate appendix compatibility artifact, PDF visual redesign, renderer
metadata freeze, process command change, RBAC route migration, signing/1С
integration, production data or storage schema redesign.

## 4. Gates and Done

Fresh independent review of this spec plus complete E2E v0.5 amendment and
explicit joint owner approval are required before test edits. Then intended RED,
independent test review, minimal GREEN, focused/full/fresh verification,
architecture and independent code review. This DRAFT does not authorize Gate 2.
