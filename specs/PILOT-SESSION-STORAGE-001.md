# PILOT-SESSION-STORAGE-001 — owned filesystem session lifecycle

Статус: **Gate 1 re-review pending**
Версия: **v10 (session-payload handoff amendment)**
Дата: **2026-09-03**

## Простыми словами

Login и управление доступом используют одно безопасное session-хранилище,
которое явно задаётся конфигурацией и работает одинаково в Compose, image и
host tests. Session сначала надёжно записывается, и только потом браузеру
отправляются cookie/redirect. Ошибка даёт обычный 503 без утечки пути или ID.
Прочитанное состояние session передаётся HTTP-слою самим владельцем хранилища,
поэтому login и управление доступом не открывают session-файл повторно.

## 1. Configuration и path contract

```text
FMONITOR_SESSION_STATE_ROOT
FMONITOR_SESSION_INSTANCE
```

Absent root key → compatibility root
`/home/fmonitor/.local/state/fmonitor2`. Present empty/relative/NUL/control/
`.`/`..` component → `CONFIGURATION_INVALID`. Instance default `pilot`, valid
regex `[a-z0-9][a-z0-9_-]{0,31}`. Exact managed final path:

```text
<state-root>/sessions/<instance>
```

No Host, port, email, user ID, cookie or request value enters filesystem path.
Deployment owns trusted resolution ancestors before state root. State root MUST
be real non-symlink directory, current euid owner and mode exactly 0700/0750/
0755. Existing `sessions` and instance MUST be current-euid real directory mode
0700. Missing descendants are mkdir 0700; EEXIST accepted only after complete
lstat identity/type/uid/mode revalidation. Adapter never chmod/chown/removes root.

## 2. Exact file grammar and ownership

Valid session ID: `[A-Za-z0-9,-]{16,128}`. Files only inside exact instance:

```text
s-<session-id>.session          committed readable session
l-<lowercase-sha256(session-id)>.lock
.stage-<64-lowercase-sha256(session-id)>-<32-lowercase-hex>.session
.revoked-<64-lowercase-sha256(old-session-id)>-<32-lowercase-hex>.session
```

Directory mode 0700; every regular file current euid, non-symlink, mode 0600.
Session data is opaque bytes length `0..1048576`. Existing file with wrong
type/uid/mode, duplicate identity change, short write/read, invalid length or
unexpected filename gives typed unavailable; no repair. Same-uid malicious code
and guarantees above trusted root are explicit residual boundary; final 0700
directory excludes other OS identities. lstat/fstat/parent identity revalidated
immediately before and after every open/read/write/rename/unlink.

Adapter generates anonymous/new session IDs as 64 lowercase hex from exact
`random_bytes(32)`. Stage/tombstone tokens use independent 16 bytes →32 hex.
Every candidate target is checked absent and CSPRNG retries at most 8 times;
exhaustion is `ID_COLLISION`, entropy failure is `ENTROPY_FAILED`. No request or
caller supplies a new session ID.

## 3. Locks and primitive outcomes

Every read/write/regenerate/destroy of ID acquires its hash lock file with
exclusive `flock(LOCK_EX|LOCK_NB)` retrying monotonic-time until 2.000 seconds;
timeout → `LOCK_TIMEOUT`. Lock file follows exact ownership/mode rules.
Multiple IDs acquire locks in binary ascending lock filename order. Operations
return typed `OK`, `NOT_FOUND`, `INVALID`, `UNAVAILABLE(category, correlation)`;
warnings/false/Throwable are captured and never emitted.

`start(null)` creates in-memory anonymous empty state and generated ID, but no
file/cookie until writeCommit. `start(supplied-valid-id)` reads exact file;
absent returns NOT_FOUND and caller treats request unauthenticated without new
cookie/file. Invalid grammar returns INVALID. `regenerate` requires committed
old ID; NOT_FOUND maps `SESSION_STALE` unavailable. `destroyCommit` on absent
valid ID is idempotent OK and permits deletion cookie without file mutation.
`close` releases all locks/handles once. No native implicit shutdown write.

Committed payload uses exactly PHP's `serialize()` representation of the whole
`$_SESSION` associative array, not the `php` session-module `name|value`
framing. The HTTP adapter decodes with `unserialize($payload,
['allowed_classes' => false])` under warning-to-failure capture and accepts only
a top-level array whose recursively reachable keys are integers/strings and
values are null/bool/int/string/array, with maximum nesting depth 16 and at most
4096 total entries. Every array element MUST have
`ReflectionReference::fromArrayElement(...) === null`, and re-encoding the
accepted value MUST be byte-identical to the input. Objects, resources, floats,
references/cycles, trailing bytes, non-array roots, non-canonical or malformed encodings are `PAYLOAD_INVALID`; decode
failure occurs before route/auth execution and maps through the exact 503 in
section 6. Empty bytes from `start(null)` mean an empty array and are never fed
to `unserialize`. Before `writeCommit` or `regenerate`, the adapter applies the
same shape limits to in-memory state and encodes it with `serialize()`. It never
calls `session_start`, `session_decode`, `session_encode` or another native
session lifecycle primitive.

