# Миграция Yii2 — inventory #76

Baseline: origin/main `414ac0a7`, новый checkout `fmonitor-2-yii2-76-20260909`.
Решение владельца 2026-09-09: Yii2 выбран; приложения организации изучать **не нужно**.
Это отменяет только соответствующий пункт этапа 1 #76. Старый WIP native-local-authentication
не перенесён. Установленный стенд и его данные не являются тестовой средой.

## Входы HTTP и целевые владельцы

`id` ниже — существующая числовая идентичность; формы и query contracts сохраняются.
Общий нынешний путь: nginx → public/runtime.php → rapid-pilot/router.php →
LocalAuth → конкретный adapter либо PilotHttp production-entrypoint → decorators.
`public/router.php` — самостоятельный native/verification вход, не установленная
production composition. `app/demo/router.php`, rapid router и docker-bootstrap —
demo/oracle; переносить их implicit defaults в Yii configuration нельзя.

| Вход/семейство | Текущий owner/adapter | Целевой owner; условие удаления |
|---|---|---|
| `/health/live`, `/health/ready` | public/runtime.php, RuntimeReadiness | Yii HealthController → RuntimeReadiness; HTTP не выполняет prepare/DDL |
| `/`, `/pilot`, `/pilot/` | rapid router/PilotHttp shell | Yii URL rules и shell; ссылки/redirects проверены |
| `/pilot/login`, `/logout`, `/activate` | RapidPilotLocalAuth, MariaDbLocalAuthRepository, custom session storage | Yii User/Security/Session и IdentityAccess; принудительный повторный вход разрешён, legacy cookie/session payload не переносится; authorization, CSRF, invitation single-use и audit сохраняются |
| `/pilot/objects` | RapidPilotObjectQueue, MariaDbObjectQueue | Process query controller/reader; q/status/page, статусы и соседняя навигация сохранены |
| `/pilot/objects/{id}` | PilotHttp, applied card reader, CompletionFlow, shell decorators | Process card controller/read model; original/opening/completion actions и документы сохранены |
| `/pilot/installers` | MariaDbInstallerDirectoryReader, InstallerDirectoryView | Workforce read controller; search/status/assignment projections сохранены |
| `/pilot/calendar[/]` | RapidPilotCalendar | Planning read controller; range/date/events сохранены |
| `/pilot/construction-control` | PilotE2ECoordinator, queue, InspectionSchedule decorator | Inspection read controller; фильтры, доступ и сортировка сохранены |
| `/pilot/objects/{id}/assignment-order/{prepare,selection,installers}` | FreshOrderHttpHandler/Coordinator | Composition controller → AssignmentOrderComposition; одна транзакция owning seam |
| `/pilot/objects/{id}/assignment-orders/{order}/{registration,template,artifacts/{order,appendix,signed_original}}` | PilotE2ECoordinator, template/document owners | Document controller → existing owners; legacy registration remains rejection/read-only contract |
| `/pilot/objects/{id}/assignment-orders/{order}/originals[/submit,/history,/{identity}/download]` | OriginalUpload/History handlers, Original service/storage | Original controller → AssignmentOrderOriginal; append-only bytes/history/replay проверены |
| `/pilot/objects/{id}/{control-engineer,execution,open}` | ExecutionHttpHandler/Coordinator | Process controller → Composition/Opening; exact capability и audit preserved |
| `/pilot/{objects,construction-control/objects}/{id}/checklist[/operations,/photos]` | ChecklistSync, InspectionEvidence, rapid completion gate | Inspection controller → InspectionEvidence; offline operation IDs/attribution/replay и фото сохранены |
| `/pilot/construction-control/objects/{id}[/sync-context]` | PilotE2ECoordinator | Inspection controller/query owner; offline context preserved |
| `/pilot/objects/{id}/inspection-schedule` | RapidPilotInspectionSchedule (HTTP SQL transaction) | Новый InspectionPlanning application seam; controller не владеет транзакцией |
| `/pilot/objects/{id}/completion` | RapidPilotCompletionFlow → InstallationCompletion | Completion controller; record/correct PTO/declaration и 85/15 unchanged |
| `/pilot/users`, `/pilot/admin/users`, `/pilot/admin/roles` | PilotUserAdminHttpHandler, MariaDbPilotUserDirectory, UserDirectoryView | IdentityAccess query controllers; no read-to-write bootstrap |
| `/pilot/admin/users/invite`, `/{id}/{invitation,status,roles[/roleId]}` | PilotUserAccess/Admin handlers, UserAccessView, IdentityAccess seams | IdentityAccess commands; exact roles, single-use tokens, atomic audit preserved |
| `/pilot/otiz[/,/objects,/payments,/history,/reconciliation,/reconciliation/quarantine,/active-baselines,/historical-replay]` | RapidPilotOtiz + Otiz/LegacyMigration readers | Otiz query controllers; pagination/filter/export semantics preserved |
| `/pilot/otiz/snapshots/{id}[/export.xlsx]` | RapidPilotOtiz + MariaDbSnapshotStore | Otiz read/export controllers; accepted snapshot unchanged |
| `/pilot/otiz/calculate`, `/snapshots/{id}/accept` | RapidPilotOtiz → SnapshotPublication | Otiz controller → existing atomic publication; A01 oracle retained |
| `/pilot/otiz/snapshots/{id}/{closures,payments/complete}`, `/closures/{id}/reverse` | RapidPilotOtiz SQL writers | Otiz settlement public seam #70; A02 serialization/replay/rollback before removal |
| `/pilot/otiz/reconciliation[/quarantine]/decisions` | RapidPilotOtiz → LegacyMigration | Reconciliation application owner; quarantine decision/audit preserved |
| `/pilot/assets/*`, `/favicon.ico` | rapid router + native public router | Yii asset registration/static nginx allowlist; exact URLs/MIME/HEAD/CSP/cache verified |

