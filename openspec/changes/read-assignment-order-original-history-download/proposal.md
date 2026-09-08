## Why

Parent original HTTP требует production read-only history/download seam. Текущий
EvidenceReader — диагностический verifier API с private inventories; его нельзя
выдавать пользователю. Нужна owning-module граница, сохраняющая immutable revisions.

## What Changes

- ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001: trusted in-process history page
  и подготовка конкретной revision PDF с проверенными size/hash до выдачи bytes.
- Native selected-original source/backing proof, stable revision-number cursor,
  проверка private file под existing digest exclusion, отсутствие writes.
- Consumer обязан проверить actor/scope. Parent HTTP остаётся целиком открытым
  до всех ролей, включая actual applied-engineer scope, и полного VERIFY.

## Capabilities

### New Capabilities
- `pilot/original-history-download`: production history и immutable prepared PDF.

### Modified Capabilities

## Impact

Owning module AssignmentOrderOriginal, native tests. Oracle: approved original
upload/correction/history preservation, selected binding и storage layout. Нет
verification-reader dependency в runtime, новых grants/DDL, HTTP/application/opening
или migration version. Политика повторного применения даты не влияет на чтение
уже принятых revisions; она остаётся NEEDS_GRILL у parent application change.
