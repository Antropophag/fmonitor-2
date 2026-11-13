## Context

Original owner имеет отдельный зрелый bytes/DB/recovery protocol. Confirmed opening атомарно применяет состав и открывает дело, но принимает произвольную дату. Контракт нового поведения: `specs/ACTUAL-START-FROM-ORDER-001.md`.

## Goals / Non-Goals

**Goals:** durable confirmed appendix date, immediate opening, retry safety, сохранение первоначального начала, active Yii integration.

**Non-Goals:** OCR, scheduled opening, backfill/reset базы, изменение formal original transport fixture API и retired PilotHttp, новый финансовый алгоритм.

## Decisions

- Composite owner в AssignmentOrderComposition; factory принимает existing original application и SelectionClock. `submit(originalCommand,objectId,appendixStartDate)` возвращает array status/reasonCode/retryable/original/opening. Full success accepted/replayed возможен только после opening/application success; pending сохраняет receipt и retry.
- Additive migration39 создаёт immutable `fm2_assignment_order_original_starts`, keyed request_id. Intent сохраняет полный semantic fingerprint и digest/size до original commit; signed fact существует только при join к принятой original revision. Это сохраняет историческую schema38 и original core protocol.
- При force-replay низкоуровневого original result проверяется дата/fingerprint канонического request returned revision, а не только текущего intent. Любая неоднозначность fail-closed.
- Opening читает persisted date через owner. Future и предшествующая documentDate appendix дата допустимы. Clock/opened_at, checklist effective time и documentDate validation сохраняют разные роли.
- После открытия correction сравнивает дату с принятой датой того же order. Новый order может иметь другое начало, но не переписывает начало дела. Application-only path сохраняет existing composition checks; reapplication после открытия разрешается узко для same-date verified correction. Исторические snapshots не переписываются.
- HTTP upload вызывает composite owner; форма/JS содержат appendixStartDate. Ручные execution opening endpoints закрыты, формы даты удалены. Missing-date legacy original остаётся явным unknown до отдельно запланированной чистой загрузки; никакого вывода из mutable plan.
- Schema owner — InstallationProcess; текущий recovery получает V39 без изменения V38. Root authored acceptance tests наблюдают SQL facts только как assertions, target mutations идут через public seams.

## Risks / Trade-offs

- [Два commit] → честный pending, immutable intent и deterministic opening identity, проверка crash/retry; подписанный PDF не удаляется при позднем отказе.
- [Старые UI/code paths] → strict metadata и отсутствие public manual-opening bypass, реальные HTTP/browser проверки.
- [Future actual start] → working/checklist доступны сразу, время открытия остаётся текущим; financial history и existing date eligibility не переписываются.
- [Production имеет незакоммиченные hotfixes и старые worker images] → backup и CAS-хеши только changed paths, patch всех необходимых runtime roles, без замены посторонних файлов.

## Migration Plan

Focused RED → отдельный executor → focused regression → независимый final review. Полный локальный suite запрещён; обычный Gate3/full CI отложены owner emergency route и не считаются approved. Production: private DB/state backup, bounded migration39, точные changed files, graceful scoped reload и read-only readiness. Reset старой базы не выполняется. Rollback возвращает предыдущие source bytes, additive facts не удаляются.
