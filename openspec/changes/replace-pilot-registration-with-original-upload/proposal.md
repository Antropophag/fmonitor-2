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
- Не менять в этом slice действующий состав и opening gate. Это отдельные будущие changes `apply-assignment-order-original-to-composition` и `open-installation-from-assignment-order-original`.
- Не добавлять в этом slice HTTP upload, metadata-read или download. Exact routes, local permissions, projection fields, not-found/forbidden и response headers принадлежат будущему change `expose-assignment-order-original-http`.

## Capabilities

### New Capabilities

- `pilot/assignment-order-original`: безопасный application-command initial upload, semantic replay, append-only correction и rejection/failure behavior оригинала распоряжения.

### Modified Capabilities

Нет: main OpenSpec capability для assignment-order lifecycle пока отсутствует. Активная Markdown truth и незавершённые change artifacts с manual registration должны быть coherently amended до exact-hash Gate 1 этого slice.

## Impact

- Planning и будущая реализация затрагивают Assignment Orders application seam, process capabilities, private document storage и immutable metadata/audit persistence.
- Canonical `CONTEXT.md`, pilot spec и pilot data model synchronously amended owner-approved original-PDF truth до Gate 1. До executable-spec approval также должны получить явную disposition `docs/installation-process-interface.md`, behavior inventory и активные E2E/RBAC/PDF changes/specs/tests, которые характеризуют реализованный manual number, `confirmRegistration` или `registered`; исторические reviews/evidence не редактируются и помечаются как legacy evidence, а не target behavior.
- Вне scope: OCR, signature/stamp verification, malware scanning, JPG/PNG/multi-file upload, обязательный template, 1С ДО integration, HTTP/read/download, смена current composition, sequential-order applicability и opening by original.
- Следующие slices (создаются отдельным OpenSpec workflow, не здесь): `apply-assignment-order-original-to-composition` определяет prospective sequential orders/ties; `open-installation-from-assignment-order-original` заменяет opening gate и фиксирует immutable opening snapshot.
- Отдельный future slice `expose-assignment-order-original-http` определяет HTTP upload, metadata query и download, включая exact local permission `assignment_order.original.read`, projection DTO и safe response contract.
