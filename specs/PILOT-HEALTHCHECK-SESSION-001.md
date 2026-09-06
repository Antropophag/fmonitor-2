# PILOT-HEALTHCHECK-SESSION-001

Версия 0.2, 2026-09-07. Gate1 review required.

## Простыми словами

Проверка живости Docker повторно использует свою анонимную сессию. Регулярная
проверка не должна переполнять session storage и блокировать вход пользователей.

## Contract

Actor — Docker healthcheck. Public seam — запуск
`sh rapid-pilot/healthcheck.sh` с `FMONITOR_DEMO_PORT` (default8092) и
`FMONITOR_SESSION_STATE_ROOT` (default /home/fmonitor/.local/state/fmonitor2).
Порт — decimal1024..65535. State root абсолютный, существующий, принадлежащий
текущему uid каталог. Не использовать пользовательские credentials.

Сохраняется прежний охват: GET /pilot/objects и /pilot/installers на
http://127.0.0.1:<port>, следование штатному redirect на login. Success означает
доступность обоих HTTP paths с конечным HTTP200; это liveness анонимного portal
entry, не проверка process grants, DB readiness или готовность запуска.
HTTP4xx/5xx, timeout, некорректный порт/state root, ошибка cookie storage → exit1.
Конечный HTTP иной чем200 также exit1. Общий сетевой deadline каждого path2s;
max redirects3. Никаких skips, превращения 503 в success, отправки email/password.

При первом успешном запуске cookie хранится в healthcheck/cookies.txt под state
root, directory0700/file0600. Следующий запуск передаёт полученный cookie.
После первого успешного запуска 20 последовательных запусков в неизменном
окружении оставляют число session/lock файлов и их bytes неизменными. Cookie
сессия анонимна. Возврат недействующего cookie допускает штатный login recovery.
Cookie не печатается в stdout/stderr, URL остаётся exact initial origin http://127.0.0.1:<port>; redirects на иной
host/port/scheme запрещены до сетевого обращения, max3. Одновременные проверки сериализуются lock-файлом в том же
закрытом каталоге; ожидание не превышает1s, contention → exit1.

Root/healthcheck/cookie/lock symlink или чужой owner не принимаются; права
существующих cookie/lock0600 и healthcheck0700 проверяются, чужие файлы не
исправляются. HTTP body не выводится. Cookie — operational state, domain facts,
authorization и audit policy приложения не меняются. Health не открывает работы,
не применяет состав, не удаляет session files. Compose вызывает этот public seam.

## Verification

Изолированный реальный PHP HTTP fixture использует RapidPilotLocalAuth и native
session owner. Два защищённых пути redirect на login без process identity.
Проверки: повтор20 раз; 503 для каждого из двух путей; bad port/root; deadline;
cookie privacy; concurrent lock denial. Expected success0/failure1 независимо от
реализации. До нового script исходная Compose probe воспроизводит рост файлов.
RED нового CLI: отсутствующий script при здоровом HTTP fixture. Gate3 до code.