Closed unavailable enum:
`CONFIGURATION_INVALID`, `ROOT_INVALID`, `PAYLOAD_INVALID`, `ENTROPY_FAILED`, `ID_COLLISION`,
`LOCK_TIMEOUT`, `READ_FAILED`, `WRITE_FAILED`, `FSYNC_FAILED`, `PUBLISH_FAILED`,
`SESSION_STALE`, `REGENERATE_FAILED`, `DESTROY_FAILED`, `GC_FAILED`,
`CLOSE_FAILED`. Correlation is fresh 12 lowercase hex per failure, internal log
only and never an HTTP header/body value.

## 4. Atomic normal write

Under ID lock, `writeCommit(id,data)`:

1. create random stage with exclusive `x+b`, mode0600;
2. write all bytes, `fflush`, `fsync`, fstat/revalidate;
3. for existing locked ID, atomic same-directory rename stage → committed
   `s-...session`; for new anonymous ID, no-clobber hard link stage → absent
   committed target then unlink stage; on EEXIST it removes/revalidates only its
   own stage, releases/closes old candidate lock, generates a wholly new ID,
   acquires/revalidates the new hash lock and proves new target absent before
   creating a new stage. At most8 complete candidate attempts; never overwrites
   another session or operates on new candidate before its lock;
4. fsync opened instance directory; revalidate committed identity;
5. release lock.

Failure before rename leaves prior committed file valid and stage quarantined;
failure after rename but directory-fsync/revalidation failure returns unavailable
and MUST NOT claim response commit. Public response is still buffered; operator
diagnosis/reset required, no blind retry/rollback.

## 5. Regeneration and destroy

`regenerate(old,data)` generates candidate new ID internally. Before any old-ID
mutation it acquires old+candidate locks in binary lock-filename order, proves
new committed path absent, and on collision releases both and retries fresh
candidate/locks at most8. Only after collision-free candidate is locked it
stages/fsyncs new data, then:

1. create no-clobber hard link old committed file → fresh absent
   `.revoked-...session`; collision retries max8;
2. fsync directory, unlink old valid path, fsync directory;
3. create no-clobber hard link stage → prevalidated/locked absent new committed
   path; unexpected EEXIST after old unlink is `REGENERATE_FAILED`, leaves user
   logged out and MUST NOT retry/publish another ID;
4. unlink stage, fsync directory/revalidate new;
5. commit new cookie; best-effort unlink tombstone followed by directory fsync.

Before old unlink failure preserves old valid plus possibly non-addressable hard
link. Crash after old unlink and before new hard-link leaves old/new invalid and
forces login; tombstone/stage never session-ID addressable. After new hard-link
only new ID valid; no step overwrites existing committed/tombstone/stage path.
Tombstone cleanup failure is logged
and left for bounded GC, never rollback/resurrect old. Dual-valid IDs forbidden.

`destroyCommit(id)` locks, hard-links committed file to fresh no-clobber revoked
tombstone (max8 collision retries), fsyncs, unlinks old valid path and fsyncs,
then allows deletion cookie/redirect; tombstone unlink is bounded cleanup.
Failure before old unlink preserves old and returns unavailable; after unlink
session invalid even if cleanup fails. No tombstone is overwritten.

## 6. Response buffering and exact failure

LocalAuth and UserAccessView SHALL buffer status/headers/body until successful
explicit `writeCommit`, `regenerate` or `destroyCommit`; only then emit response
and Set-Cookie. On typed failure discard buffered success/redirect, remove all
Set-Cookie/Location and emit:

```text
503
Content-Type: text/plain; charset=UTF-8
Content-Length: 21
Retry-After: 60
X-Content-Type-Options: nosniff
Referrer-Policy: no-referrer
X-Frame-Options: DENY
Content-Security-Policy: default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'
Permissions-Policy: camera=(), microphone=(), geolocation=()
Cross-Origin-Opener-Policy: same-origin
Cache-Control: no-store

Service unavailable.\n
```

GET and POST identical; HEAD has same headers/Content-Length and empty body.
No CORS, WWW-Authenticate, Server, X-Powered-By or unspecified application
headers. Local cli-server MAY echo exactly one outer-validated SAPI Host;
application does not emit/reflect Host. Log exact once:
`PILOT_SESSION_UNAVAILABLE category=<safe-enum> correlation_id=<12hex>`;
correlation is internal-only and response has no correlation header; no path,
uid/mode, session/cookie/CSRF/email/password/data/SQL/class/exception.

## 7. Cookie, protocol and route priority

Predecessor protocol remains:

