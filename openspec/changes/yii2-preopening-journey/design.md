## Context
Owner #76 разрешил автономный переход на Yii2 с сохранением текущих сценариев,
public application owners и append-only истории. Predecessor yii2-object-queue
merged PR86/f804f3f6. Нормативная migration matrix и обязательный plan подготовлены;
root RED candidate ещё не полностью написан и независимого Gate3 нет.

## Goals / Non-Goals
Полный путь до открытия через реальный Yii HTTP: карточка, состав, optional
PDF-template POST, raw original upload/correction, history/exact download и
отдельное open_confirmed. Сохраняются server HTML и shlz. Нет новых кадровых,
документальных или финансовых правил; checklist/photos/calendar/OTIZ/CLI — далее.

## Decisions
Yii owns request/response, native Session/User/CSRF, routing/controllers,
form validation, views/assets и safe errors. Controllers не содержат SQL или
копий domain decisions. Новые read-only queries карточки из PilotHttp переносятся
в owning-module MariaDb Yii DAO adapter; их interface определяется normative spec.

Неизменённые public services Selection Portal, Original Submission Query и History
Reader уже self-contained в своих модулях. Они получают caller-owned idle mysqli
connection и сами владеют scoped snapshots. Новые Yii transport callers используют
их целиком, как и существующие Selection/Template/Original/Opening command owners.
Это сохраняет существующие DB boundaries, а не создаёт их Yii-копии. Dedicated
request-scoped native composition закрывает ресурсы в finally; native transactions
не оборачиваются в Yii transaction и не соединяются с предварительной Yii записью.
Command получает intent/expected revision и сам повторно проверяет authoritative
facts. Native resource composition явно перечисляется в inventory с владельцем и
условием последующей полной миграции DB boundary. ADR0003 разрешает такой reuse.

## Bound transport decisions
- Template remains POST, поскольку сохраняет last generation date/audit.
- Selection/execution form uses native `_csrf`; per-route closed form decoding
  сохраняет duplicate/unknown field и byte-bound guarantees. Не добавлять второй
  общий HTTP framework или global parser, меняющий соседние маршруты.
- Original remains raw application/pdf + canonical X-FMonitor-Original metadata,
  not multipart. Native X-CSRF-Token защищает raw POST. Metadata csrfToken must match the native header; session validation belongs only
  to Yii Request. Native400 and safe envelope mapping is explicit in the spec.
- Native command Result mapping сохраняет canonical payload/replay; inherited
  Yii SafeErrorHandler envelope применяется только к framework/admission errors
  согласно явно записанной migration matrix. No exception/config/header/body leak.
- GET/HEAD read-only; no runtime migration/mkdir/credential-file generation.

## Dependency impact before Gate 2
- No schema version change planned. Existing public predicates/source manifests
  remain authoritative; actual image must package OriginalRuntime transitive classes.
- Existing public owner regression suites remain, new Yii HTTP/browser tests prove
  actor/CSRF/roles, received PDF bytes, immutable history, expected-version conflicts,
  rollback/failure mapping and user return path. Root authors tests; independent review.
- PDF renderer currently reads rapid-pilot/assets/shlz-logo.jpg.base64. Move this
  owned runtime asset byte-for-byte behind an explicit app path; prove real PDF
  text/visual behavior. Do not copy private shlz implementation or add PDF storage.
- Private original storage/fresh-reader credential/safe-log config remains explicit
  outside web root. Native storage validation/lease/read/hash belongs to Original.
- New Yii asset bundles for object-details/selection-picker/original-upload. Preserve
  current nav/icons/logout, accessible form errors, mobile controls and focus styles;
  reuse existing shell truth. No placeholder actions or hidden role expansion.
- Strict routes must map current 410 obsolete prepare/registration paths explicitly;
  don't restore old writers. Current legacy apply/open compatibility actions need an
  explicit classification in the final matrix instead of accidental404/fallback.
- Quality Graph planned paths/acceptance mapping, exact inventory/E2E list, runtime
  readiness and backup/restore impact are completed before Gate2. One final full CI.

## Risks / Trade-offs
Broad OriginalRuntime include hub is a packaging risk, not permission to load
PilotHttp resources. Existing native APIs require idle connection: a convenience
outer transaction would break their ownership/recovery guarantees. Old draft
ORIGINAL-HTTP-001 multipart/REMOTE_USER and old card no-controls clauses are obsolete;
only current owner decisions/current executable contracts are migrated. Superseding
clauses must be enumerated in the new normative spec, not inferred from filename.

## Migration Plan
Finish matrix/impact → merged predecessor → plan/root RED → independent Gate3 →
separate implementation → focused/visual/architecture → independent Gate5 → fullCI.
Working stand remains preserved until a later concrete, verified cutover step.
