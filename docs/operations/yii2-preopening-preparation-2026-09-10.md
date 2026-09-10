# Подготовка следующего среза #76: путь до открытия

Root preparation on main after PR86, base f804f3f6. Нормативная matrix и Quality
Graph input созданы; двенадцать новых HTTP/browser/image tests прошли независимый corrected Gate3 PASS после одного RETURN с6 группами.
Все12 intended RED подтверждены. Production implementation — следующий этап. Предыдущая queue
поставка merged и проверена точным CI; подробности в её delivery record.

## Полный путь и владельцы

Очередь → карточка → выбор состава → необязательный PDF-шаблон → подписанный
оригинал (initial/correction/history/download) → отдельное confirmed-original
opening → обновлённая карточка. Существующие semantic owners не дублируются:

- AssignmentOrderCompositionApplication::selectAssignmentOrderComposition через
  ProductionAssignmentOrderCompositionFactory::create(mysqli,freshConnection,prefix).
- AssignmentOrderTemplateApplication через ProductionAssignmentOrderTemplateFactory.
- AssignmentOrderOriginalApplication::submitAssignmentOrderOriginal через
  ProductionAssignmentOrderOriginalFactory::createForSelections; storage/fresh
  terminal reader/safe log входят в существующую атомарную recovery boundary.
- ProductionConfirmedOriginalOpeningFactory::create(...)->openConfirmedOriginal.
- AssignmentOrderOriginalHistoryReaderFactory: history и prepared download lease.

При подключении HTTP существующая command boundary остаётся целиком прежней:
Yii DTO передаёт намерение/expected version, владелец повторно проверяет все факты
в своей mysqli transaction. Нельзя предварительным Yii DAO read разрешить write
в другом соединении. Это reuse неизменённого owner, не перенос его DB boundary;
перенос самого persistence owner на Yii DAO требует отдельного целого среза.
Новая DB boundary карточки принадлежит Yii DAO. Portal/search/submission/history
уже являются module-owned public APIs и сохраняют свои native boundaries. HTTP/view
не записывают process facts и не загружают PilotHttp/rapid.

## Проверенные транспортные детали

- FreshOrderHttpHandler::handle допускает GET/HEAD/POST для selection, но
  **template только POST**, поскольку формирование сохраняет дату/audit.
  Предварительный read-only inventory, называвший template GET/HEAD, исправлен
  чтением handler. Нельзя превращать генерацию с фактом в GET.
- Selection — form-urlencoded максимум32768 bytes; installerTabIds[] допускает
  повторы ключа, остальные поля уникальны; неизвестные поля, bad percent encoding,
  NUL, malformed UUID/revision/id отклоняются. Native Yii `_csrf` заменяет старую
  session-bound csrfToken; exact допустимые платформенные изменения нужно описать.
- Installer search GET/HEAD: q обязателен, page decimal, только q/page query keys;
  ответ JSON items/page/hasMore. Exact eligibility принадлежит уже существующим
  кадровым/selection contracts и решениям владельца2026-09-07.
- Original POST — raw application/pdf, **не multipart**. Content-Length обязателен,
  transfer encoding не принимается, предел20971520. X-FMonitor-Original содержит
  canonical base64 UTF-8 JSON с фиксированными keys/type/order и максимум16384
  bytes encoded metadata. Точный контракт OriginalUploadInput.php + normative
  ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001.
- Native Yii CSRF для raw PDF нужно провести через стандартный X-CSRF-Token,
  не возвращая собственную проверку сессии. Окончательный mapping metadata
  csrfToken/header и status400 описать в Gate1 до тестов; это техническое решение
  migration, не новое разрешение на загрузку. Остальной wire/replay contract
  не упрощать молча.
- Initial accepted201/replay200/conflict409/failed503; authorization403,
  not-found404, too-large413, прочие domain rejection422. Failed dependencies
  generic safe JSON с Retry-After. Success encoder/history shape сохраняется.
- Prepare GET/HEAD →303 selection; старый preparePOST и registration/artifact
  family при fresh flow410. Не восстанавливать obsolete apply/registration UI.

## Read dependencies и runtime ресурсы

Card сейчас MariaDbAppliedObjectCardReader over MariaDbObjectCardReader в PilotHttp;
port projection в application-owned Yii adapter: native provenance/detail, current
selection/members/original, application/opening, completion, historical actor name.
Не перетаскивать PilotHttp namespace в YiiRuntime. Selection portal/search уже
находятся в AssignmentOrderComposition и подключаются как неизменённый public service.

Original ресурсы используют FMonitor config: FMONITOR_ARTIFACT_STORAGE_ROOT,
FMONITOR_ORIGINAL_DB_PASSWORD_FILE, FMONITOR_ORIGINAL_SAFE_LOG_FILE и explicit DB
variables. Private byte/read lease/recovery boundaries остаются владельцу Original.
Default storage/demo manifest не выводить из request/HTTP bootstrap.

