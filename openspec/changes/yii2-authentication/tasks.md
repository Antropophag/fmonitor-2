## 1. Контракт и RED

- [x] 1.1 Зафиксировать `YII2-AUTH-001` и проверить `openspec validate --strict`.
- [x] 1.2 Создать isolated MariaDB/session fixture и black-box HTTP RED; подтвердить, что failure вызван отсутствующим Yii auth route, а не setup.
- [x] 1.3 Получить независимый Gate3 APPROVED на spec, test и captured RED до production implementation.

## 2. Identity и session

- [x] 2.1 Реализовать canonical active `IdentityInterface` read и Argon2id login lookup через Yii DB; focused test доказывает active/invited/blocked/status semantics и неизменный hash.
- [x] 2.2 Настроить standard Yii Session с новым cookie namespace, persistent path, strict cookie attributes и close-before-send; base public test доказывает regeneration/restart/startup faults, затем отдельный independently reviewed fault test доказывает late close/write failure до завершения пункта.
- [x] 2.3 Реализовать двухшаговые login и POST-only logout через Yii User/Request CSRF; public HTTP test доказывает success, neutral rejection, rate limit, CSRF и logout.

## 3. Первый protected route

- [x] 3.1 Подключить `CheckAccessInterface` к canonical `AuthorizeLocalActor` без cache/fallback; matrix доказывает exact active-role permission и revoke на следующем request.
- [x] 3.2 Перенести `/pilot/admin/roles` на Yii controller и existing read/render owner; black-box test доказывает 200 HTML, 303 guest, 403 denied и 503 unavailable.
- [x] 3.3 Добавить architecture ratchet для отсутствия LocalAuth/custom session reachability на перенесённых routes и выполнить focused regression/architecture checks.

## 4. Review и поставка

- [x] 4.1 Получить независимый Gate5 на exact source и focused evidence (`reviews/code/YII2-AUTH-001-delivery.md`, source `694a2020`).
- [ ] 4.2 Выполнить один full exact-source CI и merge по принятой матрице без переключения рабочего стенда.
- [ ] 4.3 Записать фактические GREEN/evidence и следующий invitation/admin slice; #71/#76 остаются открыты до полного объёма.

Done: public auth/roles matrix GREEN, independent reviews approved, старый auth не
загружается этими routes, production stand/data не переключены и deferred slices
названы явно.