- cookie `fm2auth` without port, `fm2auth_<decimal-port>` after valid outer Host;
- lifetime/GC max 604800, Path `/pilot`, HttpOnly, SameSite Strict;
- Secure only when outer trusted server boundary injects exact server value
  `FMONITOR_TRUSTED_REQUEST_SCHEME=https`; exact `http` omits Secure, absent or
  other value is configuration unavailable before session. Raw
  `HTTP_X_FORWARDED_PROTO`/client headers are ignored;
- strict ID grammar/mode, CSRF, safe return-to;
- successful login regeneration invalidates old ID per section5;
- logout uses destroyCommit; ordinary writes explicitly commit before output.

Malformed/duplicate Host/URI rejected by outer boundary before session config.
All exact `/pilot/assets/*` CSS/JS/SVG/font routes resolve before session config/
filesystem/primitive, including unknown asset 404. Every unknown `/pilot/*`
route returns inherited 404 before session config/filesystem/authentication for
anonymous and authenticated requests. It never redirects to login. Only known
login-required routes may return `303 Location: /pilot/login`.

Both `RapidPilotLocalAuth` and `RapidPilotUserAccessView` MUST use this single
owner. Direct `session_save_path`, `session_start`, `session_regenerate_id`,
`session_write_close`, `session_destroy` and alternate hardcoded roots outside
adapter are architecture violations.

## 8. Public construction, primitive trace and deterministic faults

IdentityAccess SHALL expose one public `PilotSessionStorageFactory` composition
seam used by both HTTP consumers. It constructs the real production owner from:

```text
FilesystemPrimitives
Clock { wallSeconds(): int; monotonicNanoseconds(): int }
Entropy { bytes(int length): bytes|typed-failure }
LifecycleObserver { observe(event): void; injected implementation may block }
```

Exact public PHP surface (all types are in namespace `FMonitor\IdentityAccess`):

```php
final readonly class PilotSessionStorageConfig
{
    public function __construct(
        public string $stateRoot,
        public string $instance,
    ) {}
}

final class PilotSessionStorageFactory
{
    public function __construct()
    {
        /* implementation */
    }

    public function create(
        PilotSessionStorageConfig $config,
        PilotSessionFilesystemPrimitives $filesystem,
        PilotSessionClock $clock,
        PilotSessionEntropy $entropy,
        PilotSessionLifecycleObserver $observer,
    ): PilotSessionStorage {
        /* implementation */
    }
}

interface PilotSessionStorage
{
    public function start(?string $suppliedSessionId): PilotSessionOperationResult;
    public function writeCommit(string $sessionId, string $data): PilotSessionOperationResult;
    public function regenerate(string $oldSessionId, string $data): PilotSessionOperationResult;
    public function destroyCommit(string $sessionId): PilotSessionOperationResult;
    public function close(): PilotSessionOperationResult;
}

interface PilotSessionClock
{
    public function wallSeconds(): int;
    public function monotonicNanoseconds(): int;
}

interface PilotSessionEntropy
{
    public function bytes(int $length): PilotSessionEntropyResult;
}

enum PilotSessionFilesystemOperation: string
{
    case LSTAT = 'lstat';
    case FSTAT = 'fstat';
    case MKDIR = 'mkdir';
    case OPEN = 'open';
    case READ = 'read';
    case WRITE = 'write';
    case FFLUSH = 'fflush';
    case FSYNC_FILE = 'fsyncFile';
    case FSYNC_DIRECTORY = 'fsyncDirectory';
    case LINK = 'link';
    case RENAME = 'rename';
    case UNLINK = 'unlink';
    case FLOCK = 'flock';
    case CLOSE = 'close';
    case LIST = 'list';
    case MTIME = 'mtime';
}

enum PilotSessionLogicalArtifact: string
{
    case ROOT = 'root';
    case SESSIONS = 'sessions';
    case INSTANCE = 'instance';
    case LOCK = 'lock';
    case COMMITTED = 'committed';
    case STAGE = 'stage';
    case REVOKED = 'revoked';
}

enum PilotSessionFilesystemPhase: string
{
    case BEFORE = 'before';
    case AFTER = 'after';
}

enum PilotSessionPrimitiveOutcome: string
{
    case OK = 'ok';
    case NATIVE_FALSE = 'false';
    case WARNING = 'warning';
    case EXCEPTION = 'exception';
    case SHORT_IO = 'short_io';
}

enum PilotSessionPrimitiveFailureCode: string
{
    case NOT_FOUND = 'not_found';
    case ALREADY_EXISTS = 'already_exists';
    case PERMISSION_DENIED = 'permission_denied';
    case INTERRUPTED = 'interrupted';
    case INVALID_ARGUMENT = 'invalid_argument';
    case IO_ERROR = 'io_error';
    case UNSUPPORTED = 'unsupported';
    case UNKNOWN = 'unknown';
}

enum PilotSessionFileType: string
{
    case REGULAR = 'regular';
    case DIRECTORY = 'directory';
    case SYMLINK = 'symlink';
    case OTHER = 'other';
}

enum PilotSessionEntropyStatus: string
{
    case OK = 'ok';
    case FAILED = 'failed';
}

enum PilotSessionOperationStatus: string
{
    case OK = 'ok';
    case NOT_FOUND = 'not_found';
    case INVALID = 'invalid';
    case UNAVAILABLE = 'unavailable';
}

enum PilotSessionUnavailableCategory: string
{
    case CONFIGURATION_INVALID = 'configuration_invalid';
    case ROOT_INVALID = 'root_invalid';
    case PAYLOAD_INVALID = 'payload_invalid';
    case ENTROPY_FAILED = 'entropy_failed';
    case ID_COLLISION = 'id_collision';
    case LOCK_TIMEOUT = 'lock_timeout';
    case READ_FAILED = 'read_failed';
    case WRITE_FAILED = 'write_failed';
    case FSYNC_FAILED = 'fsync_failed';
    case PUBLISH_FAILED = 'publish_failed';
    case SESSION_STALE = 'session_stale';
    case REGENERATE_FAILED = 'regenerate_failed';
    case DESTROY_FAILED = 'destroy_failed';
    case GC_FAILED = 'gc_failed';
    case CLOSE_FAILED = 'close_failed';
}
```

