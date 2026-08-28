# PILOT-HTTP-AUTH-001 — открыть authenticated read-only оболочку пилота

- Статус: `APPROVED`
- Версия: `0.12`
- Дата: `2026-08-28`
- Актор: активный legacy-пользователь, аутентифицированный доверенным HTTP-сервером
- Публичный seam: HTTP `GET|HEAD /pilot/`

## 1. Цель и граница среза

Дать production и локальному пилоту один минимальный HTTP entry point: доверенная server identity разрешается в exact активного пользователя legacy FMonitor, после чего приложение отдаёт read-only оболочку FMonitor 2.0 на `shlz-ui`.

Срез доказывает вертикальную цепочку:

```text
authenticated server variable
→ active legacy user + active legacy role
→ safe HTML pilot shell
```

Карточка объекта не включается: текущий `InstallationProcess.getInstallationObjectProcess(id)` не является object-card read model и не возвращает legacy identity для пустого импортированного дела. Смешивание authentication с новым SQL projection сделало бы два независимых behavior gates. Следующий срез `PILOT-OBJECT-CARD-001` добавит authenticated GET одного явно импортированного дела.

## 2. Почему не legacy session и не собственный login

Legacy `Auth` выполняет LDAP/internal password authentication, затем кладёт `md5(users.id)` в CodeIgniter file session `ci_session`. Cookie settings legacy допускают insecure/non-HttpOnly режим, session storage path является runtime-конфигурацией CodeIgniter, а хэш ID сам по себе не является credential.

FMonitor 2.0 поэтому:

- не разбирает `ci_session` и не доверяет `md5(user.id)` из cookie/header;
- не принимает password и не дублирует LDAP/internal login;
- не создаёт собственную authentication cookie/session;
- принимает identity только после authentication на web-server/SSO boundary и заново проверяет active user/role в общей legacy DB.

Это совместимо с legacy deployment на том же host/database, но не наследует слабую cookie boundary. Настройка общего корпоративного auth upstream относится к deployment; приложение остаётся deny-by-default.

## 3. Trusted identity contract

Единственный identity input:

```text
$_SERVER['REMOTE_USER']
```

Он обязан быть установлен web server/FastCGI process configuration, а не преобразован приложением из request header. Значение:

- PHP string длиной `3..254` bytes;
- ASCII only;
- exact regex `/^[A-Za-z0-9.!#$%&'*+\/=?^_`{|}~-]+@[A-Za-z0-9.-]+$/D`;
- не trim-ится, не lower-case-ится и не split-ится по `\\`, `@` или `;`;
- не содержит control/NUL/whitespace.

Приложение всегда игнорирует `HTTP_REMOTE_USER`, `HTTP_X_REMOTE_USER`, `HTTP_X_FORWARDED_USER`, `HTTP_X_AUTH_REQUEST_EMAIL`, `Authorization`, cookies и query/form values как identity sources. Их наличие не заменяет отсутствующий `REMOTE_USER` и не переопределяет присутствующий.

Production reverse proxy обязан до FastCGI удалить/перезаписать любые inbound headers с identity-like names, выполнить authentication и выставить server/FastCGI param `REMOTE_USER` только из своего authenticated principal. Deployment smoke test отправляет все перечисленные spoof headers и доказывает, что они не влияют на principal.

## 4. Exact legacy resolution

После connection/charset `utf8mb4` приложение выполняет параметризованный lookup только в configured legacy namespace:

```text
users u JOIN users_roles r ON r.id = u.role_id
WHERE BINARY u.email = BINARY :remoteUser
  AND u.status = 1
  AND r.status = 1
```

Выбираются только `u.id`, `u.name`, `u.email`. Успех требует ровно одну строку, positive integer ID, nonblank trimmed display name и byte-exact email. Zero rows либо две и более строки fail closed как forbidden. DB collation не делает principal case-insensitive.

Legacy role name/ID, `users_rights2roles` и process capabilities не дают authentication. Capability будет отдельно проверяться каждой будущей process command существующим domain seam. Read-only shell доступен любому exact active user с active role.

Lookup не пишет `users.lastlogin`, legacy `logs`, process/security events или session data: upstream authentication, а не shell GET, является login event.

## 5. HTTP routing

Front controller обслуживает только prefix `/pilot`:

| Request | Result |
|---|---|
| `GET /pilot` | `308` с `Location: /pilot/`; body empty; query не сохраняется |
| `HEAD /pilot` | тот же `308` без body |
| `POST|PUT|PATCH|DELETE /pilot` | `405` до redirect/config/DB/CSS и без чтения body |
| `GET /pilot/` | authenticated shell section 6 |
| `HEAD /pilot/` | те же status/headers и exact `Content-Length` GET body, body empty |
| `GET|HEAD /pilot/assets/shlz.css` | configured public `@shlz/styles` export, section 7; authentication не требуется |
| любой иной path под `/pilot` | `404` generic response |
| иной method на известный route | `405`, `Allow: GET, HEAD`; request body не читается |

Routing использует URI path после percent-decoding ровно один раз и отклоняет invalid encoding, NUL, encoded `/` or `\\`, duplicate slash, dot segment и path-info suffix как `404`. Query не участвует в route или identity. Request Host проходит только integrity boundary раздела 5.1 и после успешной проверки не участвует в route, identity или rendering. Redirect `Location` всегда literal relative `/pilot/`, поэтому Host не отражается.

Unknown authenticated application routes возвращают `404` независимо от identity validity; `/pilot/` выполняет authentication. Asset route выдаёт только фиксированный configured file и не принимает filename/path parameter.

### 5.1. Request Host integrity boundary

До route matching, identity validation, configuration/DB/filesystem access приложение требует ровно один syntactically valid request `Host`. Оно читает raw SAPI representation, не normalizes и не выбирает один элемент из duplicate/combined value.

Valid Host — ASCII string длиной `1..253` bytes без control, whitespace, comma, slash, backslash, `@`, `#`, `?`. Он является либо DNS name из непустых labels `[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?`, разделённых `.`, без trailing dot; либо IPv4 из четырёх decimal octets `0..255` без leading zero (кроме literal `0`); либо bracketed IPv6, чьё содержимое принимается `inet_pton(AF_INET6)` и не содержит zone ID. Допустим один suffix `:<port>`, где port — canonical decimal `1..65535` без leading zero. Userinfo, scheme, path, percent-encoding и второй authority запрещены.

