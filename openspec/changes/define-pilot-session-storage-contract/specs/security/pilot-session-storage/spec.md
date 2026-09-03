## Purpose

Определяет переносимый owned filesystem session lifecycle для local authentication и access administration с explicit commit и fail-closed HTTP mapping.

## ADDED Requirements

### Requirement: Configuration и namespace точны
`FMONITOR_SESSION_STATE_ROOT` SHALL обозначать managed root. Absent key SHALL
использовать compatibility value `/home/fmonitor/.local/state/fmonitor2`;
present empty/relative/NUL/control/traversal value SHALL вернуть configuration
failure. `FMONITOR_SESSION_INSTANCE` SHALL соответствовать
`[a-z0-9][a-z0-9_-]{0,31}` и default `pilot`. Final save directory SHALL быть
`<root>/sessions/<instance>` без port/email/request-derived components.

#### Scenario: Task-owned host instance
- **WHEN** verifier передаёт absolute owned root и unique valid instance
- **THEN** LocalAuth и UserAccessView используют один exact final directory и не обращаются к compatibility path

#### Scenario: Explicit empty root
- **WHEN** key присутствует с empty value
- **THEN** HTTP возвращает storage unavailable до filesystem/session access и не применяет default

### Requirement: Managed ownership boundary исполнима
Deployment SHALL гарантировать trusted resolution ancestors до managed root.
Adapter SHALL lstat/revalidate managed root: real directory, current effective
uid, mode ровно `0700`, `0750` или `0755`, без group/world write. Existing
`sessions` и instance directories SHALL быть current-uid real directories mode
ровно `0700`. Missing descendants MAY создаваться 0700; EEXIST принимается
только после полной revalidation. Symlink/non-directory/wrong-owner/other mode
MUST fail closed. Production adapter MUST NOT chmod/chown/repair or remove
managed directories; exact handler destroy/age-bounded locked GC file removal
below is the only production deletion.

#### Scenario: Symlink instance
- **WHEN** instance path является symlink
- **THEN** storage unavailable, target unchanged, session primitive not called

#### Scenario: Concurrent first mkdir
- **WHEN** два process одновременно создают absent sessions/instance directories
- **THEN** оба могут продолжить только после наблюдения одной current-uid 0700 identity; иначе один получает unavailable, partial unsafe path не принимается

Runtime гарантия опирается на current-uid 0700 final directory, исключающий
untrusted path swap; same-uid malicious code и descriptor-relative `openat`
гарантия вне scope. Identity revalidated immediately before/after each file
operation; detected swap fails closed.

### Requirement: Explicit session transaction precedes response commit
Один adapter SHALL владеть operations `start`, `regenerate`, `writeCommit`,
`destroyCommit`, `close`. Native/custom handler warnings/false/throw maps to typed
failure. HTTP output and headers SHALL buffer until successful explicit
write/destroy commit; implicit shutdown write MUST быть отключён/завершён.
Regeneration SHALL generate candidate ID internally, acquire old+candidate hash
locks in binary order and resolve all candidate collisions before mutation.
It stages/fsyncs data, no-clobber hard-links old to hash-associated revoked
tombstone, fsyncs/unlinks old, then no-clobber hard-links stage to prevalidated
new ID and fsyncs. Unexpected collision after old unlink fails safe without
retry. Set-Cookie commits only after new publication. Failure before old unlink
preserves old valid; after it and before new publication leaves no valid session
and forces re-login; after publication only new valid. Tombstone never readable
and MAY remain for bounded same-hash-lock GC; no rollback resurrects old. On any pre-commit failure adapter SHALL remove
pending Set-Cookie and discard success body/redirect before emitting 503.

#### Scenario: Start/read failure
- **WHEN** handler cannot open/read configured store
- **THEN** exact 503 emitted without Set-Cookie, HTML, redirect or partial bytes