`PilotSessionStorageConfig` receives already resolved values: the HTTP/env
composition owns absent-vs-present-empty parsing from section 1 before calling
the factory. The factory performs the normative validation and MUST NOT read
environment/request globals. No optional/default constructor or factory
parameter is allowed. `PilotSessionEntropyResult` is immutable and has exact
public named factories:

```php
final readonly class PilotSessionEntropyResult
{
    public static function ok(string $bytes): self
    {
        /* validate the OK invariant and return the immutable value */
    }

    public static function failed(): self
    {
        /* return the immutable FAILED value */
    }
}
```

There is no other public constructor or named factory. The DTO also exposes
accessors `status(): PilotSessionEntropyStatus` (`OK|FAILED`) and
`bytes(): ?string`. The owner rejects an `OK`
value whose length differs from the length requested from `bytes`. `FAILED`
requires `null`.

The exact filesystem port is:

```php
interface PilotSessionFilesystemPrimitives
{
    public function lstat(string $path): PilotSessionPrimitiveResult;
    public function fstat(PilotSessionFileHandle $handle): PilotSessionPrimitiveResult;
    public function mkdir(string $path, int $mode): PilotSessionPrimitiveResult;
    public function open(string $path, string $mode, int $permissions): PilotSessionPrimitiveResult;
    public function read(PilotSessionFileHandle $handle, int $length): PilotSessionPrimitiveResult;
    public function write(PilotSessionFileHandle $handle, string $bytes): PilotSessionPrimitiveResult;
    public function fflush(PilotSessionFileHandle $handle): PilotSessionPrimitiveResult;
    public function fsyncFile(PilotSessionFileHandle $handle): PilotSessionPrimitiveResult;
    public function fsyncDirectory(string $path): PilotSessionPrimitiveResult;
    public function link(string $existingPath, string $newPath): PilotSessionPrimitiveResult;
    public function rename(string $fromPath, string $toPath): PilotSessionPrimitiveResult;
    public function unlink(string $path): PilotSessionPrimitiveResult;
    public function flock(PilotSessionFileHandle $handle, int $operation): PilotSessionPrimitiveResult;
    public function close(PilotSessionFileHandle $handle): PilotSessionPrimitiveResult;
    public function list(string $directory): PilotSessionPrimitiveResult;
    public function mtime(string $path): PilotSessionPrimitiveResult;
}
```

`PilotSessionFileHandle` is an immutable opaque identity with no public
constructor and the one public named factory:

```php
final readonly class PilotSessionFileHandle
{
    public static function mint(): self
    {
        /* return one new opaque identity */
    }
}
```

A filesystem-port
implementation owns each minted identity, MAY retain an internal identity-to-
native-handle map, and SHALL return it only as the `OK` value of `open`; the
token exposes no accessor, native resource or path. Wrappers pass through a
delegate-owned successful handle or may mint their own only when they fully own
the wrapped filesystem implementation.

`PilotSessionPrimitiveResult` is immutable, has no public constructor and has
the exact public named factories:

```php
final readonly class PilotSessionPrimitiveResult
{
    public static function ok(mixed $value): self
    {
        /* return the immutable OK value */
    }

    public static function nativeFalse(): self
    {
        /* return the immutable FALSE value */
    }

    public static function warning(PilotSessionPrimitiveFailureCode $failureCode): self
    {
        /* validate the safe code and return the immutable WARNING value */
    }

    public static function exception(PilotSessionPrimitiveFailureCode $failureCode): self
    {
        /* validate the safe code and return the immutable EXCEPTION value */
    }

    public static function shortIo(string|int $partialValue): self
    {
        /* validate and return the immutable SHORT_IO value */
    }
}
```