Missing/empty Host, duplicate Host, SAPI-combined value (`a.example,b.example` либо `a.example, b.example`) и любое нарушение grammar немедленно возвращают:

```text
400
Content-Type: text/plain; charset=UTF-8
Bad request.\n
```

Response содержит все security/cache headers раздела 8 и exact content length, не содержит rejected Host в body, `Location`, logs или application-controlled headers. Identity resolver, DB и configured CSS file не вызываются/не читаются. Это request-integrity check, не authentication, virtual-host authorization или source для абсолютных URL.

Production и local transport имеют разные доказуемые raw boundaries, но передают одной application grammar один canonical value:

- production trusted HTTP server обязан отвергнуть missing/duplicate/malformed raw Host **до PHP**, не объединять и не выбирать один duplicate; после проверки он выставляет per-request FastCGI/server variable `FMONITOR_TRUSTED_REQUEST_HOST` ровно в canonical single Host value;
- production application читает только `FMONITOR_TRUSTED_REQUEST_HOST`; inbound header с тем же или похожим именем удаляется server-ом и не может создать server variable;
- отсутствие/malformed trusted variable в production fail closed как exact application `400`; deployment test отдельно доказывает server-level duplicate rejection и что PHP handler/DB не вызваны;
- local `PHP_SAPI=cli-server` не имеет такого trusted pre-boundary и читает `HTTP_HOST`; raw-socket Gate 2 сохраняет v0.4 duplicate/aggregation sensitivity и transport exception раздела 8;
- application-normalized request содержит одно поле `host`, полученное из соответствующей trusted boundary; дальнейшие route/identity/render seams не видят raw Host arrays/headers.

Наличие `FMONITOR_TRUSTED_REQUEST_HOST` в обычном process environment не является local bypass: local contract всегда выбирается по exact `PHP_SAPI=cli-server` и игнорирует эту переменную. Production deployment не разрешает клиенту управлять FastCGI params.

## 6. Successful shell

Fixture:

```text
users_roles: { id: 5, status: 1 }
users: { id: 18, name: "Сидоров Сергей Сергеевич", email: "sidorov@shlz.ru", role_id: 5, status: 1 }
REMOTE_USER=sidorov@shlz.ru
```

`GET /pilot/` возвращает `200` и `Content-Type: text/html; charset=UTF-8`. DOM contract:

- `<!doctype html>`, `<html lang="ru">`, one `<meta charset="utf-8">`;
- `<link rel="stylesheet" href="/pilot/assets/shlz.css">`;
- `<body class="shlz-scope">`;
- one skip link to `#main-content`;
- `<header>` contains product name `FMonitor 2.0` and escaped display name `Сидоров Сергей Сергеевич`;
- `<nav aria-label="Основная навигация">` contains one current link `Моя работа` to `/pilot/` and disabled/non-link text `Объекты монтажа` with `aria-disabled="true"` until its slice;
- `<main id="main-content" tabindex="-1">` contains `<h1>Моя работа</h1>`, status text `Пилот подключён` and explanation `Объекты монтажа появятся после подключения карточки.`;
- no form, password field, inline script/style, mutation link, fake data, object count or role switcher.

All DB-derived text is HTML-escaped with quotes/substitution; no value enters raw attributes, CSS, URL or markup. Page uses public `shlz-ui` classes/components (`shlz-scope`, typography, status/tag/link primitives) and a small application layout stylesheet only in a later separately specified asset if needed. This slice does not copy shlz tokens/components or depend on showcase CSS.

Exact bytes may differ by insignificant HTML whitespace; Gate 2 observes parsed DOM, visible strings, link targets and absence contract independently.

## 7. `shlz-ui` public asset boundary

Required environment `FMONITOR_SHLZ_CSS_PATH` points to the deployment-installed public export corresponding to `@shlz/styles/shlz.css` (`packages/styles/dist/shlz.css` in sibling development checkout). It must be an absolute path to an existing readable regular file, not a symlink, with basename `shlz.css`; invalid/unreadable state causes shell initialization failure, never fallback/copied CSS.

Validation and bytes are bound to one opened descriptor:

1. `lstat(configuredPath)` must report a non-symlink regular file;
2. open that exact path once in read-only binary mode;
3. `fstat(openedDescriptor)` must report a regular file, and its device/inode/type must equal the pre-open `lstat` identity;
4. repeat `lstat(configuredPath)` after open and require the same device/inode/type and non-symlink state;
5. read all CSS bytes only from that already validated descriptor, never by reopening/path helper;
6. final `fstat` must retain device/inode/type and the byte count read must equal its stable size; then close descriptor.