Asset families: public shlz.css/icons/tabs/behaviors/calendar-grid; dynamic
`/pilot/assets/file-types/{name}.svg` with generic fallback; pilot CSS aliases;
Golos latin/cyrillic weights 400/500/600; checklist/service worker, picker,
selection-picker, users, control-queue, navigation, original-upload, object-details,
object-queue, installer-directory, otiz, calendar, inspection-schedule, preloader,
invite; favicon and two public file/download icons. Preserve service-worker URL/scope.
No copying private shlz-ui implementation. PDF renderer reads
`rapid-pilot/assets/shlz-logo.jpg.base64`: move asset without changing PDF branding.

## CLI, worker, scheduler и persistence

| Текущий вход | Целевой composition/owner; условие удаления |
|---|---|
| bin/fmonitor2-migrate.php | Yii migration command → existing schema owners/ledger initially; fresh+upgrade no replay |
| bin/fmonitor2-runtime-{prepare,check,recovery}.php | Yii runtime commands → Runtime/RuntimeRestore; storage permissions, backup/restore contracts retained |
| bin/fmonitor2-jobs.php | Yii jobs commands → Jobs; worker/scheduler/health/schedule-once/list-failed/retry grammar explicitly mapped |
| bin/fmonitor2-job-handler.php | Yii isolated handler → Jobs registry; lease/retry/dedup/outcomes and signal shutdown preserved |
| bin/fmonitor2-sync-workforce.php | Yii workforce sync → Workforce/InstallationProcess; no duplicate external deliveries |
| bin/fmonitor2-prepare-bitrix-config.php | Yii deployment config command; explicit token file, never demo manifest |
| bin/fmonitor2-provision-initial-admin.php | Yii identity provisioning → InitialOwnerProvisioning; one authorized provisioning operation |
| bin/fmonitor2-import-{cases,pilot-snapshot}.php | Yii explicit import → LegacyMigration/InstallationProcess; approved source-only reads |
| rapid-pilot/jobs-entrypoint.php | pilot deployment translation for worker/scheduler/health; remove after all delivery composition uses Yii console and explicit configuration instead of the active demo manifest |
| rapid-pilot/hourly-bitrix-workforce.php | superseded direct SQL/cURL workforce writer; prove no scheduler/deployment caller remains, then remove in favor of Workforce/Jobs |
| rapid-pilot/issue-invitation.php | pilot operator invitation writer; replace with the IdentityAccess Yii command or remove with the disposable pilot contour |
| rapid-pilot migration/profile/connect/import scripts | LegacyMigration operator commands and evidence tools; classify each as retained read-only evidence, Yii console migration command, or removable fixture before deleting rapid-pilot |
| bin/pilot-session-storage-inspect.php | transitional operator inspection; retire at auth cutover because forced re-login is approved and legacy session payload replay is not required |
| bin/fmonitor2-pilot-demo.php; rapid-pilot/docker-bootstrap.php; app/demo | non-production oracle/test fixtures, retain separately; never call on HTTP |

