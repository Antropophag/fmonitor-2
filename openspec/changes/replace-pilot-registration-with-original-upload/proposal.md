## Why

Pilot owner отменил ручной номер распоряжения и выбрал загрузку подписанного PDF-оригинала, но действующие contracts и application interface всё ещё считают manual registration обязательным. Первый безопасный вертикальный slice должен принять и сохранить оригинал через один public seam, не смешивая upload с выбором действующего состава или открытием работ.

Источник — `docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md`; actors — сотрудник ФКР и Руководитель ФКР; release value — проверяемый append-only original evidence как после необязательного шаблона, так и при прямой загрузке.

## What Changes

- **BREAKING contract amendment before Gate 1:** убрать из актуальной pilot truth ручной номер, `confirmRegistration` и `registered` как текущий pilot workflow; прежние facts остаются только read-only historical compatibility.
- Добавить один public application command seam для initial upload и correction одного PDF-оригинала, с immutable composition snapshot, document date, upload timestamp, hash и audit.
- Принимать один фактически валидный PDF размером не более `20,971,520` received bytes; запрещать active content, encryption/password protection, structural corruption и zero-page documents.
- Авторизовать initial upload точным capability `assignment_order.original.upload`, correction — `assignment_order.original.correct`; capabilities явно выдаются active builtin roles `fkr_operator` и `manager`, отображаемой как «Руководитель ФКР», без inference из role name.
- Сделать complete semantic repeat идемпотентным, metadata collision — conflict, а correction — append-only revision с exact expected revision и обязательной причиной.
- Определить стабильные command/result DTO, reason codes, retry precedence и полный storage/commit/response-loss contract без public orphan, включая cross-resource content lease, исключающий maintenance delete до разрешения DB outcome.
- Расширить обязательный production factory config полем `safeLogFile`: factory принимает только уже существующий canonical non-symlink regular file текущего пользователя с exact mode `0600`, до DB/private-storage access связывает final pathname identity с реально retained append descriptor и повторно проверяет на descriptor regular/current-EUID/`0600`, не создаёт и не исправляет файл и append-only пишет туда cleanup/release diagnostics без secret/path leakage. Descriptor-integrity clarification остаётся pending fresh technical Gate 1 review и не отменяет прежние owner approvals.
- Не менять в этом slice действующий состав и opening gate. Это отдельные будущие changes `apply-assignment-order-original-to-composition` и `open-installation-from-assignment-order-original`.
- Не добавлять в этом slice HTTP upload, metadata-read или download. Exact routes, local permissions, projection fields, not-found/forbidden и response headers принадлежат будущему change `expose-assignment-order-original-http`.

## Capabilities

### New Capabilities

- `pilot/assignment-order-original`: безопасный application-command initial upload, semantic replay, append-only correction и rejection/failure behavior оригинала распоряжения.

### Modified Capabilities

Нет: main OpenSpec capability для assignment-order lifecycle пока отсутствует. Активная Markdown truth и незавершённые change artifacts с manual registration должны быть coherently amended до exact-hash Gate 1 этого slice.

## Impact

- Planning и будущая реализация затрагивают Assignment Orders application seam, process capabilities, private document storage, обязательную production safe-log configuration и immutable metadata/audit persistence.
- Gate 2 production evidence сохраняет existing read-only reader/config/worker contracts. Safe-log descriptor proof теперь задаётся `ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001`: стабильные public-owner и pure-policy tests плюс обязательный exact-SHA structural Gate5 доказывают real fstat/ownership/close/data-flow и factory ordering. Pending interval observer/permission-transition method заменён; rejection history сохранена, rejected mechanisms не повторяются. Public owner не допускает raw-handle/adoption escape и не переоткрывает путь для append.
- Isolated MariaDB Gate 2 setup использует named public schema migration version 1 и verification-only fixed Example-A seed seam; runtime paths не вызывают их, fixture не создаёт original facts и evidence reader остаётся read-only.
- Canonical `CONTEXT.md`, pilot spec и pilot data model synchronously amended owner-approved original-PDF truth до Gate 1. До executable-spec approval также должны получить явную disposition `docs/installation-process-interface.md`, behavior inventory и активные E2E/RBAC/PDF changes/specs/tests, которые характеризуют реализованный manual number, `confirmRegistration` или `registered`; исторические reviews/evidence не редактируются и помечаются как legacy evidence, а не target behavior.
- Вне scope: OCR, signature/stamp verification, malware scanning, JPG/PNG/multi-file upload, обязательный template, 1С ДО integration, HTTP/read/download, смена current composition, sequential-order applicability и opening by original.
- Следующие slices (создаются отдельным OpenSpec workflow, не здесь): `apply-assignment-order-original-to-composition` определяет prospective sequential orders/ties; `open-installation-from-assignment-order-original` заменяет opening gate и фиксирует immutable opening snapshot.
- Отдельный future slice `expose-assignment-order-original-http` определяет HTTP upload, metadata query и download, включая exact local permission `assignment_order.original.read`, projection DTO и safe response contract.

## Shared safe-log owner amendment — 2026-09-06

Новый технический contract сохраняет owner-approved file policy и production
failure boundary. Private-constructor opened owner единолично выполняет normal
non-creating open, real fstat validation и retained append/close. Existing logger
остаётся только compatibility facade. Требуются fresh Gate1→RED→Gate3→GREEN→
Gate5, включая structural proof; feasibility review не считается approval.
Product authority не расширяется, новые owner decisions не запрашиваются.