Любое mismatch, short read, read/stat failure или path replacement возвращает `503`; partial bytes не отправляются. Замена directory entry после identity revalidation не меняет already-open descriptor bytes, но detected pre-open/open/revalidation swap fail closed. Gate 2 synchronizes a regular-file→symlink and regular-file→other-regular-file swap between initial `lstat` and open/revalidation; ни target, ни replacement bytes не выдаются.

«Descriptor» в этом контракте означает только owner-level PHP stream resource, открытый `fopen(..., 'rb')`. FFI, integer/raw file descriptor, `fileno`, `dup`, direct libc `open/read/close`, subprocess/proc ownership и смешивание PHP/libc ownership прямо запрещены. Один resource имеет ровно одного `PhpCssDescriptor` owner.

`GET /pilot/assets/shlz.css` returns its exact bytes with:

```text
200
Content-Type: text/css; charset=UTF-8
Content-Length: exact byte count
```

`HEAD` returns no body. Asset bytes are read only from startup-validated configured path, never selected by request input. No local imitation or modification of `../shlz-ui` occurs.

## 8. HTTP security headers and cache

Every response under `/pilot`, including errors, redirect and CSS, includes:

```text
X-Content-Type-Options: nosniff
Referrer-Policy: no-referrer
X-Frame-Options: DENY
Content-Security-Policy: default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'
Permissions-Policy: camera=(), microphone=(), geolocation=()
Cross-Origin-Opener-Policy: same-origin
Cache-Control: no-store
```

No response contains `Access-Control-Allow-Origin`, `Set-Cookie`, `Server`, `X-Powered-By`, stack trace or debug toolbar. Application removes PHP `X-Powered-By`; production web server is responsible for suppressing its own version banner.

Узкое transport exception для утверждённого local `php -S` smoke/Gate 2: built-in SAPI может автоматически добавить ровно один response header `Host`, byte-for-byte равный **успешно проверенному** request Host. Этот header создаёт только SAPI после application handler; приложение не использует valid Host для identity/routing/rendering и не копирует его в `Location`, body, links или иной application header. Gate 2 для local `php -S` допускает только это exact valid-Host echo и проверяет отсутствие второго `Host`; production deployment обязан удалить response `Host` и не получает это исключение.

PHP 8.5 `cli-server` передаёт duplicate/invalid Host приложению и добавляет response `Host` вне управляемого router-ом header list. `header_remove()`/replacement не удаляет этот SAPI header, а попытка поставить безопасное значение создаёт второй `Host`. Поэтому local `400` имеет следующее узкое evidence-backed исключение:

- при одном доставленном непустом invalid Host допускается ровно один unavoidable SAPI response `Host`, byte-for-byte равный доставленному raw value;
- при двух duplicate Host values `v1`, `v2` SAPI может сохранить две raw response lines `Host: v1`, `Host: v2` в этом порядке либо одну line `Host: v1,v2` / `Host: v1, v2`; иных separators, reorder, normalization или values Gate 2 не принимает;
- приложение не вызывает `header('Host: ...')` и не добавляет ещё один Host для непустого/duplicate case;
- при действительно missing Host, когда SAPI не создал echo, local router ставит единственный fixed `Host: rejected.invalid`; request value для отражения отсутствует;
- во всех cases status/body/security headers остаются exact `400` contract, а raw Host встречается только в unavoidable response header — никогда в body, `Location`, link, log, error text или другом header.

Gate 2 посылает ровно два заранее заданных duplicate values, фиксирует отправленные raw Host lines, затем сравнивает response на уровне raw header lines, не через client library, которая необратимо объединяет duplicates. Любой extra Host value, изменение порядка/байтов кроме двух exact separators выше, application duplication либо отражение вне Host header делает test красным. Это исключение существует только для loopback `PHP_SAPI=cli-server`; production reverse proxy обязан не пропускать response Host вовсе.

Exception не ослабляет запреты на credential/principal/header reflection: spoof identity headers, cookies, `Authorization`, DB/env/path values и arbitrary Host bytes по-прежнему не появляются в application-controlled output.

For dynamic/error plain text or HTML, exact UTF-8 `Content-Length` is emitted. `HEAD` headers equal corresponding GET headers including length, except body is empty.

## 9. Authentication/error results

`/pilot/` exact outcomes:

| Condition | Status / media / exact visible body |
|---|---|
| missing/malformed/non-string `REMOTE_USER` | `401`, `text/plain; charset=UTF-8`, `Authentication required.\n` |
| syntactically valid but absent/inactive/ambiguous user or inactive/missing role | `403`, `text/plain; charset=UTF-8`, `Access denied.\n` |
| DB connect/charset/query failure or invalid CSS path | `503`, `text/plain; charset=UTF-8`, `Service unavailable.\n`, `Retry-After: 60` |
| unknown route | `404`, `text/plain; charset=UTF-8`, `Not found.\n` |
| wrong method | `405`, `text/plain; charset=UTF-8`, `Method not allowed.\n` |
| missing/duplicate/invalid Host | `400`, `text/plain; charset=UTF-8`, `Bad request.\n` |

