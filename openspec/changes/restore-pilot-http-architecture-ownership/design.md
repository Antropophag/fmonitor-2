## Context

См. `proposal.md` — Why. Изменение пересекает временный rapid-pilot adapter и основной HTTP-модуль, но не меняет доменную модель или persistence schema. Текущие публичные oracle уже существуют: object-list/RBAC tests, owner-session user-access tests, CSP/asset tests и `tools/architecture/check`.

Архитектурное правило разрешает SQL в `app/PilotHttp/MariaDb*.php`, запрещает новый SQL-owner в `rapid-pilot`, а hotspot ratchet запрещает рост уже крупных coordinator/router. `rapid-pilot` может владеть presentation wiring, но не доменными фактами. Все три ветви являются read/transport behavior; новых state-changing application seams нет.

## Goals / Non-Goals

**Goals:**

- Один явный MariaDB-владелец формирует полный read model очереди объектов.
- Один связный user-access HTTP owner обрабатывает owner-backed session и административные user routes, а `PilotE2ECoordinator` только делегирует совпавший маршрут.
- Один presentation asset owner обслуживает file-type SVG, а корневой router только распознаёт и передаёт маршрут.
- После переноса `tools/architecture/check` не видит три новых ObjectQueue SQL fingerprint и рост двух hotspot относительно сохранённого baseline.
- Текущие публичные ответы, permissions, данные и история остаются byte/semantic-equivalent в пределах существующих oracle.

**Non-Goals:**

- Не объединять все PilotHttp handlers и не завершать полный strangler migration.
- Не менять schema, query semantics, status taxonomy, UI, session format или auth policy.
- Не добавлять fallback authority, новые routes, headers, cache policy или asset source.
- Не запускать deployment, import, stand restart, production/remote mutation или CI publication.

## Decisions

1. **Очередью владеет `app/PilotHttp/MariaDbObjectQueue`.** Класс получает уже проверенные `mysqli`, process prefix и legacy prefix; публичный `read(q, status, page, size)` возвращает прежние `objects` и `filters`. В него целиком переносятся source predicate, latest-order/application joins, completion predicates, search escaping, count/page validation, sorting, row validation, date normalization и status projection. `RapidPilotObjectQueue` сохраняет HTTP input extraction, local user/RBAC, completion decoration, scheduling buttons и rendering. Это следует существующему `MariaDbConstructionControlQueue`; перенос отдельных SQL-строк или baseline update оставил бы неясное владение.

2. **User-admin session и route handling извлекаются связным блоком.** Новый collaborator в `app/PilotHttp` получает необходимые identity/dependency/session collaborators и обслуживает только `/pilot/admin/users`, invite и role-assignment routes при owner-backed session. Он владеет route matching, trusted local identity/CSRF handoff, invite-only PRG, flash consume/publish и отображением storage fault как 503. `PilotE2ECoordinator` делегирует route и сохраняет общую CSP/response policy через малый явный response collaborator либо переданный типизированный интерфейс. Приватный coordinator method не становится публичным callback: это создало бы ложный public seam и оставило бы state coupling. Альтернатива вынести только formatting flash отклонена, потому что она не убирает `ownerSessionState` lifecycle и не уменьшает hotspot по связной ответственности.

3. **Session state имеет одного владельца на запрос.** Извлечённый handler загружает state один раз, меняет локальную копию, выполняет не более одной требуемой публикации и очищает request-local state в `finally`. При publish failure успешное административное тело/redirect не выдаётся. Существующие cookie name, scheme, Origin/Sec-Fetch-Site, HEAD и payload-codec правила сохраняются. Это защищает последовательность invite flash и role change от частичной записи.

4. **File-type assets остаются presentation adapter.** Малый `RapidPilotFileTypeAsset` (либо эквивалентный существующему asset boundary класс) предоставляет `matches(path)` и terminating `handle(path)`. Он принимает только прежний `[a-z0-9-]+`, читает только публичный `../shlz-ui/packages/icons/dist/file-types`, сохраняет generic fallback и точные response headers/body semantics. Router содержит одну делегацию. Перенос в domain/application module отклонён: asset bytes не являются прикладным фактом.

5. **Baseline неизменяем в этом slice.** Успех доказывается удалением новых findings и фактическим уменьшением hotspot-файлов, а не признанием долга. Новые owner-файлы должны оставаться ниже hotspot threshold и не создавать SQL/dependency/session-storage findings.

6. **Проверка следует затронутым публичным маршрутам.** Для queue используются `local_rbac_objects_route_admission_001_test.php`, `pilot_object_list_001_test.php`, `rapid-pilot/verify-object-queue-filters.php` и обновлённый source-boundary verifier. Для session-admin — `pilot_session_storage_user_access_fault_001_test.php`, `pilot_session_storage_accepted_payload_http_001_test.php`, `pilot_local_trusted_scheme_001_test.php` и релевантный auth test. Для assets/CSP — `pilot_shlz_assets_001_test.php`, `pilot_route_csp_inventory_001_test.php` и `pilot_route_csp_001_test.php`. Ожидания меняются только там, где тест законно фиксировал прежнее внутреннее размещение; публичные ожидания не ослабляются.

## Risks / Trade-offs

- [Динамический SQL после переноса меняет фильтр или порядок] → переносить query/projection одним блоком, сохранить independent literal route assertions для каждого status/search/page и проверить invalid input.
- [Session extraction нарушает flash/CSRF atomicity] → извлекать полный request-local lifecycle, проверить success, repeat consumption и forced publish fault через существующие storage tests.
- [Handler начинает доверять request-selected actor] → принимать actor только из owner session и повторять server-side capability check на административном действии.
- [Asset extraction меняет HEAD/body или заголовки] → закрепить текущие raw HTTP tuples до реализации и сравнить exact content type, length, cache и nosniff для exact/fallback/missing cases.
- [Тест подстраивается под новый класс] → сохранять public HTTP oracle; source verifier может проверять только отсутствие SQL в adapter и вызов назначенного read owner.
- [Параллельные рабочие изменения пересекают auth tests] → не перезаписывать чужой working-tree diff; согласовать узкий file ownership и перед review зафиксировать exact hashes.

## Migration Plan

1. Зафиксировать текущие public HTTP результаты и architecture failure как pre-change evidence без stand/data действий.
2. Последовательно извлечь queue read owner, user-admin session handler и file-type asset handler, после каждого переноса выполнить его focused regression.
3. Выполнить PHP lint, `git diff --check` и `PATH=/opt/homebrew/bin:$PATH tools/architecture/check`; baseline не менять.
4. Передать production diff, неизменённые public expectations и verification output независимому code reviewer.
5. Для отката вернуть только wiring и новые owner-файлы на предыдущие repository bytes; schema/data rollback отсутствует.

Полный `make verify` и production integration gates остаются отдельным последующим доказательством согласно текущему delivery mode; их отсутствие не отмечается как APPROVED или Done.
