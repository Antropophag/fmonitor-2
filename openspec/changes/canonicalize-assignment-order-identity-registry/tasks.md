## 1. Executable specification и Gate 1

- [x] 1.1 Завершить exact registry/receipt schema, source fingerprint, observer/snapshot/deadline contract и literal hashes в ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001; проверить PHP declarations, prefix lengths и OpenSpec strict.
- [x] 1.2 Получить independent technical Gate1 exact hashes; reviewer подтверждает observable scope и отсутствие нового продуктового решения, RED ещё отсутствует. Review: `assignment-order-identity-registry-gate1-review-v01-2026-09-05.md`, exact spec31ffe9a.

## 2. RED и независимый test review

- [x] 2.1 Написать smallest public-seam missing-engine RED с fixed historical IDs/frontier/hash; сохранить intended failure, не setup error. Record `assignment-order-identity-registry-red-v1-2026-09-05.md`.
- [x] 2.2 Добавить preflight/partial/repeat/prefix/conflict/rollback/concurrency/cleanup matrix через approved verification API; доказать sensitivity без production source data. Matrix RED v1/v2 и exact external-decoy correction сохранены.
- [x] 2.3 Получить independent Gate3 APPROVED exact test hashes; reviewer не автор tests или будущей implementation. Reviews tracer-v1 и matrix-v2; implementation после35c421d.

## 3. Minimal migration engine

- [ ] 3.1 Реализовать только approved engine/facade и typed verification composition; focused matrix GREEN с неизменными expected values.
- [ ] 3.2 Проверить relevant migration regression, architecture-check, lint и diff-check; сохранить exact SHA и private raw evidence.
- [ ] 3.3 Получить independent Gate5 engine-only APPROVED; не считать его writer cutover или parent selection Done.

## 4. Required release integration

- [ ] 4.1 Получить отдельно gated all-writer cutover, registry allocator, selection-family и original-reader contracts/implementations; проверить полный live ownership manifest и запрет N-1 writes.
- [ ] 4.2 Зафиксировать canonical version на фактическом frontier, пройти отдельные RED/Gate3/GREEN/Gate5 registration; не резервировать номер только planning-файлом.
- [ ] 4.3 Выполнить full make verify и clean deployment/restart preservation на exact SHA; Done/архив только после всех dependencies, literal VERIFY_OK и независимого integration review.