#### Scenario: Regenerate/write failure
- **WHEN** credentials valid but new-ID write/old-ID invalidation fails
- **THEN** no authenticated redirect/cookie committed; old session либо остаётся
  valid до first rename, либо становится revoked tombstone и user logged out;
  два valid IDs невозможны, staged/tombstone cleanup bounded, response exact 503

#### Scenario: Crash между regeneration publications
- **WHEN** process падает после old unlink и до staged→new no-clobber publication
- **THEN** old/new IDs оба invalid, tombstone/staged files не читаются login seam,
  следующий request требует login, GC может удалить только exact owned artifacts

#### Scenario: Logout destroy failure
- **WHEN** destroy commit fails
- **THEN** no success redirect/deletion cookie is committed and response exact 503

### Requirement: Exact unavailable HTTP mapping
Для GET и POST storage failure SHALL return status 503, body `Service unavailable.\n`,
`Content-Type: text/plain; charset=UTF-8`, `Content-Length: 21`,
`Retry-After: 60`, `Cache-Control: no-store`, `X-Content-Type-Options: nosniff`,
`Referrer-Policy: no-referrer`, `X-Frame-Options: DENY`, literal CSP
`default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'`,
`Permissions-Policy: camera=(), microphone=(), geolocation=()`,
`Cross-Origin-Opener-Policy: same-origin`; no Set-Cookie/Location. HEAD has same
headers/length and empty body. Internal log MAY contain category+opaque ID only,
never path, uid, mode, session ID/data, CSRF, email/password or exception.
Response MUST NOT contain `Access-Control-Allow-Origin`, `WWW-Authenticate`,
`Server`, `X-Powered-By` or unspecified application headers. Built-in local
SAPI MAY echo exactly one validated raw `Host` header as transport behavior;
production application MUST NOT generate/reflect Host.

#### Scenario: HEAD write-precondition failure
- **WHEN** HEAD к session-consuming path встречает unavailable storage
- **THEN** exact 503 headers/length returned with empty body and no cookie

#### Scenario: POST regeneration failure
- **WHEN** POST `/pilot/login` или `/pilot/logout` встречает typed regenerate/write/destroy failure
- **THEN** exact same 503 headers/body returned, no redirect/cookie/HTML and no unspecified headers

### Requirement: Existing cookie/session protocol сохраняется
Cookie name SHALL быть `fm2auth` без port и `fm2auth_<decimal-port>` для valid
outer-boundary Host port; malformed/duplicate Host rejected before storage.
Cookie lifetime 604800 seconds, Path `/pilot`, HttpOnly, SameSite Strict, Secure
for trusted HTTPS only. Session ID grammar remains `[A-Za-z0-9,-]{16,128}`;
strict mode, CSRF, safe return-to, successful regeneration and old-ID
invalidation preserved. GC max lifetime remains 604800.

#### Scenario: Compose restart within lifetime
- **WHEN** committed cookie/session моложе 604800 seconds и same volume/root/
  instance используется stop/start без reset
- **THEN** authenticated session and CSRF/return-to state remain usable, bytes are not reseeded/rotated solely by restart

### Requirement: Exact route priority и оба consumer используют owner
Static assets `/pilot/assets/*` (including CSS/JS/SVG/fonts) SHALL be served or
rejected before LocalAuth/session configuration. Malformed Host/URI belongs to
outer boundary before storage. Any unknown `/pilot/*` route SHALL return the
inherited `404 Not found` before session configuration, storage or authentication
and SHALL NOT redirect to login. `303 /pilot/login` is reserved for known
login-required routes. Both LocalAuth and UserAccessView MUST use the one
adapter/config; hardcoded/alternate save path forbidden.

#### Scenario: Asset with invalid storage config
- **WHEN** exact known/unknown `/pilot/assets/...` requested while storage config invalid
- **THEN** inherited asset result returned with zero storage env/filesystem/primitive access

#### Scenario: Unknown non-asset route
- **WHEN** anonymous or authenticated request targets an unrecognized `/pilot/*` path
- **THEN** response is inherited `404 Not found` with zero session env/filesystem/primitive access and no login redirect