`401` deliberately omits `WWW-Authenticate`: приложение не запускает Basic auth и не знает authentication scheme. Все errors obey section 8 and reveal no distinction beyond listed categories.

DB lookup occurs only after valid route/method, valid server identity and valid startup config. Missing identity does not query DB. Invalid/forbidden identity response never contains submitted principal or user facts.

Exact evaluation priority removes mixed-failure ambiguity:

1. validate Host integrity → `400` before every other application action;
2. normalize route and identify known terminal route without executing it; unknown route → `404`;
3. reject unsupported method on every known route, включая `/pilot`, → `405` without reading body/config/DB/CSS;
4. only `GET|HEAD /pilot` executes literal `308` redirect;
5. CSS route validates/reads only configured CSS and returns `200` or `503` without identity/DB;
6. shell route validates `REMOTE_USER`; missing/malformed → `401` even if DB/CSS configuration is also broken;
7. validate shell configuration/connect/query; failure → `503`;
8. zero/ambiguous/inactive identity → `403`; exact active identity → `200`.

## 10. CSRF and mutation boundary

Срез не имеет state-changing route, form, cookie или bearer token, поэтому CSRF token не выпускается. `POST`, `PUT`, `PATCH`, `DELETE` на любой известный route возвращают `405` до чтения body и до DB/filesystem access.

Это не разрешение будущим POST работать только на `REMOTE_USER`: первый mutation slice обязан отдельно определить cryptographically random session-bound CSRF token, exact `Origin`/`Host` policy, content type/body limits и Post/Redirect/Get. GET/HEAD никогда не вызывают process commands.

## 11. Composition/configuration

HTTP bootstrap использует обязательные environment values:

```text
FMONITOR_DB_HOST
FMONITOR_DB_PORT
FMONITOR_DB_NAME
FMONITOR_DB_USER
FMONITOR_DB_PASSWORD
FMONITOR_LEGACY_TABLE_PREFIX
FMONITOR_SHLZ_CSS_PATH
```

DB fields и prefix наследуют exact presence/empty/regex contract `PILOT-CASE-IMPORT-001`; password и legacy prefix должны присутствовать, explicit empty допустим. HTTP bootstrap не применяет migrations и не создаёт process factory: shell требует только identity resolver и renderer. Connection charset `utf8mb4` подтверждается до lookup.

### 11.1. Минимальный публичный composition API

Ниже — обязательные namespace/type/method seams первого HTTP-среза. PHP parameter/property types и return types являются частью testable API; внутренние helpers, SQL и parsing strategy не предписываются.

```php
namespace FMonitor2\PilotHttp;

final readonly class PilotHttpRequest
{
    public function __construct(
        public string $method,          // normalized uppercase token
        public string $path,            // validated, once-decoded path
        public string $host,            // validated canonical transport value
        public mixed $serverIdentity,   // exact REMOTE_USER value or null/non-string
    ) {}
}

final readonly class PilotHttpResponse
{
    /** @param array<string,string> $headers */
    public function __construct(
        public int $status,
        public array $headers,
        public string $body,
    ) {}
}

final readonly class HttpUser
{
    public function __construct(
        public int $id,
        public string $displayName,
        public string $email,
    ) {}
}

interface TrustedServerIdentity
{
    /** @throws InvalidServerIdentity */
    public function resolve(mixed $serverIdentity): string;
}

interface HttpUserDirectory
{
    // null represents absent, inactive, dangling-role or ambiguous identity: all are 403.
    public function resolveActiveUser(string $principal): ?HttpUser;
}

interface PilotShellRenderer
{
    public function render(HttpUser $user): string;
}

interface CssAsset
{
    /** @throws CssAssetUnavailable */
    public function readBytes(): string;

    /** Idempotent at asset level; delegates one owner close attempt. */
    public function close(): void;
}

interface CssDescriptor
{
    /** Reads/validates the already opened PHP stream from section 7. */
    public function readBytes(): string;

    /** @throws CssDescriptorCloseFailed; exactly one underlying close attempt. */
    public function close(): void;
}

interface CssDescriptorOpener
{
    /** @throws CssAssetUnavailable */
    public function open(string $absolutePath): CssDescriptor;
}

interface PhpStreamCloser
{
    /** @throws CssDescriptorCloseFailed */
    public function close(mixed $phpStream): void;
}

interface PhpStreamClosePrimitive
{
    // Mirrors PHP fclose result and may emit a warning or throw.
    public function close(mixed $phpStream): bool;
}

final class PilotHttpApplication
{
    public function __construct(
        TrustedServerIdentity $identity,
        HttpUserDirectory $users,
        PilotShellRenderer $shell,
        CssAsset $css,
    );

    public function handle(PilotHttpRequest $request): PilotHttpResponse;
}
```

`InvalidServerIdentity`, `CssAssetUnavailable` и outer infrastructure exception являются distinct exception classes без secret-bearing public messages. Application map-ит их в утверждённые `401`/`503`; spies могут бросать эти types, но не подменяют ожидаемый HTTP result.

`CssDescriptorCloseFailed` имеет exact observable contract:

```php
final class CssDescriptorCloseFailed extends \RuntimeException
{
    // No public arguments; always the same safe state.
    public function __construct();
}
```

```text
getMessage()  = "CSS descriptor close failed."
getCode()     = 0
getPrevious() = null
```

Exception не имеет дополнительных public properties/getters с исходной ошибкой, warning или resource metadata; string representation не используется в response/reporter.

