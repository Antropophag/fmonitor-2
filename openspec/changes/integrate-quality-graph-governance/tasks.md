## 1. Gate 1 — executable specification и аудит baseline

- [x] 1.1 Составить inventory текущих SSD/TDD harness, CI entry points, repository-owned команд и evidence formats; сохранить audit с командами и file/commit references и проверить `git diff --check`.
- [x] 1.2 Создать executable spec `QUALITY-GRAPH-GOVERNANCE-001` с public governance seam, receipt schema, canonical identities, gate ordering, rejected cases, representative PR matrix и exact expected results; получить явное Gate 1 approval по `docs/development-process.md`.
- [x] 1.3 Зафиксировать проверенный exact Quality Graph v0.1.7-compatible package/action release set и upstream source permalinks; проверить отсутствие floating refs и смешанных версий автоматической проверкой fixtures.

## 2. Gates 2–3 — RED и независимый test review

- [ ] 2.1 Написать минимальные тесты публичной repository-owned governance команды для valid lineage, exact test/implementation sets, missing evidence, hash drift, reviewer non-independence, Git gate chronology, reviewed-commit mismatch, path escape, immutable supersession, duplicate IDs и stale graph provenance; проверить, что setup исправен.
- [x] 2.2 Продемонстрировать intended RED до implementation, сохранить точную команду, exit code и релевантный вывод в `docs/operations/quality-graph-governance-red-evidence.md`.
- [x] 2.3 Поручить отдельному агенту независимый Gate 3 review спецификации, тестов и RED; сохранить независимые review records с reviewer identity и verdict `APPROVED` до GREEN.

## 3. Gate 4 — минимальный lineage governance

- [ ] 3.1 Добавить immutable versioned machine-readable receipt-chain schema и fixtures, которые индексируют spec, exact tests, RED/reviews/GREEN и exact implementation files по relative path и SHA-256 без копирования narrative; проверить schema/history fixtures focused tests.
- [ ] 3.2 Реализовать fail-closed checker и одну repository-owned команду с детерминированными failure categories; проверить focused Gate 2 tests GREEN.
- [x] 3.3 Обновить spec/review/evidence templates canonical `delivery-metadata` блоками с identity, artifact-author, verdict, timestamps, Git-derived exact test/implementation sets и reviewed implementation commit без self-containing commit SHA; проверить старые records не ломаются вне явно onboarded slices.
- [x] 3.4 Подключить governance checker к `make architecture-check`/отдельной документированной Make-команде без изменения test semantics `make verify`; проверить локальный positive и каждый negative fixture.

## 4. Gate 4 — минимальный Quality Graph и CI

- [x] 4.1 Добавить canonical graph declaration и детерминированные generated runner/manifest с минимальными nodes для drift validation, lineage governance и существующего full verification seam; проверить их parity командой upstream CLI и отдельно зафиксировать allowlisted publisher override.
- [ ] 4.2 Добавить untrusted PR runner с read-only permissions, exact pins и content-addressed Result v0 artifacts; проверить node/PR/head/run/attempt/graphDigest provenance на fixture event.
- [ ] 4.3 Добавить repository-owned trusted publisher только для upstream `watch`/`publish`, с `actions: read`, `contents: read`, `checks: write`, base-branch topology, без checkout, `issue_comment`, command job и approval/write surfaces; проверить rejection missing/stale/mismatched results и fail-closed allowlisted comparison с generated v0.1.7 publisher.
- [ ] 4.4 Расширить architecture policy проверками запрета floating refs, mixed toolchain versions, обхода repository commands и небезопасных publisher permissions; выполнить `make architecture-check`.
- [ ] 4.5 Выполнить focused suites и `make verify`, сохранить GREEN commands/results и exact head commit в operations evidence.

## 5. Gate 5 — независимый code review

- [ ] 5.1 Поручить отдельному от test reviewer и implementation author агенту независимый Gate 5 review executable spec, approved tests, production/tooling diff, security boundary и GREEN evidence; сохранить `reviews/code/QUALITY-GRAPH-GOVERNANCE-001.md` с exact reviewed implementation commit и `APPROVED`.
- [ ] 5.2 При изменении тестов после Gate 5 вернуться к Gate 2/3; иначе проверить lineage receipt самого slice публичной governance командой.

## 6. Representative PR и dual-run parity

- [ ] 6.1 Создать фактический незамерженный GitHub representative PR против `main`, не меняя branch protection, и выполнить старый harness и новый graph на одинаковых head commits для positive case и negative cases: graph drift, missing/stale lineage, failing repository command и stale provenance.
- [ ] 6.2 Сохранить phase A parity matrix с run URLs/IDs, head SHAs, commands, exit statuses, expected/actual failures и graph digests; проверить, что новый graph не пропускает ни одного failure старого механизма.
- [ ] 6.3 Проверить phase B trusted publisher/dashboard parity только на topology, уже доступной из base branch; если bootstrap topology ещё не смержена, явно оставить задачу незавершённой и зафиксировать blocker без объявления полной parity.
- [ ] 6.4 Не удалять и не делать необязательным старый механизм; подготовить отдельное future cutover proposal только после завершённых 6.2 и 6.3.

## 7. Done definition

- [ ] 7.1 Выполнить `make architecture-check`, `make verify`, graph validation и lineage governance для exact reviewed implementation commit плюс допустимого evidence envelope; проверить все результаты GREEN и связаны одной Git-derived provenance chain.
- [ ] 7.2 Подтвердить, что PR не смержен, branch protection не изменён, старый CI/harness сохранён, approvals disabled, а незавершённая publisher parity не представлена как завершённая миграция.