Assets: object-details.js, selection-picker.js, original-upload.js и existing shell.
Сохранить public shlz API/keyboard/mobile, исключить runtime reads из rapid.

## Источники будущей матрицы

PILOT-OBJECT-CARD-001; ASSIGNMENT-ORDER-SELECTION-NATIVE-001;
ASSIGNMENT-ORDER-TEMPLATE-GENERATE-001; ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001;
ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001 и текущие original lifecycle
specs; confirmed_original_opening_001_test.php и existing native tests;
original_ready_queue_manual_test.php (owner2026-09-07), object_card_actor_name;
selection_native/selection_http/selected_original_lifecycle и
pilot_current_flow_browser.cjs. Старые полные тесты остаются adjacent oracles,
новые test cases обязаны пройти реальные Yii endpoints без injected identity.

До Gate2: прочитать все конкретные нормативные контракты, полная acceptance matrix
(roles/denials/replay/concurrency/faults/private bytes/methods/CSRF/return paths),
dependency inventory/readiness/deploy/backup impact и Quality Graph plan. Новое
поведение пока не реализовано, tests/independent reviews не заявлены.

## Дополнительные прочитанные границы

FreshOrderHttpResources требует одинаковый process/legacy prefix при fresh flow;
это реальное ограничение прежнего composition owner, не выводить независимые
prefixes из новых UI read defaults без проверки. Actor native Original authorizer
читает local pilot users/roles/permissions (MariaDbAssignmentOrderOriginalEvidence),
поэтому Yii User ID совместим; никаких legacy injected globals не требуется.

ExecutionHttpHandler различает apply/open/open_confirmed. Текущий основной UI
использует open_confirmed и возвращает303 на карточку; старые apply/open handlers
ещё существуют. Будущая матрица должна явно классифицировать эти compatibility
входы, а не молча удалить их или вернуть отдельную кнопку применения состава.

Yii SafeErrorHandler уже выдаёт безопасный JSON `{ok:false,reason:...}`, а не plain
text. Его inherited envelope и native400 CSRF использовать в transport mapping;
успешные Original worker-result JSON и доменные результаты сохранять отдельно.
Нельзя копировать произвольный exception message/stack в новый формат ответа.

## Прочитанные normative details для будущей матрицы

Selection-native сохраняет8 ports, case lock, request/audit recovery и независимый
fresh reader; ambient transaction не допускается. Разрешение требует active
builtin FKR/manager и exact selection grant, не любого custom role с похожим именем.
Template каждый POST генерирует заново с current Moscow date и новым audit;
request-id replay/cache не обещаются. Bytes выдаются только после подтверждённого
commit, uncertainty не повторяет mutation автоматически.

Original upload HTTP v0.3 требует: canonical ordered metadata keys; raw-stream
mismatch/server framing distinction; first denial/repeated denial audit policies
принадлежат native application. Admission rejection не вызывает command и не
создаёт terminal/domain audit. UI retry сохраняет File/metadata/requestId при
неопределённом сетевом результате; изменённый intent получает новый requestId.
201/200 ведёт обратно к GET form; correction может относиться к older order после
новой pending selection. Не навязывать latest-only correction policy.

Original history API — trusted read port, сам не выдаёт grants. Read-only snapshot
при idle borrowed mysqli connection; active caller transaction unavailable и не
коммитится/откатывается. History metadata не читает файлы; prepared download после
release DB snapshot проверяет весь файл/size/hash под существующим nonblocking
shared digest lease и освобождает все ресурсы до return. Missing/corrupt/busy file
не превращается в partial200. Historical revision exact bytes, не current leaf.
HTTP authorization/scope взять из отдельного ORIGINAL-HTTP-001, пока он не прочитан
здесь нельзя считать матрицу grants завершённой.

## Подтверждённое повторное использование public read owners
Read-only audit подтвердил отсутствие PilotHttp/rapid edges у
ProductionAssignmentOrderSelectionPortalFactory, ProductionAssignmentOrderOriginalSubmissionFactory
и AssignmentOrderOriginalHistoryReaderFactory. Их owning-module adapters сами
владеют временными read-only snapshots и требуют idle caller-owned mysqli connection.
Read metadata не читает private bytes; prepared download намеренно проверяет
файлы внутри Original owner. Вызов этих неизменённых APIs из Yii не переносит DB
boundary; ADR0003 и inventory разрешают такой reuse. Внешняя Yii/PDO transaction
вокруг native snapshot/command запрещена. Card reader в PilotHttp действительно
переносится и поэтому использует Yii DAO.

Это уточнение draft design, не исключение из требования Yii DAO для новых или
переносимых DB boundaries. Source APIs/connection ownership сохранены. Broad
OriginalRuntime include hub требует packaging/no-legacy traces в actual Yii image;
не подменять его прежним PilotHttp/OriginalUploadResources.