Production types реализуют interfaces:

```php
final class RemoteUserIdentity implements TrustedServerIdentity {}
final class MariaDbHttpUserDirectory implements HttpUserDirectory {}
final class ShlzCssAsset implements CssAsset {}
final class PhpCssDescriptor implements CssDescriptor {}
final class PhpCssDescriptorOpener implements CssDescriptorOpener {}
final class NativePhpStreamCloser implements PhpStreamCloser {}
final class NativePhpFclosePrimitive implements PhpStreamClosePrimitive {}
final class ProductionPilotShellRenderer implements PilotShellRenderer {}
```

`PilotHttpApplication::__construct(...)` является public injection seam Gate 2: test создаёт real application с recording/throwing spies каждого interface и вызывает только public `handle()`. Он доказывает приоритет и no-bypass: `405 /pilot` не вызывает ни одну dependency; CSS route вызывает только `CssAsset`; missing identity вызывает только `TrustedServerIdentity`; shell success вызывает identity→CSS availability/read→directory→renderer; dependency вызывается не более одного раза на request. Shell читает CSS bytes только для availability/integrity и не включает их в HTML; asset route возвращает те же bytes. Это application-level public seam дополняет, но не заменяет real HTTP/real MariaDB/real filesystem scenarios.

Host/path/server-global parsing остаётся в отдельном production `PilotHttpRequestFactory`:

```php
final class PilotHttpRequestFactory
{
    /** @throws InvalidHttpRequest mapped only to exact 400 */
    public static function fromServer(array $server): PilotHttpRequest;
}
```

Gate 2 не обязан подделывать globals: grammar проверяется real raw HTTP tests, а spies получают уже independently constructed valid `PilotHttpRequest`. Request object не содержит raw header arrays, cookies, query/form/body или DB/config. `PilotHttpResponse.headers` содержит canonical unique application headers without CR/LF; application уже возвращает empty body и GET-equivalent content length для HEAD. Thin emitter emits response exactly и учитывает только unavoidable local SAPI Host exception; он не меняет status/application headers/content length/body.

Эти boundaries обязательны, не illustrative. Procedural router, объединяющий Host grammar, env parsing, DB SQL, CSS filesystem validation, HTML и response emission в одном file/closure, не соответствует срезу. Factory валидирует presence/syntax config и возвращает application with injected production implementations; filesystem open и DB connect остаются lazy после route/method/identity priority. Handler не создаёт dependencies внутри branches и не exposes directory/descriptor. Thin `public/router.php` только строит config/request, вызывает factory/application и emits response. Exceptions are mapped once at request/application boundary.

### 11.2. V0.7 request-scoped entrypoint и lazy configuration

Этот раздел supersedes только constructor/factory wiring раздела 11.1; DTO, identity/directory/renderer/CSS interfaces и их behavior остаются. Application больше не получает eagerly constructed DB/CSS dependencies:

```php
interface EnvironmentSource
{
    // false means absent; values are never trimmed/defaulted.
    public function read(string $name): string|false;
}

interface PilotHttpDependencies
{
    // Each accessor is lazy and memoized inside one request scope.
    public function css(): CssAsset;
    public function users(): HttpUserDirectory;

    // Idempotent; closes every opened DB/file resource and clears references.
    public function close(): void;
}

interface UnexpectedFailureReporter
{
    // Receives only stable category/correlation id, never Throwable or secrets.
    public function report(string $category, string $correlationId): void;
}

interface CorrelationIdSource
{
    // Returns an opaque non-secret ASCII identifier; may throw on entropy failure.
    public function nextId(): string;
}

final class PilotHttpApplication
{
    public function __construct(
        TrustedServerIdentity $identity,
        PilotShellRenderer $shell,
        PilotHttpDependencies $dependencies,
    );

    public function handle(PilotHttpRequest $request): PilotHttpResponse;
}

final class PilotHttpRequestFactory
{
    /** @throws InvalidHttpRequest */
    public function fromServer(array $server): PilotHttpRequest;
}

final class PilotHttpEntrypoint
{
    public function __construct(
        PilotHttpRequestFactory $requests,
        PilotHttpApplication $application,
        PilotHttpDependencies $dependencies,
        CorrelationIdSource $correlationIds,
        UnexpectedFailureReporter $failures,
    );

    // Complete application response; does not emit headers/body itself.
    public function handle(array $server): PilotHttpResponse;
}

final class ProductionPilotHttpEntrypointFactory
{
    // Must retain EnvironmentSource lazily; create performs no env read/DB/file open.
    public static function create(EnvironmentSource $environment): PilotHttpEntrypoint;
}
```

Production `ProcessEnvironmentSource` wraps `getenv` without snapshotting the whole environment. `ProductionPilotHttpDependencies` reads only the values needed by the requested accessor:

- `css()` reads only `FMONITOR_SHLZ_CSS_PATH`, then constructs/opens `ShlzCssAsset` under section 7;
- `users()` reads only six DB values plus `FMONITOR_LEGACY_TABLE_PREFIX`, validates them, opens MariaDB, confirms `utf8mb4`, and constructs `MariaDbHttpUserDirectory`;
- neither accessor reads `REMOTE_USER`, Host, unrelated environment or the other accessor's config;
- factory/constructors perform no accessor call.