### Requirement: Cleanup ownership разделён
Production adapter never removes session directories. It removes files only by
explicit normal destroy or exact age-bounded locked GC described by this
capability; no broad cleanup/repair. Tests SHALL own exact temp root, created
processes and cookies; finally stops/reaps processes, closes handlers and removes
only verified task root. Compose reset/retention remain separate operator seams.

#### Scenario: Test failure cleanup
- **WHEN** assertion fails after task-owned session creation
- **THEN** attempt-all cleanup removes only exact task root, production/default/foreign roots unchanged

### Requirement: Production lifecycle имеет публичную наблюдаемую test seam
Identity/Access SHALL expose one public factory which constructs the same
production session owner used by both HTTP consumers. The factory SHALL accept
filesystem primitives, monotonic/wall clock and entropy as explicit
dependencies and SHALL return immutable public operation-result DTOs; default
production composition SHALL use native filesystem, system clocks, CSPRNG and a
no-op observer. Tests MUST only replace those dependencies around the real
owner and MUST NOT dispatch, calculate or attest lifecycle results themselves.

The filesystem port SHALL expose exact calls `lstat`, `fstat`, `mkdir`, `open`,
`read`, `write`, `fflush`, `fsyncFile`, `fsyncDirectory`, `link`, `rename`,
`unlink`, `flock`, `close`, `list` and `mtime`. Each call SHALL emit immutable
`before` and `after` events containing sequence number, operation, opaque
logical artifact (`root|sessions|instance|lock|committed|stage|revoked`),
session-id SHA-256 where applicable, call ordinal and typed outcome; event data
MUST NOT contain path, session ID/data, cookie, CSRF or credential. Injectable
faults SHALL be keyed only by `(operation, logical artifact, call ordinal)` and
return the same typed false/warning/exception/short-IO outcomes the owner maps.

After every `after` event, the observer MAY deterministically `continue`,
`pause` until verifier release, or terminate the verifier-owned child without
running cleanup. These hooks SHALL be available only through explicit factory
injection, MUST NOT be selectable by environment/request/Compose production
configuration and MUST NOT change ordering or results when no-op. Clock SHALL
provide deterministic wall seconds plus monotonic nanoseconds and has no failure
channel; entropy SHALL provide requested byte count or typed failure. Public result SHALL contain only operation status,
safe unavailable category, opaque correlation ID and, on success, the owner-
generated current session ID; it SHALL contain no event claims or filesystem
snapshot supplied by the test.

The exact public PHP API SHALL be the v8 sections 8 and 10 surface in
`specs/PILOT-SESSION-STORAGE-001.md`: namespace `FMonitor\IdentityAccess`,
required two-string config constructor, five-required-dependency factory
`create`, exact owner `start/writeCommit/regenerate/destroyCommit/close`
operations, exact filesystem method arguments and primitive-result accessors,
exact `public static function` primitive/entropy result named factories,
clock/entropy methods, opaque handle `public static function mint(): self`,
exact scalar stat construction, observer/event accessors and
immutable owner-result accessors. Primitive exception results carry only a
closed safe adapter code, never a Throwable/message/path; filesystem adapters
own minted handle identities and wrappers may pass delegate-owned successes.
Filesystem operation, logical artifact, phase, primitive outcome/failure,
file type, entropy status, operation status and unavailable-category enums SHALL
use the exact case names/backing strings in v8. No listed parameter is optional/defaulted and factory
or owner MUST NOT read environment/request globals. Tests SHALL wrap these
interfaces and MUST NOT substitute a different construction or operation API.