There is no other public constructor or named factory. The DTO exposes exact accessors
`outcome(): PilotSessionPrimitiveOutcome`
(`OK|NATIVE_FALSE|WARNING|EXCEPTION|SHORT_IO`), `value(): mixed`, and
`failureCode(): ?PilotSessionPrimitiveFailureCode`. The owner validates each `ok`
or `shortIo` value against the operation whose result it consumes;
`nativeFalse` carries
null value/code; `warning` and `exception` require a closed adapter enum value
and carry null value; no factory accepts a `Throwable`, exception message,
path or other native diagnostic. `shortIo` carries only partial read bytes or a
partial write byte count and a null code. `value()` is operation-bounded:
`lstat/fstat` return
an immutable `PilotSessionFileStat`; `open` a `PilotSessionFileHandle`; `read`
bytes; `write` an integer byte count; `list` a list of raw basename strings;
`mtime` an integer epoch second; other successful calls `null`. Non-`OK`
results carry only native-shaped safe failure data required for owner mapping;
`failureCode` is non-null exactly for `WARNING|EXCEPTION` and is one of
`NOT_FOUND|ALREADY_EXISTS|PERMISSION_DENIED|INTERRUPTED|INVALID_ARGUMENT|IO_ERROR|UNSUPPORTED|UNKNOWN`,
never warning text/path. This generic
result does not contain or calculate an owner/session outcome.
`PilotSessionFileStat` is immutable and has the exact public constructor
`__construct(PilotSessionFileType $type, int $uid, int $mode, int $device,
int $inode, int $linkCount, int $size, int $mtime)`. It validates non-negative
numeric metadata and exposes exact accessors `type():
PilotSessionFileType` (`REGULAR|DIRECTORY|SYMLINK|OTHER`), `uid(): int`,
`mode(): int`, `device(): int`, `inode(): int`, `linkCount(): int`, `size(): int`
and `mtime(): int`.

```php
interface PilotSessionLifecycleObserver
{
    public function observe(PilotSessionFilesystemEvent $event): void;
}
```

`PilotSessionFilesystemEvent` is immutable, has no public constructor and has
these exact owner-only creation methods:

```php
final readonly class PilotSessionFilesystemEvent
{
    /** @internal Real PilotSessionStorage owner call sites only. */
    public static function ownerBefore(
        int $sequence,
        PilotSessionFilesystemOperation $operation,
        PilotSessionLogicalArtifact $artifact,
        ?string $sessionIdSha256,
        int $ordinal,
    ): self {
        /* validate and return immutable BEFORE event */
    }

    /** @internal Real PilotSessionStorage owner call sites only. */
    public static function ownerAfter(
        int $sequence,
        PilotSessionFilesystemOperation $operation,
        PilotSessionLogicalArtifact $artifact,
        ?string $sessionIdSha256,
        int $ordinal,
        PilotSessionPrimitiveOutcome $outcome,
    ): self {
        /* validate and return immutable AFTER event */
    }
}
```

Both factories require positive `sequence` and `ordinal`. Hash is null exactly
for `ROOT|SESSIONS|INSTANCE` and exact 64 lowercase hex for
`LOCK|COMMITTED|STAGE|REVOKED`. `ownerBefore` fixes phase `BEFORE` and null
outcome; `ownerAfter` fixes phase `AFTER` and the supplied non-null outcome.
There is no path, literal session ID or bytes parameter. The DTO exposes exact accessors
`sequence(): int`, `operation(): PilotSessionFilesystemOperation`,
`phase(): PilotSessionFilesystemPhase` (`BEFORE|AFTER`),
`artifact(): PilotSessionLogicalArtifact`, `sessionIdSha256(): ?string`,
`ordinal(): int`, and `outcome(): ?PilotSessionPrimitiveOutcome` (`null` on
`BEFORE`, non-null on `AFTER`). Observer events are emitted by the real owner
immediately around each filesystem-port call. The production no-op returns
immediately; an injected observer MAY block inside `observe()` only for an
`AFTER` event until its verifier-owned IPC release arrives. The filesystem
wrapper may inject a result, while the observer may only observe/block and
cannot supply a primitive or owner result.

Production binds native filesystem, system clocks, CSPRNG and no-op observer.
No environment, request, cookie or Compose setting can select test adapters or
pause/fault behavior. Verifier composition MAY inject wrappers, but MUST invoke
the same owner and MUST NOT implement/dispatch a scenario or synthesize its
result.

Raw-HTTP verification uses this exact additional public composition seam in
namespace `FMonitor2\PilotHttp` (the four session port types are imported from
`FMonitor\IdentityAccess`):

```php
final class ProductionPilotHttpEntrypointFactory
{
    public static function create(EnvironmentSource $environment): PilotHttpEntrypoint
    {
        /* bind native filesystem, system clock, CSPRNG and no-op observer */
    }

    public static function createWithSessionStorageDependencies(
        EnvironmentSource $environment,
        PilotSessionFilesystemPrimitives $filesystem,
        PilotSessionClock $clock,
        PilotSessionEntropy $entropy,
        PilotSessionLifecycleObserver $observer,
    ): PilotHttpEntrypoint {
        /* build the same complete production graph with only these ports replaced */
    }
}
```

