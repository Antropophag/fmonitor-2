# PILOT-DEMO-BOOTSTRAP-001 — запустить воспроизводимый демонстрационный пилот одной командой

- Статус: `APPROVED`
- Версия: `0.2`
- Дата: `2026-08-29`
- Актор: локальный оператор демонстрации FMonitor 2.0
- Публичный seam: отдельный процесс `php bin/fmonitor2-pilot-demo.php [start|reset|status|cleanup]`
- Наследует: `PRODUCTION-MIGRATION-RUNNER-001`, `PILOT-CASE-IMPORT-001`, `PRODUCTION-COMPOSITION-001`, `ARTIFACT-STORE-001`, `PILOT-HTTP-AUTH-001 v0.12`, `PILOT-E2E-FLOW-001 v0.4`, `PILOT-SHLZ-ASSETS-001 v0.1`

## 1. Цель и граница

Оператор из чистого checkout запускает один короткий command и получает локальный configured production HTTP-пилот, в котором без SQL, редактирования файлов, подстановки cookie или иных ручных подготовительных действий можно пройти утверждённый путь:

```text
очередь объектов → карточка 4512 → монтажник 1042 и инженер 73
→ сформировать и скачать оба артефакта → ввести номер 12-Р
→ открыть работы датой 2026-08-29 → увидеть «В работе» и следующий шаг инженера
```

Bootstrap является только demo/deployment orchestration. Browser runtime остаётся `public/router.php`, команды выполняются только production `InstallationProcess`, downloads — production `AssignmentOrderArtifactService`, а данные процесса хранятся в настоящих MariaDB `fm2_*` tables. `app/demo`, in-memory delegates, runtime mocks, прямые SQL-мутации из HTTP и подключение к работающему legacy FMonitor запрещены.

Bootstrap создаёт минимальную **legacy-shaped source fixture** в своей demo database, затем применяет production migrations, заполняет deployment-owned user/capability/workforce configuration и вызывает production `PilotCaseImporter`. Fixture SQL допустим только внутри offline bootstrap до старта HTTP и не используется как process-command substitute.

Срез не улучшает test harness, не синхронизирует Bitrix, не переносит legacy history и не изменяет production deployment/login contract.

## 2. Команды и операторский контракт

Из repository root поддерживаются только:

```text
php bin/fmonitor2-pilot-demo.php start
php bin/fmonitor2-pilot-demo.php reset
php bin/fmonitor2-pilot-demo.php status
php bin/fmonitor2-pilot-demo.php cleanup
```

Без аргумента команда эквивалентна `start`. Лишний, повторный или неизвестный аргумент даёт exit `64` и одну redacted JSON line `{"ok":false,"reason":"CONFIGURATION_INVALID"}`. Команда не читает stdin.

`start` идемпотентно подготавливает отсутствующий demo generation, проверяет существующий, затем запускает foreground PHP loopback server. Первый успешный stdout до server log — следующий трёхстрочный banner:

```text
FMonitor 2.0 pilot: http://127.0.0.1:8092/pilot/objects
User: sidorov@shlz.ru · business time: 2026-08-29T12:00:00+03:00
Stop: Ctrl+C · reset: php bin/fmonitor2-pilot-demo.php reset
```

После этой строки URL уже отвечает. Сервер привязан только к `127.0.0.1`; bind failure не печатает success banner и возвращает redacted failure. Порт по умолчанию `8092`, optional `FMONITOR_DEMO_PORT` — canonical decimal `1024..65535`. Никакие DB credentials, filesystem paths или SQL не печатаются.

`status` не изменяет state и возвращает одну JSON line с exact keys `ok,running,url,generation,state`: `state` равен `ready`, `incomplete` или `absent`; process state конкретного объекта и secrets не выводятся.

`reset` разрешён только когда demo server этого checkout не запущен. Он создаёт новый полностью подготовленный generation и лишь после successful smoke атомарно делает его active; предыдущий generation сохраняется как recoverable backup. Затем `reset` запускает server и печатает тот же banner. Failure оставляет прежний active generation выбранным и пригодным к повторному `start`.