Допустимы только exact canonical environment keys, перечисленные в разделе 11: `FMONITOR_DB_HOST`, `FMONITOR_DB_PORT`, `FMONITOR_DB_NAME`, `FMONITOR_DB_USER`, `FMONITOR_DB_PASSWORD`, `FMONITOR_LEGACY_TABLE_PREFIX`, `FMONITOR_SHLZ_CSS_PATH`. Любые aliases (`DB_HOST`, `MYSQL_*`, `FMONITOR_DB_PASS`, legacy app constants и т. п.) игнорируются и не являются fallback. Absent canonical key никогда не получает default, даже если alias присутствует; result следует exact config/error priority. Empty допустим только там, где это прямо разрешено canonical contract. `REMOTE_USER` и `FMONITOR_TRUSTED_REQUEST_HOST` являются request server variables соответствующих boundaries, не читаются через `EnvironmentSource` как config.

Consequently `400`, `404`, `405` and `/pilot` `308` read no environment value and open no DB/file; asset route reads only CSS path; missing/malformed identity reads no environment/DB/CSS; shell after valid identity calls `css()` before `users()` according to section 9.

`PilotHttpRequestFactory.fromServer()` has an exact parsing order: select the approved production/local Host source and validate section 5.1 first; only after valid Host may it read/normalize request method and URI. Invalid Host therefore returns `400` even when URI encoding/path/method are also invalid. It never reads environment or identity-like request headers.

`PilotHttpEntrypoint.handle()` is the single secured outer execution boundary. Его protected `try/catch/finally` начинается **до** вызова `CorrelationIdSource.nextId()`:

1. запрашивает non-secret correlation ID (`1..128`, ASCII `[A-Za-z0-9._-]+`); invalid/throwing source не выходит наружу: используется fixed internal fallback label `correlation-unavailable`, reporter получает category `pilot_http_correlation_failure`, caller получает exact generic `503`;
2. invokes request factory then application;
3. maps `InvalidHttpRequest` to exact `400`;
4. leaves only explicitly documented expected application outcomes (`401/403/404/405/503`) intact;
5. catches every other `Throwable`, reports only category `pilot_http_unexpected_failure` plus correlation ID, and returns exact redacted `503`;
6. before returning any response calls `dependencies.close()` exactly once; a close failure is itself mapped/reported as the same redacted `503` and never replaces it with exception text.

Каждый вызов reporter сам заключён в non-throwing guard: failure reporter не может изменить response, пропустить cleanup или раскрыть свою exception; fallback logging в response отсутствует. Entropy failure не запускает request factory/application, но всё равно проходит `dependencies.close()` exactly once.

`ProductionPilotHttpDependencies.close()` attempts **every** resource close even if an earlier close throws. Для каждого held resource алгоритм: copy local reference → clear stored reference in `finally` → attempt close → retain first close failure → continue with all remaining DB/file resources. После попыток он throws только retained first failure; повторный `close()` является no-op и не повторяет close/use already-cleared references. Это действует на success и every failure. `MariaDbHttpUserDirectory` does not own a connection beyond this request scope. Gate 2 observes `close()` once for early/no-resource, success, expected failure, throwing spy and close-throw paths; multi-resource spy proves later resources attempted/cleared after first throw and second close no-op; real MariaDB test proves request connection is closed after response and cannot be reused.

Production CSS ownership конкретизирован:

```php
final class ProductionPilotHttpDependencies implements PilotHttpDependencies
{
    public function __construct(
        EnvironmentSource $environment,
        CssDescriptorOpener $cssDescriptors,
    );
}

final class ShlzCssAsset implements CssAsset
{
    public function __construct(
        string $absolutePath,
        CssDescriptorOpener $descriptors,
    );
}
```

`ProductionPilotHttpEntrypointFactory` создаёт `NativePhpFclosePrimitive`, injects его в `NativePhpStreamCloser`, closer — в `PhpCssDescriptorOpener`, а opener передаёт тот же closer каждому созданному `PhpCssDescriptor`. Gate 2 может inject fake opener/descriptor, object `PhpStreamCloser` либо `PhpStreamClosePrimitive` в real production policy/owner types без подмены factory/class/function. `ShlzCssAsset` lazy-open-ит не более одного descriptor, memoizes bytes/handle и отдаёт ownership своему `close()`; destructor не является вторым close mechanism.

`PhpCssDescriptor` получает `PhpStreamCloser` constructor dependency. Его `close()` устанавливает internal `closeAttempted = true` и очищает stored PHP resource reference **до** единственного вызова `$closer->close($resource)`. Ни при каком failure closer/raw close/destructor retry не вызываются повторно; последующий `close()` — no-op. Resource считается relinquished owner-ом после первой попытки.

`NativePhpStreamCloser` — production policy object:

```php
final class NativePhpStreamCloser implements PhpStreamCloser
{
    public function __construct(PhpStreamClosePrimitive $primitive);
    public function close(mixed $phpStream): void;
}
```

Его `close()` устанавливает scoped fully-qualified `\set_error_handler`, вызывает `$primitive->close($phpStream)` ровно один раз и преобразует warning, return `false` и любой `Throwable` в новый `CssDescriptorCloseFailed` exact contract выше. Warning severity/message/file/line и primitive exception class/message/code/data не копируются в message/code/properties и не передаются как `previous`/cause. Fully-qualified `\restore_error_handler` всегда выполняется в `finally`; policy object не хранит resource и не повторяет primitive call.