Both methods perform the same environment/config parsing and construct the same
LocalAuth, UserAccessView, router and session owner graph; the second method
differs only in the four explicitly supplied ports. It has no scenario/result
argument and no test dispatcher. Production router/bootstrap SHALL call only
`create`. A verifier-owned raw-HTTP launcher MAY explicitly call
`createWithSessionStorageDependencies` before serving its owned child. No
environment key, request/header/cookie value, CLI flag, Compose value, service
locator or runtime branch may select that method or provide its dependencies.

`FilesystemPrimitives` exact operations are `lstat`, `fstat`, `mkdir`, `open`,
`read`, `write`, `fflush`, `fsyncFile`, `fsyncDirectory`, `link`, `rename`,
`unlink`, `flock`, `close`, `list`, `mtime`. Every invocation emits immutable
before and after `PilotSessionFilesystemEvent` with monotonically increasing
sequence, operation, phase, logical artifact
`root|sessions|instance|lock|committed|stage|revoked`, lowercase session-ID
SHA-256 when applicable, per `(operation,artifact)` ordinal and typed outcome.
It never exposes literal path, ID, session bytes, cookie, CSRF or credentials.

Fault injection matches only exact `(operation, logical-artifact, ordinal)` and
returns the native-shaped false/safe-warning-code/safe-exception-code/short-read/short-write outcome
for that primitive. After an observed after-event the parent-controlled observer
may pause the verifier-owned child until explicit release; the parent may kill
that child to prove a crash boundary without child cleanup. No hook skips,
completes or declares an owner operation. With no-op observer/wrapper, order and
result are identical to production.

All public operations return immutable `PilotSessionOperationResult`. It has no
public constructor and these exact owner-only creation methods:

```php
final readonly class PilotSessionOperationResult
{
    /** @internal Real PilotSessionStorage owner call sites only. */
    public static function ownerStarted(
        string $currentSessionId,
        string $sessionPayload,
    ): self
    {
        /* validate ID and return OK */
    }

    /** @internal Real PilotSessionStorage owner call sites only. */
    public static function ownerWriteCommitted(string $currentSessionId): self
    {
        /* validate ID and return OK */
    }

    /** @internal Real PilotSessionStorage owner call sites only. */
    public static function ownerRegenerated(string $currentSessionId): self
    {
        /* validate ID and return OK */
    }

    /** @internal Real PilotSessionStorage owner call sites only. */
    public static function ownerDestroyed(): self
    {
        /* return OK with null current ID */
    }

    /** @internal Real PilotSessionStorage owner call sites only. */
    public static function ownerClosed(): self
    {
        /* return OK with null current ID */
    }

    /** @internal Real PilotSessionStorage owner call sites only. */
    public static function ownerNotFound(): self
    {
        /* return NOT_FOUND with all nullable fields null */
    }

    /** @internal Real PilotSessionStorage owner call sites only. */
    public static function ownerInvalid(): self
    {
        /* return INVALID with all nullable fields null */
    }

    /** @internal Real PilotSessionStorage owner call sites only. */
    public static function ownerUnavailable(
        PilotSessionUnavailableCategory $category,
        string $correlationId,
    ): self {
        /* validate correlation ID and return UNAVAILABLE */
    }
}
```

Only the concrete real owner MAY call these `@internal` factories; architecture
ratchets SHALL reject every other production/support/test call site. This public
visibility is solely the PHP mechanism permitting the separate owner class to
construct the final DTO; it is not verifier authority to synthesize evidence.
The DTO exposes exact accessors `status(): PilotSessionOperationStatus`
(`OK|NOT_FOUND|INVALID|UNAVAILABLE`),
`category(): ?PilotSessionUnavailableCategory`, `correlationId(): ?string` and
`currentSessionId(): ?string`, and `sessionPayload(): ?string`.
Category/correlation are non-null only for
`UNAVAILABLE`; current ID is non-null only for successful `start`,
`writeCommit` or `regenerate` and is either owner-generated or the validated
supplied ID accepted by `start`. Session payload is non-null only for successful
`start`: it is the exact committed bytes read by the owner, or empty bytes for
a newly started session. The HTTP adapter MUST restore `$_SESSION` from this
in-memory handoff and MUST NOT reopen/read committed storage or invoke a second
session owner. Payload MUST NOT enter logs, filesystem events, inspection
output, correlation data or HTTP failures. `ownerUnavailable` requires exact 12
lowercase hex correlation ID. The DTO contains no mutable field,
filesystem/event claim or test-supplied snapshot. HTTP consumes this DTO and
still obeys section 6.

Wall clock owns expiry/mtime comparisons; monotonic clock owns the exact lock
deadline; entropy owns ID, stage/tombstone token and correlation bytes. Injected
clock values and entropy failures are mapped by the real owner, not by test
fixtures. Clock has no failure channel: a verifier supplies deterministic
integer values, including forward/backward wall values and monotonic deadline
progression; only entropy exposes typed `FAILED`.