`cleanup` также требует остановленного server, проверяет owner marker/repository fingerprint и удаляет только поколения и артефакты этого demo. Чужая database, таблица, каталог либо generation без exact marker не изменяется. Команда сообщает количество удалённых поколений; после материального удаления сообщает, что восстановление bootstrap-ом невозможно. Сам cleanup никогда не затрагивает source checkout, `../fmonitor`, `../shlz-ui` или home root.

## 3. Конфигурация и безопасное владение

MariaDB defaults фиксированы для сохранённого локального demo service:

```text
host=127.0.0.1 port=23306 database=fmonitor2_demo
user=fmonitor2_demo password=fmonitor2_demo_local
```

Оператор может переопределить их только canonical `FMONITOR_DEMO_DB_HOST`, `FMONITOR_DEMO_DB_PORT`, `FMONITOR_DEMO_DB_NAME`, `FMONITOR_DEMO_DB_USER`, `FMONITOR_DEMO_DB_PASSWORD`; их grammar наследует migration runner. Bootstrap не читает legacy application config или generic `DB_*` aliases. Он не создаёт/drop database и не требует global DB privilege.

Каждый checkout получает стабильный lowercase 8-hex fingerprint из canonical repository realpath. Поколение использует два непересекающихся prefix длиной не более 32:

```text
fm2d_<fingerprint>_g<decimal>_       process
fm2l_<fingerprint>_g<decimal>_       legacy
```

Active-generation manifest и artifact roots находятся только под `~/.local/state/fmonitor2/pilot-demo/<fingerprint>/`; artifacts — в `generations/<n>/artifacts`, с inherited secure ownership/mode `ARTIFACT-STORE-001`. `/tmp`, workspace source tree и shared artifact root запрещены. Manifest записывается atomic replace и содержит только fingerprint, generation, prefixes, port и lifecycle state; DB password отсутствует.

В каждом generation есть bootstrap-owned marker с random nonce и repository fingerprint. `start` принимает generation только если marker, exact schema v4, fixture и artifact root совместимы. Partial generation не становится active; повтор безопасно продолжает/пересоздаёт только этот inactive generation. Bootstrap никогда не переиспользует prefix без совпавшего marker.

## 4. Детерминированная fixture

Legacy namespace содержит минимальные реальные таблицы `fm_maintable`, `users`, `users_roles`, достаточные production adapters; их column shapes соответствуют утверждённым adapter contracts. Данные:

- actor `18 / Сидоров Сергей Сергеевич / sidorov@shlz.ru`, active user и active role;
- engineer `73 / Анна Волкова`, active user и active role;
- object `4512`: `77-000123`, `Москва, ул. Примерная, д. 10`, entrance `2`, planned `2026-10-05..2026-12-20`, `responsstroicontrol=73`, zero/null completion and PTO;
- non-pilot legacy object `4999` с planned start `2026-09-30`, доказывающий explicit import boundary.

Process deployment configuration содержит actor capabilities `assignment_order.prepare`, `assignment_order.confirm_registration`, `installation.open`; engineer `73` имеет только `construction_control_engineer` и position `Инженер строительного контроля`.

`fm2_workforce_catalog` содержит employed installers, оба с source `one_c_zup_via_bitrix` и `workforce_source_updated_at=2026-08-27T18:15:00+03:00`:

| Табельный ID | ФИО | Должность | Период |
|---:|---|---|---|
| 1042 | Иванов Иван Иванович | Электромеханик по лифтам | `2024-02-01..null` |
| 2088 | Петров Пётр Петрович | Электромеханик по лифтам | `2025-01-10..null` |

Bootstrap вызывает importer только для `4512`; начальная projection — revision `1`, `needs_assignment_order`, без orders, artifacts, events, tasks и opening facts. `4999` не импортируется. Fixture не содержит подготовленного результата пути.

Production HTTP получает exact environment:

```text
REMOTE_USER=sidorov@shlz.ru
FMONITOR_NOW=2026-08-29T12:00:00+03:00
FMONITOR_TRUSTED_REQUEST_HOST=127.0.0.1:8092
```

