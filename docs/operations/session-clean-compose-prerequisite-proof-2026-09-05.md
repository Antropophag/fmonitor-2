# Clean Compose prerequisite proof для session restart

Дата: 2026-09-05. Исполнитель: `/root`.
Exact source SHA: `af75b2002766391ad0dce78bf609e4bbdeeadfa7`.
Это diagnostic deployment evidence, не launch approval и не qualifying
session-behavior RED.

## Isolation и build

Создан detached clean checkout этого SHA, без `.env`. Новый project
`fm2-session-check-3c9ce25b4a` использовал исходный compose.yaml и единственный
test-only override image name; project/env фиксировались явными inputs.
Bootstrap identity — fictional `session-proof@example.invalid`, password
создан для этого disposable contour и сохранён только в private external state.
Source import, Bitrix profile, existing demo и production secrets не использовались.
До создания доказано отсутствие exact containers/volumes/network и свободный8092.

`docker compose build pilot` — exit0. Exact image:
`sha256:b98963779a006f167986f082f7c7ff78f28e9fc4e30d66a6e11f9d8ecb7613d8`,
tag `fm2-session-check-3c9ce25b4a:af75b2002766`.
Read-only native hash probe независимо сравнил все382 tracked app/bin/public/
rapid-pilot files с clean checkout — совпадение. Первый probe завершился setup
exit255 из-за пропущенного Docker `-i`; после исправления только транспорта
тот же image дал полное exact совпадение. Это не было behavioral RED.

## Фактический startup

`docker compose up --detach --wait --wait-timeout 60 pilot` — exit1:
pilot unhealthy. MariaDB healthy, canonical migrations успешно создают33
tables, terminal12; последующие retries — version12/appliedVersions `[]`.
Pilot bootstrap даёт exact `{"ok":false,"reason":"MIGRATION_FAILED"}` и
exit70, container находился в restarting, restartCount11 при наблюдении.

Independent read-only schema inspection private MariaDB:

- обе object-detail v12 tables присутствуют;
- configured legacy object table отсутствует;
- configured generation sentinel отсутствует.

Read-only volume observation: artifact directory создан, active manifest
отсутствует. Bootstrap требует эти prerequisites и не публикует readiness при
их отсутствии. Поэтому public login и actual session cookie stop/start proof
пока недостижимы; подстановка credentials не исправляет этот blocker.
Связанные незавершённые slices: generation-metadata и отдельно gated setup/seed
prerequisites. Runtime repair/DDL для обхода отказа не добавлялись.

## Cleanup и durable evidence

После сохранения observations pilot остановлен. Project labels exact containers,
обоих volumes и network проверены; `docker compose down --volumes` удалил только
этот project. Затем independently доказано отсутствие всех пяти exact resources.
Чистый temporary worktree удалён. Основной test-db healthy и не изменялся этим
contour. Dedicated exact image сохранён для image-only verification; глобальный
`fmonitor2-pilot` tag не заменялся.

Private raw evidence:
`/Users/antropophag/.local/state/fmonitor2-verification/session-compose-uz0c2i_b`.
`evidence-manifest.json` SHA-256
`82709d917c428bee89e31f53f092b81809b201f2723edbb4739ed683464f9d09`;
он pin-ит build/startup/pilot logs, image/source hashes, independent schema/
volume observations и cleanup. Source/model/test files этим proof не менялись.
Clean deployment и session restart остаются непроверенными на успех.
