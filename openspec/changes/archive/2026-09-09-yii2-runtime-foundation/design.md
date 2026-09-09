## Context

См. proposal и ADR0003. Production runtime собирает искусственный TCPDF autoloader;
для Yii2 нужен Composer lock. Изолированная foundation не обслуживает рабочие данные.

## Goals / Non-Goals

**Goals:** штатные Yii web/console lifecycle, детерминированный JSON health contract,
без изменения текущего установленного entrypoint. Existing RuntimeReadiness остаётся
единственным владельцем read-only readiness до следующего DB-boundary slice.

**Non-Goals:** auth/сессии, пользовательский HTML, перенос DB transactions и deploy стенда.
Их отсутствие явно отражается в issue76 stage2/3 tasks, не скрывается за health GREEN.

## Decisions

- `public/yii.php`, `bin/yii`, общий config + web/console config, Composer Yii2 2.0.55.
- Yii Request/Response/UrlManager/VerbFilter и Controllers; не писать второй router.
- HealthController вызывает существующий RuntimeReadiness с явной env config;
  invalid/unavailable readiness выдаёт безопасный общий ответ и nonzero console exit.
- Runtime path вне source; no global user identity, demo defaults, implicit DDL.
- Yii error handler/response events обеспечивают safe JSON и security headers.
- Изолированный Dockerfile PHP8.4/FPM/nginx; Composer install из lock. Current
  PHP8.5 runtime не переключается. Framework может быть проверен CI на старом PHP
  дополнительно, но compatibility claim требует отдельного 8.4 contour.
- Не выключать архитектурные checks для нового каталога; при ложном internal
  assumption record exact replacement maintaining public behavioral contracts.

## Risks / Trade-offs

- Ошибочно объявить полную готовность по health → этап2 остаётся открытым до real URL
  controller/read owner/HTML/assets и parity.
- Утечка exception/config через Yii default error → production debug false, generic
  JSON для infrastructure errors, исключение superglobal dumps из logger.
- Подмена общего Composer vendor старым TCPDF setup → обновить dependency bootstrap
  и проверить fresh install, сохранив pinned TCPDF revision.

## Migration Plan

Изолированная установка, intended RED и независимый Gate3; implementation/focused
GREEN + Gate5; один exact-source full CI кандидата. Merge foundation не переключает
стенд. Следующий срез переносит real read route, затем auth по отдельному mapping.