## 9. GC, concurrency and restart

At most once per process start, GC enumerates at most 10000 entries; overflow →
`GC_FAILED` without deletion. From exact committed/stage/revoked/lock grammar it
selects at most 100 eligible files ordered oldest mtime then binary name. Stage/
tombstone grammar embeds exact session-ID hash, so GC opens the same
`l-<hash>.lock`; malformed/unassociated artifacts are foreign/unchanged. It
nonblocking-locks corresponding valid ID and deletes only
current-euid mode0600 files older than 604800 seconds, revalidating before unlink
and fsyncing directory. Lock file is eligible only when committed/stage/tombstone
targets absent, lock acquired and mtime older than 604800. Unknown/newer/locked/
wrong identity/mode/type unchanged. Oldest-first prevents binary-prefix
starvation across starts. Production never removes directories.

Concurrent mkdir accepts one creator plus fully revalidated EEXIST. Concurrent
writes serialize by lock; timeout maps 503. Two servers with distinct instance
names have disjoint files. Compose ordinary stop/start uses same volume/root/
instance and fixed observation within lifetime; valid cookie session/CSRF/
return-to bytes remain usable, no reseed/rotation solely by restart.

## 10. Read-only Compose inspection

There is no predecessor inspector, script, Make target, output or exit behavior
to inherit. The exact public command is:

```text
php bin/pilot-session-storage-inspect.php --state-root <absolute-root> --instance <valid-instance>
```

It accepts exactly these four arguments in this order, with each value in the
following argv element; no `--key=value`, omitted/duplicate/extra option,
environment fallback or default is accepted. Root and instance use sections 1
and 2 validation, but the command never creates missing paths. The command
constructs native read-only filesystem primitives and calls this exact public
namespace `FMonitor\IdentityAccess` seam:

```php
enum PilotSessionInspectionStatus: string
{
    case OK = 'ok';
    case UNAVAILABLE = 'unavailable';
}

interface PilotSessionStorageInspection
{
    public function inspect(
        PilotSessionStorageConfig $config,
        PilotSessionFilesystemPrimitives $filesystem,
    ): PilotSessionInspectionResult;
}

final class PilotSessionStorageInspector implements PilotSessionStorageInspection
{
    public function __construct()
    {
        /* implementation */
    }

    public function inspect(
        PilotSessionStorageConfig $config,
        PilotSessionFilesystemPrimitives $filesystem,
    ): PilotSessionInspectionResult {
        /* perform only the normative read-only inspection */
    }
}

interface PilotSessionInspectorCliArguments
{
    /** @return list<string> Arguments after the executable name. */
    public function values(): array;
}

interface PilotSessionInspectorCliOutput
{
    public function writeStdout(string $bytes): void;
    public function writeStderr(string $bytes): void;
}

final class PilotSessionInspectorCliApplication
{
    public function __construct(
        PilotSessionStorageInspection $inspector,
        PilotSessionFilesystemPrimitives $filesystem,
        PilotSessionInspectorCliArguments $arguments,
        PilotSessionInspectorCliOutput $output,
    ) {
        /* retain the four exact dependencies */
    }

    public function run(): int
    {
        /* validate argv, invoke inspector once, write exact output, return exit code */
    }
}

final readonly class PilotSessionInspectionResult
{
    /** @internal PilotSessionStorageInspector call sites only. */
    public static function inspectorOk(string $canonicalJson): self
    {
        /* validate exact canonical envelope and return immutable OK */
    }

    /** @internal PilotSessionStorageInspector call sites only. */
    public static function inspectorUnavailable(): self
    {
        /* return immutable UNAVAILABLE with null JSON */
    }

    public function status(): PilotSessionInspectionStatus
    {
        /* implementation */
    }

    public function canonicalJson(): ?string
    {
        /* implementation */
    }
}
```

`PilotSessionInspectionResult` has no public constructor or other named factory.
Only `PilotSessionStorageInspector` MAY call its two `@internal` factories;
architecture ratchets reject every other production/support/test call site.
`inspectorOk` accepts only a completely validated exact canonical envelope and
returns `OK` with that non-null JSON; `inspectorUnavailable` returns
`UNAVAILABLE` with null JSON. Public accessors remain independently assertable.
The DTO contains no HTTP, authentication or session-owner outcome, so neither
factory can attest cookie reuse or authentication.

The production `bin/pilot-session-storage-inspect.php` executable always binds
one real `PilotSessionStorageInspector`, native read-only filesystem primitives,
an argv adapter returning exactly `array_slice($argv, 1)`, and direct
stdout/stderr output, then exits with `PilotSessionInspectorCliApplication::run()`.
There is no alternate production binding. Tests MAY instantiate the same CLI
application with owned argument/output ports, the real inspector plus an owned
filesystem wrapper to obtain deterministic `inspectorUnavailable`, or an
inspection implementation whose `inspect` throws solely to exercise the
internal-failure mapping. The injected inspection interface has no method to
construct or return a fabricated result: inspection-result factories remain
restricted to the real inspector, and test implementations may only throw.
No env/request/Compose/argv value selects a dependency implementation; argv is
data consumed after composition, not a composition selector.