`NativePhpFclosePrimitive.close()` — единственное production-место фактического системного закрытия stream. Оно содержит ровно `return \fclose($phpStream);`, не ставит handler, не catch-ит, не хранит resource и не retry-ит. Production factory всегда использует этот primitive; иной primitive доступен только через явный constructor injection real policy closer.

Все production filesystem/runtime primitives в этом срезе вызываются только fully-qualified global names: `\lstat`, `\fopen`, `\fstat`, `\fread`/`\stream_get_contents`, `\feof`, `\is_resource`, `\fclose`, `\set_error_handler`, `\restore_error_handler` и применимые аналоги. Unqualified calls внутри namespace и объявление/использование namespaced shadow functions запрещены. Gate 2 fault injection выполняется только через object interfaces (`CssDescriptorOpener`, `CssDescriptor`, `PhpStreamCloser`, `PhpStreamClosePrimitive`), никогда через function shadow, `runkit`, preload или изменение global function table.

`ShlzCssAsset.close()` вызывает descriptor `close()` не более одного раза, очищает descriptor reference в `finally` и сохраняет idempotency при exception. `ProductionPilotHttpDependencies.close()` после CSS failure продолжает закрывать MariaDB и остальные resources по attempt-all contract, затем сообщает retained first failure outer boundary.

Effective v0.8 API **не содержит** `PilotHttpApplicationFactory` и не использует прежний eager `PilotHttpConfig` из superseded section 11.1. Единственный production composition seam — `ProductionPilotHttpEntrypointFactory.create(EnvironmentSource)`. Реализация/тест не могут выбирать между старой и новой factory, а наличие callable obsolete factory считается несоответствием spec.

### 11.3. Safe production bootstrap; no class-shadow injection

`public/router.php` uses one fixed repository file:

```php
$entrypoint = require dirname(__DIR__) . '/app/PilotHttp/production-entrypoint.php';
$response = $entrypoint->handle($_SERVER);
```

`production-entrypoint.php` unconditionally `require`s the fixed production type files (not `require_once`, conditional `class_exists`, mutable include path or user autoloader), constructs `ProcessEnvironmentSource`, and returns only `PilotHttpEntrypoint`. Predeclared/shadow classes cause redeclaration/fail-closed startup; they cannot substitute a factory/application. No environment variable, request value, `auto_prepend_file` convention or test flag selects a class/file/factory.

Gate 2 injection never loads `public/router.php` and never shadows production names. It directly constructs the **real** public `PilotHttpEntrypoint` and real `PilotHttpApplication` with test implementations of the declared interfaces, calls `handle(serverArray)`, and asserts returned DTO plus spy calls. Separate raw HTTP tests exercise the fixed production bootstrap. Thus no source inspection, global mutation or preload trick is required to prove no bypass.

## 12. Runnable local pilot without bypass

Тот же production contract запускается локально только на loopback:

```bash
REMOTE_USER=sidorov@shlz.ru \
FMONITOR_DB_HOST=127.0.0.1 \
FMONITOR_DB_PORT=23306 \
FMONITOR_DB_NAME=fmonitor2_demo \
FMONITOR_DB_USER=... \
FMONITOR_DB_PASSWORD=... \
FMONITOR_LEGACY_TABLE_PREFIX= \
FMONITOR_SHLZ_CSS_PATH=/home/antropophag/code/shlz-ui/packages/styles/dist/shlz.css \
php -S 127.0.0.1:8092 public/router.php
```

PHP built-in server inherits OS environment as server variables; application executes the same `REMOTE_USER` resolver with no development flag, special user, request header or permissive fallback. Binding any non-loopback address with operator-supplied identity is outside supported local contract. Local smoke test proves:

- `curl http://127.0.0.1:8092/pilot/` resolves user `18`;
- adding `Remote-User`, `X-Remote-User`, `X-Forwarded-User`, `Authorization` and `Cookie` for a different user does not change displayed identity;
- a second server process without OS `REMOTE_USER` returns exact `401` despite spoof headers.

Production MUST set `REMOTE_USER` per authenticated request at trusted server boundary; a process-wide identity is permitted only for single-user loopback pilot/demo.

## 13. Redaction and audit

Responses never contain principal on failure, DB/env values, SQL/table/prefix/path, driver messages, filesystem metadata, exception class/message or stack. Logs written by this slice, if an outer deployment captures them, use only request correlation ID generated by server, outcome category and resolved numeric user ID after success; raw headers/cookies/password/principal and SQL are forbidden. Application itself need not create a log file.

Successful/failed GET does not write product/security audit because it is read-only and authentication occurs upstream. A future security-audit retention/export contract is separate.

## 14. Gate 2 public seam and sensitivity

Gate 2 starts real PHP built-in server processes on random loopback ports with isolated real MariaDB fixtures and requests them over HTTP. It parses raw status/headers/body and DOM; private resolver/renderer methods are not called as assertions.

Required sensitivity includes:

