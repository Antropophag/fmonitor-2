# Изолированный Yii2 runtime (#76)

Этот контур развивается отдельно от deploy/runtime и не переключает рабочий стенд.
Он использует PHP 8.4, настоящий Composer vendor из общего lock и Yii web/console.
Первый operational slice проверяет liveness/readiness; это ещё не пользовательский
маршрут и не завершение #76. Авторизация и процессные маршруты добавляются далее.

Контракт: specs/YII2-RUNTIME-001.md. Состояние поставки и фактически выполненные
команды будут записаны в docs/operations/yii2-progress-2026-09-09.md.

Никаких секретов из .env рабочего стенда сюда не копировать. Отдельные ports,
project name, runtime state и тестовая DB выбираются для каждого изолированного
контура. Владелец разрешил пересоздать тестовые данные/пользователей и повторный
вход после переключения; новый Yii cookie namespace не переносит legacy sessions.

Обновления PHP/Yii/Composer lock требуют focused HTTP/CLI, fresh install и полного
CI кандидата. Production upgrade/rollback готовится по окончании migration slices.

Для локальной проверки foundation:

```sh
docker build -f deploy/yii2/Dockerfile -t fmonitor2-yii2:foundation .
FMONITOR_YII_IMAGE=fmonitor2-yii2:foundation docker compose -p fm2yii-foundation -f deploy/yii2/compose.yaml up -d --no-build
curl -i http://127.0.0.1:18076/health/live
curl -i http://127.0.0.1:18076/health/ready
FMONITOR_YII_IMAGE=fmonitor2-yii2:foundation docker compose -p fm2yii-foundation -f deploy/yii2/compose.yaml exec -T php php bin/yii health/live
FMONITOR_YII_IMAGE=fmonitor2-yii2:foundation docker compose -p fm2yii-foundation -f deploy/yii2/compose.yaml down
```

Ожидаются live `200 {"ok":true}` и ready `503 SERVICE_UNAVAILABLE`: foundation
compose намеренно не содержит database/configuration. Это не ошибка готового
контура данных: readiness будет 200 только с явно подготовленными storage/schema.
Host порт переопределяется `FMONITOR_YII_HTTP_PORT`; image — `FMONITOR_YII_IMAGE`.
Контейнеры non-root и read-only, записываемые каталоги — отдельные tmpfs. Для будущего
user runtime session/artifact paths станут постоянными volumes в его собственном
операционном срезе; текущая foundation не принимает пользовательские записи.