The inspector lstat/revalidates without `mkdir`, `open` for write, lock, GC,
chmod/chown, rename or unlink and emits one UTF-8 JSON object plus LF, with
object keys in the literal order below, no insignificant whitespace and entries
sorted by binary ascending emitted `entryKeySha256`:

```json
{"schemaVersion":1,"root":{"type":"directory","uid":1000,"mode":448,"device":1,"inode":2,"linkCount":2,"size":4096,"mtime":0},"sessions":{"type":"directory","uid":1000,"mode":448,"device":1,"inode":3,"linkCount":2,"size":4096,"mtime":0},"instance":{"type":"directory","uid":1000,"mode":448,"device":1,"inode":4,"linkCount":2,"size":4096,"mtime":0},"entries":[{"entryKeySha256":"<64-lowercase-hex>","logicalType":"committed","uid":1000,"mode":384,"device":1,"inode":5,"linkCount":1,"size":10,"mtime":0,"contentSha256":"<64-lowercase-hex>"}]}
```

Numeric examples are placeholders for observed integers, not required literal
metadata. Directory objects always contain exactly
`type,uid,mode,device,inode,linkCount,size,mtime`; `type` is `directory`.
Each entry always contains exactly
`entryKeySha256,logicalType,uid,mode,device,inode,linkCount,size,mtime,contentSha256`;
`logicalType` is `lock|committed|stage|revoked`. Empty `entries` is `[]`.
No other top-level/member key is allowed.

For each exact grammar entry,
`entryKeySha256` is exact 64 lowercase hex `SHA-256` of the complete raw basename
bytes; the literal basename is used only inside inspector validation/hashing and
is never returned. Snapshot contains root/descendant identity and, for each
entry, `entryKeySha256`, logical type, uid, mode, device, inode, link count,
size, mtime and content SHA-256 digest. Duplicate emitted entry keys, unknown/
wrong entries, identity change or incomplete read exit non-zero without partial
success output. Inspector never emits a literal basename, file bytes, decoded
session data, session ID, cookie/CSRF/credentials or paths outside root and
cannot repair evidence.

Exit/output protocol is exact:

- success: exit `0`, canonical JSON plus one LF on stdout, empty stderr;
- argv/config syntax failure: exit `64`, empty stdout, exact stderr
  `Usage: pilot-session-storage-inspect --state-root <absolute-root> --instance <valid-instance>\n`;
- any identity, metadata, grammar, duplicate-key, open/read/digest/revalidation
  or filesystem failure: exit `65`, empty stdout, exact stderr
  `Inspection unavailable.\n`;
- an otherwise uncaught internal failure: exit `70`, empty stdout, the same
  exact redacted `Inspection unavailable.\n`.

The CLI application maps a real inspector `UNAVAILABLE` result to `65`. It maps
any `Throwable` escaping `inspect` to `70`; the throwable class/message/trace is
not written. For either path it writes stdout zero times and stderr exactly once.
Argument syntax is decided before calling `inspect`; invalid argv maps `64` and
calls neither inspector nor filesystem. On success inspector is called exactly
once, stdout exactly once with `canonicalJson() . "\n"`, stderr zero times.

No partial JSON is written before complete successful inspection.

Compose restart proof requires both equal pre/post canonical snapshots within
lifetime and successful raw HTTP reuse of the original cookie. Inspector JSON
alone never attests authentication or owner result.

## 11. Tests, cleanup and Done

Gate 2 invokes the real owner factory and independently records exact primitive
events/material state while fault-injecting config, mkdir/EEXIST/swap, each
open/read/lock/write/fflush/fsync/link/rename/directory-fsync/unlink/close phase,
regeneration crash boundaries, destroy, GC, deterministic clock values, entropy
failure, both consumers through the explicit HTTP composition seam and exact
GET/HEAD/POST responses. A test-owned dispatcher or child JSON claim is
not evidence. Parent snapshots include uid/device/inode/mode/link count/size/
digest/bytes as appropriate; pause/kill proves crash regions. Tests own exact
root/process/cookies and attempt-all finally stop/reap, close and delete only
verified task root; foreign/default/Compose roots unchanged.

Done requires fresh owner approval of this v10 exact reviewed hash, intended RED, independent test
approval, minimal implementation, LocalAuth+UserAccessView and asset priority
GREEN on unprivileged host/current image, real Compose stop/start cookie proof,
architecture/lint/full/fresh verification and independent code approval.
Pre-amendment Gate 3 reviews do not apply. The v2 exact-hash approval remains
historical and is insufficient only for this newly exact public PHP API; this
DRAFT does not authorize replacement Gate 2 until a fresh independent Gate 1
review and fresh owner approval of the v10 hashes.