- success, HEAD parity, exact redirect, CSS exact bytes;
- missing/malformed server identity and every spoof header alone/in combination;
- exact byte/case email match, duplicate email, inactive user/role, dangling role;
- DB unavailable/query failure, unreadable/symlink CSS;
- method matrix, request body not read, path encoding/dot/suffix/query adversaries;
- `POST|PUT|PATCH|DELETE /pilot` каждый даёт `405` до redirect, не читает open body и не обращается к DB/CSS/configured path;
- Host integrity: missing, duplicate/comma-combined, whitespace/control, userinfo/scheme/path, malformed DNS/IPv4/IPv6 and port; each exact `400` before identity/DB/CSS. Raw-socket assertion допускает только unavoidable local SAPI echo из раздела 8 внутри response Host, no reflection anywhere else; missing Host получает единственный fixed `Host: rejected.invalid`; valid local Host остаётся sole exact SAPI echo;
- production boundary fixture: trusted server rejects two raw Host lines before PHP invocation and passes exactly one `FMONITOR_TRUSTED_REQUEST_HOST` for valid request; client headers cannot create/override it;
- deterministic CSS path swaps regular→symlink and regular→different-regular between pre-open inspection and descriptor identity revalidation; both exact `503`, no bytes from either file, while unchanged file bytes come from the same opened descriptor;
- required composition types/factory are instantiated through public bootstrap; test doubles at their public constructor seams prove routing does not bypass identity, directory, renderer or validated CSS asset;
- real `PilotHttpEntrypoint` injection test (без router globals/class shadow): Host parser precedes malformed URI; recording `EnvironmentSource` proves 400/404/405/308 read zero keys, asset reads only CSS path, missing identity reads zero keys, shell reads CSS then exact DB keys; throwing dependencies map once to generic 503 and reporter sees no Throwable/secret;
- canonical environment sensitivity: each absent `FMONITOR_*` remains failure despite populated plausible aliases; no alias/default key is ever read; obsolete `PilotHttpApplicationFactory` is absent from effective public API;
- correlation source throw/invalid and reporter throw all return same redacted 503, skip request/application, still close once, and never expose entropy/reporter details;
- request-scope cleanup: `PilotHttpDependencies.close()` exactly once on every branch, including unexpected/close failure; real mysqli connection is unusable after returned response and no CSS descriptor remains open;
- multi-resource cleanup spy: first close throws, every later DB/file close is attempted, all stored refs cleared, retained failure maps once, second close is no-op;
- real `ProductionPilotHttpDependencies` + injected `CssDescriptorOpener/CssDescriptor` fault handles: descriptor close return-false/warning/Throwable each causes one typed failure, exactly one handle-level close call, DB/later cleanup still attempted, second dependency/asset close no-op; no FFI/raw fd/proc helper is loaded or called;
- real `PhpCssDescriptor` + injected object `PhpStreamCloser` proves exactly one closer call on success/throw and no retry; real `NativePhpStreamCloser` with injected recording primitives returning true/false, emitting warning and throwing proves one-call policy mapping plus handler restoration. Separate `NativePhpFclosePrimitive` normal path proves fully-qualified `\fclose` closure. Test declares adversarial same-namespace function names and proves they are never called; no function-shadow mechanism supplies fault injection;
- close redaction: adversarial warning and primitive Throwable use distinct secret message/class/code/data literals; resulting exception is exact `CssDescriptorCloseFailed`, message `CSS descriptor close failed.`, code `0`, previous `null`, no extra public data, and none of the literals reaches reporter/HTTP output;
- separate real `PhpCssDescriptor` normal path reads the validated public CSS bytes and performs exactly one successful PHP `fclose`; process resource count returns to baseline without manual/raw close;
- production bootstrap adversary with predeclared same-name factory/application cannot serve a request or substitute behavior; fixed unconditional require fails closed, while normal bootstrap returns exact real `PilotHttpEntrypoint`;
- all security headers, no cookie/CORS/banner, HTML escaping and forbidden markup;
- DB/legacy rows and all `fm2_*` tables unchanged before/after;
- process-wide loopback identity and no-identity second server as section 12.

Expected DOM/text/status/headers come from sections 5–9, not production output or prototype HTML.

## 15. Не входит в срез и следующий порядок

- login/logout/password/LDAP/SSO implementation;
- reuse/migration of CodeIgniter session;
- object list/card/read model or process command;
- application session and CSRF token;
- process capability authorization (performed by future commands);
- custom application CSS/JavaScript beyond public `shlz-ui` export;
- TLS/reverse-proxy configuration files and upstream authentication product choice;
- metrics, persistent request/security log.

Следующий slice: `PILOT-OBJECT-CARD-001` — authenticated `GET /pilot/objects/{positive-id}` для явно импортированного case, read-only composition of process projection + approved legacy identity mapping, с `404` без enumeration detail и без process mutation.

## 16. Решения и доказательства

- `../fmonitor/application/controllers/Auth.php`, `application/config/config.php`: legacy login/session boundary read-only evidence и причины не принимать hash/cookie как новую identity.
- `PROCESS-USER-DIRECTORY-001`: active legacy user + active role contract; process capabilities distinct from identity.
- `PRODUCT.md`, pilot/screen-flow specs: web platform, specialized shell, FKR navigation and no wide editable table.
- `../shlz-ui/README.md`, `docs/architecture.md`, `packages/styles/package.json`: standalone public `@shlz/styles/shlz.css` consumer boundary.
- `PRODUCTION-COMPOSITION-001`: HTTP/controller/session intentionally separate from domain factory.

## 17. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь поручил автономно довести проект до запускаемого пилота. Версия 0.12 выбирает минимальный безопасный authenticated shell; object card остаётся отдельным read-model slice. Для close failure закреплён exact безопасный exception без previous/cause и без переноса warning/primitive данных.

Gate 2 разрешён для версии `0.12`; прежние tests требуют обновления и нового независимого Gate 3 review.
