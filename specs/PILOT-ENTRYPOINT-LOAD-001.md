# PILOT-ENTRYPOINT-LOAD-001

Версия0.1,2026-09-06. DRAFT / independent Gate1 required.

## Простыми словами

Локальный портал не должен падать с HTTP500, когда login/router уже загрузил
общие HTTP-классы. Исправляется только повторное подключение dependencies.

## Contract

Actor — PHP router/bootstrap. Public seam — require
`app/PilotHttp/production-entrypoint.php`, возвращающий PilotHttpEntrypoint.
В новом PHP process допустимы: чистая загрузка; заранее require_once PilotHttp.php;
заранее require_once ProductionPilotHttpEntrypointFactory.php; повторное require
entrypoint в том же процессе. Каждый include возвращает объект PilotHttpEntrypoint
с callable handle без redeclaration/fatal error. Expected child stdout: LOAD_OK
с LF, exit0, stderr empty. Передавать DB/session credentials для construction не нужно.

Повторно объявлять classes нельзя. Каждый entrypoint создаётся штатной production
factory; authentication, routes, error handling, dependency implementation,
DB/filesystem/network operations выполняемых request этим срезом не меняются.
Construction не создаёт domain facts/audit и не требует нового authorization.

Gate1 → bounded child-process RED → independent Gate3 → minimal dependency-load
fix → focused GREEN/lint/architecture → independent Gate5. Затем проверить
ранее падавшие HTTP routes на отдельном synthetic local preview. Не менять
protected E2E, не подменять HTTP500 skips или false health/readiness.