Current repositories use mysqli. Existing transaction seams retain their connection
until a whole atomic boundary migrates to Yii DB/DAO/Query Builder. A controller may
not mix PDO/Yii and mysqli to perform parts of one transaction. No blanket ActiveRecord
rewrite. Schema migrations/readiness are explicit owners, not web bootstrap.
`JobsRuntimeCommand` currently creates handler subprocesses using the old bin path;
changing only the top-level worker entry does not complete stage 6.

## Transitive dependencies and removals

The companion TSV indexes 1,347 lexical SQL/include/compatibility references in app,
bin, public, rapid-pilot runtime PHP and deployment configuration. It is a source
navigation aid, **not** proof of executed reachability or a complete SQL parser.
Actual load traces and golden role flows must close each route before its removal.

Known non-obvious edges: ProductionPilotHttpEntrypointFactory loads LocalAuth;
RuntimeConfiguration publishes rapid CSS path; PDF renderer reads rapid logo;
MariaDbIdentityBootstrapApplication imports app/RapidPilot/LocalRoleCatalog alias;
rapid Otiz loads LegacyMigration readers. PilotHttp.php contains multiple classes
behind shim files, so plain PSR-4 without existing autoload/compatibility handling
is insufficient. Runtime image currently fabricates vendor/autoload.php from
rapid-pilot/tcpdf-autoload.php; Yii requires a real Composer install and lock.

Delete progressively: composite router, HTTP response emitters/request parser,
route admission dispatcher, session codec/storage composition after compatible
Yii session migration, duplicate auth bootstrap, shell decorators after view parity,
HTTP SQL writers, implicit demo setup and asset runtime loaders. Keep historical
test evidence and read-only migration adapters under their actual owners.
Disposable pilot users, test fixtures and legacy session files need not be migrated;
the cutover intentionally invalidates old cookies. This does not waive role/capability,
invitation, authorization, CSRF or append-only audit behavior, and it does not permit
blanket deletion of business artifacts or migration evidence.

## Order and verification

1. Inventory/ADR (this document); isolated Yii web+console operational foundation.
2. First real read-only route: `/pilot/admin/roles`, existing directory reader and
   rendered HTML; explicit temporary authentication boundary until stage 3. Requires
   authorization characterization before wiring; cookie/session continuity is not a
   cutover requirement because the owner approved forced re-login.
   Health-only foundation is not completion of issue76 stage2.
3. Yii auth/session/identity admission #71, then process and Otiz slices (#24/#70).
4. Console/import/DB boundary migration and removal of all remaining runtime edges.
5. Golden role flows + exact-source full CI + isolated upgrade/rollback rehearsal;
   switching stand requires the separately agreed operational step from #76.

Per slice: public specification → intended RED → independent test review → focused
GREEN → independent code review. Keep external denial/history/atomicity/HTTP oracles.
Internal class-name, custom session filesystem and hand-router assertions are not
silently disabled: inventory them for explicit replacement alongside the owning slice.
No current inventory or historic approval proves final readiness.

## Проверенное продвижение —2026-09-10

PR85 merged907cb0a3 (exact source41066c68, Actions34431377731 SUCCESS/VERIFY_OK)
перенёс на Yii /pilot/admin/users, /pilot/users, invite/reissue/role/status и
/pilot/activate. Application owner IdentityAccess\YiiUserAccess, внутренние
MariaDb adapters используют одну Yii Connection/transaction; реальные HTTP trace
и browser/concurrency подтверждены независимыми reviews. Proxy activation token
не попадает в nginx query/referrer diagnostics при502; обычная диагностика сохранена.

Ранее PR79 перенёс native Yii login/logout/roles и session admission, PR83 —
settlement owner и Yii OTIZ read/export/payment/reversal routes. Эти строки
inventory отмечают поставку конкретных capabilities, а не полное завершение
этапов3/5 или удаление всего прежнего runtime.

Старые users/auth/OTIZ handlers пока нужны сохранённому старому stand: runtime.php
остаётся на rapid router. Критерий удаления — все входы рабочего контура переведены
на Yii, golden flows и rehearsal GREEN, затем согласованное переключение.
Следующий active slice yii2-object-queue: GET/HEAD objects + schedule POST;
планирование не объявлено реализованным до его Gates3/5 и CI.

PR86 mergedf804f3f6 (source58023c82, Actions34439847358 SUCCESS/VERIFY_OK)
добавил Yii GET/HEAD /pilot/objects и POST inspection-schedule с owners
YiiObjectQueue/YiiInspectionPlanning, full canonical readiness без HTTP DDL и
проверенной native навигацией. Старый stand adapter сохраняется до cutover.
Следующий active slice — yii2-preopening-journey; пока planning, не GREEN.
