## Context

См. proposal.md. Нормативный контракт: `specs/FEEDBACK-001.md`. Начальная база — origin/main 41573bf7. Root пишет spec/tests; отдельный sol/low executor реализует; независимый sol/low reviewer принимает Gates 3/5. Автономное авторство spec/tests не разрешено.

## Goals / Non-Goals

**Goals:** один журнал обратной связи в существующем Yii2 runtime, проверяемый через тот же application owner и HTTP.

**Non-Goals:** новые роли/система уведомлений, GitHub API, attachments, telemetry, изменения #40/#66 или deployment стенда. Для первого этапа версия — явно настроенная версия Yii приложения 2.0, не commit traceability.

## Decisions

- `app/YiiRuntime/FeedbackApplication.php` — Yii composition seam; правила и persistence — компактный `app/InstallationProcess/MariaDbFeedback.php` и при необходимости ограниченный helper. SQL/DDL только у допустимого persistence owner. Не создаём новую архитектурную подсистему ради двух команд.
- Две immutable таблицы `fm2_feedback` и `fm2_feedback_results`; numeric IDs, actor-scoped request UUID uniqueness, fingerprint normalized command, UTC actor/time. Result — добавление заметки без status machine; независимые заметки коммутируют, поэтому optimistic status version не требуется. Точный replay проверяется до добавления; unique constraint обеспечивает concurrency. Transaction/auth свежие; root/result никогда не UPDATE/DELETE.
- Owner нормализует context allowlist, version из trusted Yii config. Форма обычная, не modal/JS; скрытый requestId сохраняется при ошибке. Navigation helper вызывается существующим ViewSupport и четырьмя дублированными shells (objects/installers/users/roles). OTIZ files и checklist owners не затрагиваются. Существующие шрифты, spacing, shlz fields/buttons сохраняются; режим Operate. Визуальный контроль desktop/mobile по одному batch и исправление обнаруженного.
- Schema v25: новая `FeedbackSchemaMigration` в InstallationProcess и registration в существующем catalogue. Recovery V25 добавляет таблицы и AI inventory; V24 и исторические profiles не редактируются. Это необходимый прямой consumer: RuntimeRecovery сегодня сравнивает точный набор таблиц с V24 и иначе отклонит backup.
- Необязательная архитектурная baseline correction допустима только если checker действительно обнаружит новый public seam и отдельный reviewer явно одобрит ownership; без общего rebaseline debt. Общие verification/CI algorithms/workflows не меняются. Регистрация новых тестов в существующих categories/suites — только необходимая inventory запись, поскольку canonical CI использует явный список.

## Risks / Trade-offs

- Содержимое введённого текста может включать чувствительные данные → явная подсказка, без автоматического сбора, только admin access и HTML escaping; журнал не публикуется.
- Потеря ответа после commit → сохранённый request identity и одинаковый receipt при повторе, version upgrade не ломает replay.
- Старая recovery inventory отвергает новую schema → новый immutable V25 профиль и focused frontier/backup tests; предыдущие profiles сохраняются.

## Migration Plan

Перед публикацией focused schema tests проверяют чистую миграцию, repeat/conflict и v24 upgrade; runtime DDL запрещён. Применение существующим canonical migration command. Backup/restore сохраняет новые записи как часть текущей DB. Rollback коду не удаляет факты; downgrade schema не предлагается. Внешних сервисов/пакетов и новых env dependencies нет. Schema fixtures/explicit inventories проверяются по актуальному frontier, включая rapid-pilot verification consumers; любые необходимые изменения перечисляются delivery record.

## Persistence contract details

`fm2_feedback`: id BIGINT UNSIGNED AI PK, request_id CHAR(36), request_fingerprint CHAR(64), actor_user_id BIGINT UNSIGNED, description TEXT, page_path VARCHAR(255), nullable object_id BIGINT UNSIGNED, app_version VARCHAR(80), created_at DATETIME(6). `fm2_feedback_results`: id BIGINT UNSIGNED AI PK, feedback_id BIGINT UNSIGNED FK к root.id, request_id CHAR(36), request_fingerprint CHAR(64), actor_user_id BIGINT UNSIGNED, result TEXT, created_at DATETIME(6). Все остальные поля NOT NULL. В каждой таблице UNIQUE(actor_user_id,request_id); result index(feedback_id,id). Это минимальный явный список persisted facts; дополнительные metadata columns не нужны.
