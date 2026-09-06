# Local UI preview — 2026-09-06

Owner спросил, когда можно локально поднять сайт и нажимать кнопки. Root поднял
отдельный Docker Compose project `fmonitor2-local-preview`, loopback8092.
URL `http://127.0.0.1:8092/pilot/login`. Synthetic login
`fmonitor-preview-testuser@shlz.ru`; credential хранится только во внешнем0600
`/Users/antropophag/.local/state/fmonitor2-local-preview/preview.env`.

## Owned resources and reproduction

External0700 directory `/Users/antropophag/.local/state/fmonitor2-local-preview`
содержит compose.override.yaml, ownership.json, seed-preview.php и HTTP captures.
Только project volumes `fmonitor2-local-preview_mariadb-data` и
`fmonitor2-local-preview_pilot-state`; существующий test-db23306 не менялся.
Текущий image `fmonitor2-local-preview:1eba93cf966d` built from exact source
1eba93cf966d49b85d56e1a3b38e2096a6618f0d. Обе services healthy.

Compose: main compose.yaml + external override, external --env-file, explicit
`-p fmonitor2-local-preview`. Bitrix profile/import-production не запускались.
Fresh canonical migrations1..13 прошли. Current docker bootstrap требовал
дополнительные source/auxiliary fixture tables; они созданы только в isolated
preview DB по существующим demo/runtime verification fixtures. Domain history,
signed originals и template PDFs не загружались. Bootstrap прошёл через public
identity application, без отключения authorization/readiness.

## Observed routes / remaining work

Authenticated HTTP checks after fix:
- /pilot/objects200 (пока0видимых объектов), /pilot/calendar200;
- /pilot/admin/roles200;
- /pilot/admin/users503 — отдельный ещё не исправленный request/session blocker;
- /pilot/installers403 — bootstrap superadmin не получает process capability автоматически.

Initial HTTP500 на users/roles/installers был Cannot redeclare PilotHttpRequest.
Исправлен отдельным PILOT-ENTRYPOINT-LOAD-001 через Gates1→RED→Gate3→GREEN→Gate5.
Initial/fixed captures сохранены во внешней папке; failure не превращался в skip.
CUA browser недоступен: выполнена HTTP-проверка входа, не visual browser QA.

Это промежуточный UI preview. Новые selection command/PDF без хранения/original
workflow ещё не подключены полностью. Полного make verify/VERIFY_OK нет, CI/PR/
remote deployment не выполнялись. Goal остаётся active. Следующий core шаг —
закрыть findings selection-core Gate3v1; local users503 тоже launch blocker.
