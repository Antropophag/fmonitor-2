# PILOT-LOCAL-TRUSTED-SCHEME-001

Версия0.2,2026-09-07. Independent Gate1 required.

## Простыми словами

Экран пользователей в локальном Docker preview возвращает503 из-за отсутствующей
настройки доверенной схемы запроса. Явно задаём http для существующего loopback
HTTP профиля. Backend продолжает отклонять не настроенную/недопустимую схему.

## Contract

Actor: локальный активный администратор с exact `access.administer` и валидной
owner-backed сессией. Oracle: PILOT-SESSION-STORAGE-001 §6 trusted scheme;
существующий Compose публикует прямой HTTP только127.0.0.1:8092 безTLS termination.

Public configuration seam: `docker compose -f compose.yaml config --format json`
с synthetic bootstrap credentials и `--env-file /dev/null`. Effective
services.pilot.environment.FMONITOR_TRUSTED_REQUEST_SCHEME MUST равняться literal
`http`. Pilot published port сохраняет host_ip127.0.0.1,published8092,target8092.
Ни host environment, ни входящий HTTP Forwarded/X-Forwarded-Proto не выводят схему.
Другой deployment profile отвечает за explicit https отдельно.

Public runtime seam: real rapid-pilot/router.php HTTP GET `/pilot/admin/users`
с native synthetic local actor99 (active system_admin) и явно назначенным
`access.administer`, native public session storage. Схема для test server берётся
из effective Compose config, не из независимо подставленного test default.
GET200 возвращает страницу «Пользователи» с действительными32hex action tokens;
эти tokens записаны owner storage до выдачи ответа. Повторный GET200 сохраняет
работоспособность. Все DB/facts/roles/capabilities неизменны при GET; private
original storage не меняется. Обычное добавление session tokens допустимо.

Контроль без trusted scheme (unset или пустое значение) возвращает503 и exact
`Service unavailable.\n`, без раскрытия страницы/новых tokens. Поддельный header
`X-Forwarded-Proto: http` не исправляет missing/invalid config. Никакого backend
fallback/default, relaxed authorization, native session bypass или нового grant.

Этот срез меняет только configuration binding, не session/application contract.
Нет domain writes/audits, migrations, imports, нового code baseline или изменения
protected E2E. Runtime framework/read-only authorization наследуются неизменно.

## Delivery / operations

Gate1→native real-router RED with healthy login + local directory setup→Gate3→
one-line configuration GREEN→related session regressions/config validation→Gate5.
Native proof runs in task-owned isolated DB/session resources, not preview data.
После scoped approval можно применить ту же explicit env к existing owned local
preview с прежним exact image, без build, новых migrations, production imports, volume reset или grants.
Штатный startup старого image может выполнить свой idempotent bootstrap;
единственная допустимая DB разница — manifest_nonce существующей generation
sentinel (ровно одна существующая строка), совпадающий с новым active.json.
Остальные поля active.json неизменны. Generation/fingerprint и все прочие
DB rows/DDL сохраняются exact. Перед/после сохраняются hashes и все ранее
существующие session bytes. Snapshot после recreation берётся ДО нового smoke
login; smoke создаёт новую task-owned session и сохраняет нормальный login audit.
Health/login/users/roles проверяются, старые session bytes остаются exact. Это configuration-only recovery, не deploy
нового source, не fullVERIFY или launch approval. Remote mutations запрещены.