и generation-specific DB prefixes/artifact root, repository `app/PilotHttp/pilot.css` и `FMONITOR_SHLZ_CSS_PATH`, указывающий на официальный standalone public export sibling design system:

```text
../shlz-ui/packages/styles/dist/shlz.css
```

Допустим иной resolved path только если он является public export path того же установленного `@shlz/styles`, оканчивается exact `/packages/styles/dist/shlz.css` и проходит весь filesystem/manifest contract `PILOT-SHLZ-ASSETS-001`; source entrypoint `../shlz-ui/packages/styles/shlz.css`, Showcase, `src/`, copied/local snapshot и автоматически выбранный «похожий» CSS запрещены. Bootstrap разрешает canonical repository-relative path через `realpath`, но передаёт HTTP composition абсолютный canonical public export path. Он не собирает `shlz-ui` и не исправляет отсутствующий dist.

До ready banner bootstrap требует readable exact public export и успешно построенный полный transitive `@import` graph. Browser получает root и каждый dependency только через same-origin routes `/pilot/assets/...`, определённые `PILOT-SHLZ-ASSETS-001`; bootstrap не считает наличие или `200` одного root `shlz.css` достаточным. Missing/wrong-basename/source-tree/symlinked/unreadable public export, invalid/escaping/missing dependency, graph limit/identity failure либо любой non-`200`/wrong MIME transitive smoke response дают redacted startup failure `SHLZ_ASSETS_UNAVAILABLE`, nonzero exit, остановку spawned server и отсутствие ready banner. Failure не печатает path, import target или filesystem details и не делает fallback.

`FMONITOR_NOW` — единственный clock для команды и business date; отдельный `businessDate` override запрещён. Все три demo-команды могут иметь один exact instant: append-only ordering остаётся по записи/ID, а order date и допустимая actual start согласованно равны `2026-08-29`.

Loopback development identity наследует `PILOT-HTTP-AUTH-001`: browser не вводит пароль, header или cookie identity; router переносит process environment `REMOTE_USER` в server variable только для loopback PHP server. Это не login и не разрешено на non-loopback bind. CSRF/session остаются production E2E contract; browser проходит формы обычными cookies. URL использует `http` только в этой loopback demo composition.

## 5. Observable launch smoke

До banner bootstrap сам делает public HTTP smoke через уже запущенный router, без SQL observation:

1. `GET /pilot/objects` с canonical Host возвращает `200`, stylesheet links в exact порядке `/pilot/assets/shlz.css`, затем `/pilot/assets/pilot.css`, и ровно один object link `/pilot/objects/4512`;
2. `GET /pilot/objects/4512` возвращает `200`, `Требуется распоряжение` и canonical prepare link;
3. `GET /pilot/objects/4512/assignment-order/prepare` возвращает `200`, selectable installer IDs `1042,2088`, prefilled engineer `73` and an enabled submit;
4. `GET /pilot/objects/4999` возвращает `404`;
5. `GET` root `/pilot/assets/shlz.css`, каждый member полного transitive manifest и `/pilot/assets/pilot.css` возвращает `200`, exact CSS MIME и exact configured bytes; unknown well-formed CSS asset отсутствует;
6. повторный `GET /pilot/objects` не меняет public projection.

Smoke не выполняет POST, не создаёт session/domain facts и не скачивает artifact. Любой mismatch завершает startup failure, останавливает spawned server и не печатает ready banner.

## 6. Полный ручной walkthrough и persistence

После banner оператор только в browser:

1. открывает printed URL и карточку `4512`;
2. открывает форму распоряжения, выбирает `1042 / Иванов Иван Иванович`, оставляет prefilled `73 / Анна Волкова`, подтверждает инженера и формирует распоряжение;
3. после PRG видит prepared version `1` и скачивает `Скачать распоряжение` и `Скачать приложение`; оба ответа — nonempty production stored HTML attachments;
4. вводит `12-Р`, сохраняет и видит `Зарегистрировано в 1С ДО`; файлы остаются прежними;
5. вводит actual start `2026-08-29`, открывает работы и видит `В работе`, дату, opening audit, `Чек-лист: Доступен` и `Инженеру строительного контроля: провести первую инспекцию объекта. Ответственный: Анна Волкова`;
6. возвращается в очередь и видит `4512 / В работе / Инженеру: провести первую инспекцию`.

