## Context

См. `proposal.md` и capability contract. В `main` уже есть durable jobs, scheduler slots, transactional outbox, delivery-attempt history и Yii console runtime, но нет SMTP-адаптера и недельной проекции. Native identity хранит email/roles/access, а канонические даты и прогресс распределены по существующим read-моделям. SMTP-профиль `k2-mailer` найден в соседнем read-only `../k2`, где секрет захардкожен; он является только операционным свидетельством и не импортируется в исходники.

## Goals / Non-Goals

**Goals:**

- Один application seam строит персональную read-only проекцию по заданным `recipient identity`, московской отчётной неделе и `generated-at`.
- Существующий Jobs/Outbox владеет планированием, дедупликацией, retry и историей доставки.
- SMTP transport является узким адаптером с typed outcome и полной внешней конфигурацией.
- Focused tests доказывают календарь, scope, историю, пять разделов, внимание, idempotency/concurrency, redaction и отсутствие предметных мутаций.

**Non-Goals:**

- Не создаётся второй queue/outbox/scheduler и не добавляется domain logic в `rapid-pilot`.
- Не выполняется live-отправка реальным руководителям в тестах или CI.
- Не вводится статистическая модель прогноза и не меняются канонические правила открытия/закрытия или 85/15.

## Decisions

### 1. Owning module и public seam

Новый bounded module `WeeklyFkrReport` владеет immutable report values, выборкой scoped данных, классификацией строк и renderer. Его публичный seam принимает точное время формирования и identity получателя, возвращая детерминированный envelope без сетевого эффекта. Jobs handler вызывает seam и добавляет outbox intent. Альтернатива — формировать HTML внутри scheduler/worker — отвергнута: смешивает календарь, доступ, предметную проекцию и delivery lifecycle.

### 2. Snapshot reads вместо копирования фактов

Проекция читает канонические native tables/read-модели as-of заданных границ. Новая таблица отчётных предметных snapshot не создаётся: письмо воспроизводимо из `generated-at`, period identity и существующей append-only истории. Если существующая read-модель не умеет корректный as-of, добавляется read-only query adapter рядом с владельцем фактов, а не fallback на current row. Альтернатива — вычислить delta из текущего процента — запрещена контрактом.

### 3. Глобальная область `objects.read` применяется в query boundary

Recipient directory отдаёт только active identity с действующей ролью руководителя ФКР, `objects.read` и валидным email. По решению владельца №11 все такие руководители получают общий полный набор доступных объектов; per-manager assignments не создаются. Object source проверяет global permission до materialization, renderer не является security boundary.

### 4. Две стадии durable workflow

Московский weekly scheduler создаёт один `weekly-fkr-report.generate` job на report-week. Handler фиксирует перечень подходящих recipient identities и создаёт отдельный outbox intent на каждого с fingerprint `(template version, report week, recipient identity, scoped payload)`. Существующий `outbox.dispatch` выполняет transport и хранит outcomes. При обнаружении, что текущая схема не гарантирует required concurrency semantics, минимальное расширение остаётся в owner-модуле Jobs и миграции; параллельная система запрещена.

### 5. SMTP adapter и конфигурация

Transport использует SMTP AUTH/TLS через поддерживаемую Composer-зависимость либо уже доступный Yii mailer, выбранный после dependency inventory. Конфигурационные ключи имеют префикс `FMONITOR_SMTP_`; password не имеет default. Профиль `k2-mailer` документирует host/port/encryption/from без значения password. Сертификат проверяется в production; небезопасная настройка из legacy `k2` не наследуется. Test override — отдельная явная переменная с non-production guard.

### 6. Rendering и ссылки

Renderer выдаёт multipart-equivalent HTML и plain text из одной typed view model. HTML — отдельный email surface в incumbent FMonitor world: 680px presentation-table canvas, inline critical CSS, системная типографика, спокойная тёмно-синяя/нейтральная палитра и текстовые status labels. Никаких images/SVG/background-image/data URI/web-font/external resource. Hybrid-fluid table layout не полагается на flex/grid/media queries и деградирует в последовательную колонку; цвет дублируется текстом. Обе версии имеют одинаковые данные/порядок и экранируют всё содержимое. Absolute object URL строится из обязательного trusted public base URL и opaque canonical object id; email не принимает произвольные URL из данных.

### 7. Verification и архитектурные границы

Root напишет нормативный executable spec и tests до реализации. Integration tests проходят публичные seams report builder, scheduler/job handler и delivery adapter с fake SMTP server/transport; expected values заданы literal fixtures. Email markup lint запрещает внешние ресурсы и unsafe layout, а Chromium captures проверяют 680px desktop/web и 320px narrow render; Outlook compatibility подтверждается structural contract и независимым review, без ложного заявления о live Outlook automation. Schema/frontier inventory, compose readiness, backup/restore применимость и architecture manifests обновляются по фактическому diff. `rapid-pilot` остаётся только oracle: новых файлов и runtime calls там нет.

## Risks / Trade-offs

- [As-of история части прогресса может оказаться неполной] → fail-safe «Недостаточно данных», отдельная test fixture и запрет вывода нуля.
- [SMTP legacy использует отключённую проверку сертификата] → production profile требует peer verification; несовместимый сервер блокирует readiness вместо скрытого downgrade.
- [Список получателей или scope меняется между generate и retry] → логическая identity и scoped payload фиксируются при generate; адрес валидируется/разрешается по явно выбранной policy, описанной в executable spec до Gate 2.
- [Письмо слишком велико] → ограниченный набор полей, стабильная сортировка; pagination/attachments не вводятся. Предельный размер и поведение overflow фиксируются до Gate 2 после измерения production-like fixtures.
- [Дубликат при SMTP success с потерянным ACK] → exactly-once через обычный SMTP недоказуем; persisted pre-send claim и provider outcome обеспечивают at-most-one concurrent attempt, а неоднозначный transport outcome остаётся UNKNOWN и не выдаётся за успешную доставку. Если SMTP не предоставляет idempotency key, этот residual risk явно отражается в operations.

## Migration Plan

1. Добавить normative spec, RED tests, verification input/plan и независимый Gate 3.
2. Реализовать report module, wiring и при необходимости additive technical migration; выполнить focused checks.
3. Добавить SMTP dependency/config/readiness без committed secret, настроить deployment environment извне checkout.
4. Проверить test-recipient override на `k2-mailer` отдельной явной ручной операцией; live send выполняется только по отдельному разрешению владельца.
5. Провести Gate 5 и один exact-source GitHub CI run. Rollback отключает scheduler/worker registration; durable intents/history сохраняются, schema не откатывается разрушительно.
