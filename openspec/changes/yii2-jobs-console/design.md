## Context

`app/Jobs` уже реализует очередь, worker/scheduler processes, health и operator commands. `bin/fmonitor2-jobs.php` вызывает этот owner напрямую, а production compose добавляет временную manifest/Bitrix translation через `rapid-pilot/jobs-entrypoint.php`. Yii2 console пока содержит только health controller.

## Goals / Non-Goals

**Goals:** один Yii2 console bootstrap для worker/scheduler/health; byte-compatible публичный JSON и exit behavior; сохранение process prefix discovery, secret staging/cleanup, signals, durable queue и restart semantics; отсутствие transitive `rapid-pilot` load.

**Non-Goals:** изменение Jobs API/schema, расписания или workforce rules; перенос imports/migrations; удаление всего каталога `rapid-pilot`; переключение рабочего stand или DB upgrade/rollback.

## Decisions

1. Добавить тонкий Yii console controller/command composition, который валидирует только transport/runtime input и вызывает `JobsRuntimeCommand`; доменные факты остаются у `app/Jobs`.
2. Manifest translation и Bitrix secret staging переносятся в основной runtime package, а не копируются в controller. Временный token-файл удаляется в `finally`; значения секретов не входят в stdout/stderr.
3. Production compose вызывает общий Yii console launcher для `jobs/worker`, `jobs/scheduler`, `jobs/health`. Старый rapid entrypoint не вызывается и не загружается новым boundary. Runtime closure доказывается сочетанием actual included-file trace и полного repository-owned process-construction inventory; произвольные внешние child processes вне этого контролируемого графа не входят в утверждение.
6. Pilot image устанавливает единый locked Composer vendor так же, как production runtime image; ручная подмена `vendor/autoload.php` TCPDF-only загрузчиком несовместима с Yii console и удаляется.
4. Существующие job tables и факты не мигрируются. Проверка охватывает restart, slot deduplication, heartbeat health, invalid configuration, signal contract и runtime load closure.
5. Schema frontier, PDF/photo storage, sessions/offline actions и web routes не затрагиваются; общий upgrade/rollback и deployment gate №76 остаются открытыми.

## Risks / Trade-offs

- Yii может форматировать ошибки/логи иначе: controller отключает интерактивность и сохраняет закрытый JSON/exit contract.
- Bootstrap способен открыть DB или загрузить лишний runtime до валидации: RED проверяет invalid invocation/no facts и transitive load closure.
- Перезапуск может создать повторный slot: isolated compose test сравнивает durable state до и после restart.

## Migration Plan

После independently reviewed RED реализовать тонкую composition, получить focused GREEN и Gate 5, затем проверить один exact-source full CI. Rollback — вернуть compose entrypoints на прежний адаптер; schema и persisted facts не меняются.
