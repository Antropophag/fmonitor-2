# CHECKLIST-HTTP-AUTHORIZATION-001

## Простыми словами

Issue #130: отказ в действии возвращается как HTTP 403. Ответ операции не
раскрывает чек-лист пользователю без действующего права чтения объекта.
Разрешённая синхронизация, история и бизнес-отказы сохраняются.

## Public seam and acceptance

Real Yii HTTP entrypoint `public/yii.php`, POST
`/pilot/objects/{id}/checklist/operations|photos` and corresponding
`/pilot/construction-control/objects/{id}/checklist/operations|photos` aliases.
Existing canonical owner permissions and append-only invariants are inherited
from YII2-INSPECTION-JOURNEY-001; no role grants change.

- A1: Active authenticated actor with neither checklist read nor action permission,
  valid session CSRF and valid JSON receives 403, status rejected. No projection,
  revision, composition, personnel, operation receipts or photo content is returned.
  Cover fresh command, replay identity, stale revision and invalid business input.
- A2: Allowed reader without action permission still reads GET successfully;
  canonical action authorization denial maps to exactly 403 (not validation 422).
  This also applies to item 42: its documentary-closure business shortcut must
  not precede refusal of an unauthorized item-completion action. Authorized
  item 42 requests retain the inherited 409 and exact documentary-closure message.
  Read-only denials cover item completion/correction, section completion,
  completion retraction, photo upload and photo revocation. Missing formal
  retraction assignment is an authorization refusal, independently of malformed
  retraction input or missing original operation (business rejections).
  Authorization refusals may omit projection even for readers.
- A3: Authorized executor accepts item completion (revision 0 -> 1, server actor
  73, installers 7001/7002); duplicate remains 200 without new facts, stale or
  changed-payload conflict remains 409, invalid installer remains 422. These
  authorized responses retain current projection required for synchronization.
- A4: Every response that includes projection requires current owner read access
  for that actor/object, independently of action permission or result status.
  A formally assigned fixture engineer with photo-revoke capability alone still
  lacks read access (no construction-control role, checklist.read or item-complete
  grant). HTTP mutations require read admission as well as command admission:
  both photo_revoked and completion_retracted replay/conflict/business-invalid
  requests must not expose protected content. The retraction cases independently
  catch missing controller read admission even when photo authorization rejects
  before its duplicate lookup.
  With no read and no action permission, refusal creates no facts. This slice does
  not grant or redefine action permission for actors without read access.
- A5: Photo and operation aliases enforce the same safe denials. Existing photo
  capability/assignment authorization refusals map to 403. Authorized photo,
  section, correction, retraction and conflict flows remain functional.
- A6: Guest and deactivated sessions cannot mutate or obtain protected data;
  existing 303 login redirect is preserved. CSRF, method, JSON and body-size
  failures retain existing transport semantics. All rejected commands and replays
  preserve the complete domain table inventory, revisions and private artifact bytes.

## Design and impact

One security mapping slice; root authors spec/tests, separate sol/low executor
implements and independent sol/low reviewer decides Gates 3/5. Owner authorized
separate worktree from latest origin/main, PR without merge, on 2026-09-14.
Extend the existing HTTP journey test already selected by full CI. No new shared
verification inventory entry. No migration/schema/fixture table additions,
deployment, backup format, runtime dependency or offline-client changes required.
Makefile, legacy-import, integration config and OpenSpec yii2-imports-workforce
are outside scope. This is a fix of the existing migration contract, not a new
migration slice. Verification binding lives separately under docs/operations.