Server restart через `start` без `reset` использует тот же generation. Новый browser GET восстанавливает final state и оба downloadable immutable artifacts из MariaDB/artifact store; bootstrap не reseed-ит, не возвращает state назад и не вызывает process command. Repeat `start` начального generation столь же сохраняет initial state.

## 7. Re-run, reset и отказоустойчивость

- повтор `start` при уже работающем exact owner PID/port даёт redacted `ALREADY_RUNNING`, не запускает второй server;
- stale PID распознаётся только после проверки process identity; чужой PID никогда не сигналится;
- `reset` generation `N → N+1` создаёт снова exact initial fixture и новый empty artifact root, сохраняя `N` неизменным;
- interrupted reset оставляет `N` active; следующий reset использует новый generation number и не принимает partial rows как success;
- отсутствие MariaDB, PHP `mysqli`, official sibling `../shlz-ui/packages/styles/dist/shlz.css` public export или любого его transitive dependency, writable secure state root или compatible schema даёт concise redacted failure и nonzero exit до banner;
- никакой failure не включает password, SQL, driver exception, absolute artifact/CSS paths или row payload.

## 8. Gate 2 seam и independently fixed expected values

Gate 2 запускает CLI как отдельный process против real MariaDB demo database с unique checkout fingerprint/state root under test user's home, затем обращается только к printed public HTTP URL и CLI `status`. Он не вызывает private bootstrap methods, production renderers как oracle или SQL для process assertions.

Один test доказывает: clean `start` provisioning and smoke; section 6 browser-shaped GET/POST/cookie/redirect/download journey; server stop/restart persistence; reset to initial state; cleanup ownership containment. Expected labels, people, dates, number, routes and final next step берутся литералами из разделов 4–6. Fixture setup может проверять bootstrap-owned table catalog для isolation, но успешные business facts наблюдаются только HTTP.

Обязательная sensitivity: official standalone dist export succeeds; source `packages/styles/shlz.css`, wrong-basename, missing root и broken/escaping transitive import fail closed before banner; every manifest member is browser-loadable with exact CSS MIME; occupied port; foreign marker/prefix; interrupted inactive generation; spoof identity headers do not change actor; non-imported `4999`; restart does not reseed; reset does not delete previous generation; cleanup refuses foreign paths/rows.

## 9. Authorization, audit и запреты

OS user and demo DB credential authorize offline provisioning. Browser actor authorization полностью production capability-based. Bootstrap не принимает actor ID, selected team, registration number or opening date as CLI inputs and не создаёт ложных domain events. Три успешных browser commands создают inherited immutable order/installers/artifacts and exact append-only process events; restart/reset orchestration itself does not append user process events.

Запрещено:

- писать/читать production or legacy application database;
- изменять `../fmonitor`, копировать source artifacts или компоненты `../shlz-ui`;
- обслуживать `app/demo` как часть walkthrough;
- seed-ить prepared/registered/working state либо восстанавливать его прямым SQL;
- auto-reset на `start`, удалять backup generation без `cleanup` или использовать `/tmp`;
- bind на `0.0.0.0`, принимать identity из inbound header или показывать demo credentials в UI.

## 10. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Approved by: separately tasked Gate 1 agent `/root/bootstrap_spec`
- Дата: `2026-08-29`
- Решение: `APPROVED`
- Комментарий: пользователь явно поручил автономно довести пилот до запускаемого browser journey без SQL/ручной подготовки, с real legacy-shaped и `fm2_*` data, детерминированным clock, безопасным reset и сохранением обязательного SSD + TDD workflow. Версия 0.2 закрепляет официальный standalone `packages/styles/dist/shlz.css` и обязательную browser-выдачу всего transitive public graph `PILOT-SHLZ-ASSETS-001`, исключая source-tree и root-only ложный success. Срез ограничен demo/deployment orchestration и не вводит альтернативную бизнес-реализацию.
