## Why

`RapidPilotLocalAuth` и `RapidPilotUserAccessView` независимо hardcode-ят
`/home/fmonitor/.local/state/fmonitor2/sessions`. Compose случайно подходит, но
host clean-checkout не может получить login cookie; ownership, modes,
multi-instance isolation и session write failures не имеют общего контракта.

## What Changes

- Ввести единый Identity/Access session-storage owner для обоих consumers.
- `FMONITOR_SESSION_STATE_ROOT` обозначает managed state root; absent использует
  current Compose compatibility root, explicit empty/invalid fail closed.
- `FMONITOR_SESSION_INSTANCE` задаёт deterministic instance namespace; final
  save directory `<root>/sessions/<instance>`.
- Проверять exact owner/type/modes managed directories, создавать только absent
  owned descendants 0700, revalidate identity around every session operation и
  никогда не repair/remove production state.
- Заменить implicit native shutdown write explicit buffered lifecycle
  open/read/start/regenerate/write/destroy/close до HTTP response commit; typed
  failure даёт exact 503 без Set-Cookie/partial response.
- Regeneration использует fail-safe staged file + non-addressable revoked
  tombstone, исключая два одновременно valid session ID даже при crash.
- Публичная factory-композиция принимает production-owned filesystem
  primitives, monotonic/wall clock, entropy и lifecycle observer/hook; exact
  events и fault outcomes позволяют verifier-у детерминированно остановить или
  оборвать production lifecycle, не исполняя session protocol вместо него.
- Exact public PHP signatures фиксируют config/factory inputs,
  owner operations, filesystem arguments/results, clock/entropy,
  observer/events и immutable result accessors, чтобы independent RED wrappers
  не изобретали implementation contract.
- Injectable primitive/entropy results имеют точные `public static function` named factories;
  opaque handle minting и scalar stat construction позволяют независимому
  adapter/wrapper вернуть успешный port result без раскрытия native resource.
- Closed backed enums фиксируют filesystem operations, logical artifacts и
  safe primitive failure codes; clock имеет deterministic integer values, но
  не failure channel.
- Explicit `createWithSessionStorageDependencies` даёт raw-HTTP verifier-у
  тот же production graph без env/request selector; exact inspector class/CLI,
  canonical JSON envelope и exit codes фиксируют Compose evidence seam.
- Owner result/events и inspector result получают exact constructible
  `@internal public static` factories: PHP visibility делает separate owner/
  inspector classes исполнимыми, а architecture call-site ratchet запрещает
  test/support фабриковать owner/event/inspection evidence.
- Inspector CLI имеет exact application runner с injectable inspector,
  filesystem, argv и output ports: production bin всегда binds native
  implementations, а verifier детерминированно доказывает exit `65`
  и `70` без env/request/argv dependency selector.
- Публичный immutable result DTO является единственным machine-readable
  результатом операций, а read-only Compose inspector показывает canonical
  metadata/digests volume под стабильными SHA-256 ключами полных basename, не
  раскрывая literal basename/session ID и не выполняя mutation.
- Сохранить approved cookie/CSRF/session-ID/GC semantics и уточнить route priority: любой неизвестный `/pilot/*` возвращает `404` до session/config/auth; `303 /pilot/login` применяется только к известным login-required маршрутам.
- **Actor:** anonymous/authenticated browser and deployment operator. **Source
  oracle:** both current session owners, Compose volume, CSP login verifier.
  **Target seam:** local-auth/user-access HTTP exchange + configured storage
  adapter. **Release value:** secure portable host/image login. **Non-goals:**
  password/RBAC, SSO, distributed sessions, retention redesign, production
  volume migration automation.

## Capabilities

### New Capabilities

- `security/pilot-session-storage`: Exact configured filesystem session lifecycle, ownership, isolation, commit/failure mapping and predecessor protocol preservation.

### Modified Capabilities

Нет.

## Impact

Identity/Access session adapter и public infrastructure seams, LocalAuth,
UserAccessView, Compose/env/runbook, login/user-access tests and architecture
ratchet. Domain facts unchanged.