`PilotSessionOperationResult` SHALL expose only the exact eight `@internal
public static` owner factories in v8; `PilotSessionFilesystemEvent` SHALL expose
only exact `ownerBefore/ownerAfter`; `PilotSessionInspectionResult` SHALL expose
only exact `inspectorOk/inspectorUnavailable`. PHP public visibility exists only
so the separate real owner/inspector classes can construct final DTOs. An
architecture call-site ratchet SHALL reject calls outside the concrete real
owner for owner-result/event factories and outside the exact inspector for
inspection-result factories. Test/support code MUST inspect accessors and
independent material/HTTP evidence and MUST NOT call these factories.

Raw HTTP injected-fault verification SHALL use only the exact public
`ProductionPilotHttpEntrypointFactory::createWithSessionStorageDependencies`
signature from v8. It builds the same complete graph as production `create`,
replacing only filesystem/clock/entropy/observer ports. No environment, request,
cookie, CLI or Compose selector SHALL choose or populate this composition.

#### Scenario: Fault is proved by production execution
- **WHEN** verifier injects failure for `fsyncFile(stage, ordinal=1)` and invokes a real owner operation through the public factory
- **THEN** observed before/after trace comes from the production owner, result category is `FSYNC_FAILED`, material state matches the specified crash region, and changing production call order makes the verifier fail

#### Scenario: Deterministic crash boundary
- **WHEN** verifier pauses after the observed `unlink(committed)` success event and kills only its owned child before release
- **THEN** read-only material inspection proves old/new invalid and non-addressable stage/revoked artifacts without a test dispatcher completing or declaring the lifecycle

### Requirement: Compose volume inspection is read-only and canonical
A public inspection seam SHALL accept only an already resolved managed
root/instance from operator-owned invocation and SHALL perform no mkdir, lock,
GC, repair, chmod, chown, write, rename or unlink. It SHALL return canonical
JSON sorted by binary ascending emitted `entryKeySha256`, exact 64 lowercase hex
SHA-256 of the complete raw basename bytes. Literal basename SHALL be used only
inside inspector validation/hashing and SHALL NOT be returned. Output SHALL
contain root/descendant identity and, for each exact grammar entry:
`entryKeySha256`, logical type, uid, mode, device, inode, link count, size,
mtime and content SHA-256 digest. It MUST NOT return literal basename, session
bytes, session ID, path outside the managed root, cookie, CSRF or credentials.
Duplicate emitted entry keys, invalid identity, unknown entry or any attempted
mutation SHALL fail closed with non-zero status and no partial success snapshot.
Compose restart verification SHALL combine snapshots from this seam with raw
HTTP cookie behavior; inspector output alone MUST NOT attest authentication.
The exact class/result signature, command
`php bin/pilot-session-storage-inspect.php --state-root <absolute-root> --instance <valid-instance>`,
four-argument argv grammar, canonical JSON key/envelope grammar and exit/output
codes `0|64|65|70` SHALL be those in executable v8 section 10. There is no
predecessor inspector/default/output behavior to infer or inherit.

The exact v8 CLI application SHALL accept inspection, filesystem, argv and
stdout/stderr output ports. Production bin SHALL unconditionally bind the real
inspector, native read-only filesystem, process argv adapter and direct output;
no argument/environment/request/Compose value selects dependencies. Invalid
argv calls no inspector/filesystem and maps `64`; real inspector `UNAVAILABLE`
maps `65`; a Throwable escaping injected `inspect` maps deterministically to
`70` without class/message/trace; successful result maps `0`. Tests MAY use
owned argv/output, real inspector plus filesystem wrapper, or a throwing
inspection implementation, but MUST NOT construct an inspection result.

#### Scenario: Stop/start preserves inspected material
- **WHEN** verifier snapshots the configured Compose volume, performs ordinary stop/start with the same root/instance and snapshots again before expiry
- **THEN** canonical entry identities/digests are unchanged and the original cookie succeeds through the real HTTP consumer

#### Scenario: Inspector cannot prepare its own evidence
- **WHEN** inspector sees an unknown/wrong-owner entry or cannot read metadata/digest without mutation
- **THEN** it exits non-zero, changes no volume metadata or bytes and emits no fabricated success snapshot
