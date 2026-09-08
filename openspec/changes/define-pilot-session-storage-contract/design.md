## Context

См. proposal.md. Two consumers use native PHP sessions and a hardcoded path;
native shutdown write prevents deterministic pre-response failure mapping.

## Goals / Non-Goals

**Goals:** exact config/namespace; implementable ownership modes; one adapter;
explicit commit before response; protocol/restart preservation; host/image tests.

**Non-Goals:** openat/same-uid attacker guarantee, distributed store, retention,
password/RBAC, production volume data migration.

## Decisions

1. Config keys are exact; compatibility default preserves current Compose.
   Present empty never falls back. Instance namespace is explicit and bounded.
2. Trust starts at managed root, not `/` ancestors. Root accepts only
   current-uid 0700/0750/0755; session descendants exact0700. Trusted deployment
   ancestors and same-uid malicious swaps are stated residual boundary.
3. `PilotSessionStorage` implements/owns `SessionHandlerInterface`-compatible
   primitives with warning capture and explicit boolean/typed outcomes.
   `writeCommit/destroyCommit` happen while response buffered; shutdown has no
   pending session write. This makes 503/no-cookie implementable.
4. Regeneration generates/locks old+candidate before mutation, then uses
   no-clobber hard links: old→hash-associated tombstone, unlink old,
   stage→prevalidated new. Unexpected post-invalidation collision fails without
   retry. Crash between publications logs user out, never leaves dual-valid IDs.
   Tombstone unlink is same-lock bounded GC, not rollback/security success.
5. Both LocalAuth and UserAccessView depend on adapter; architecture rejects
   `session_save_path/session_start` elsewhere and hardcoded session paths.
6. Router retains static-asset/outer Host/URI priority. Unknown `/pilot/*`
   routes resolve to inherited 404 before session/config/auth; only known
   login-required routes may redirect to `/pilot/login`.
7. Production never cleans roots. Tests use task-owned roots/processes and
   attempt-all finally cleanup; Compose restart verifier uses persistent volume
   and fixed clock below GC lifetime.
8. IdentityAccess exposes a single public factory for the real owner. Its ports
   are filesystem primitives, wall/monotonic clock, entropy and an immutable
   lifecycle observer. Production composition supplies native/no-op adapters;
   verifier composition supplies recording/fault wrappers but still invokes the
   same owner. A wrapper may select a fault by exact operation/artifact/ordinal,
   pause after an emitted operation event, or let the parent kill its owned
   child. It never calculates the owner result. Alternative test-owned scenario
   dispatch was rejected because it can self-attest arbitrary JSON.
   The executable contract fixes the exact PHP constructor/factory method,
   owner operations, every filesystem method argument/return, opaque handle,
   clock/entropy methods, observer/event/result accessors and closed enums.
   Primitive/entropy results have exact `public static function` named factories. Filesystem-port
   implementations own opaque handle identities minted without resource/path
   accessors and construct immutable stats from exact scalar metadata; wrappers
   may pass delegate-owned successes through. Native exceptions are reduced to
   closed safe adapter codes before entering a primitive result.
   Config parsing remains at HTTP/env composition; factory validation never
   reads globals. This is sufficient for independently authored wrappers without
   making test code choose a production API.
9. Events use a closed vocabulary and safe logical artifact/hash identifiers,
   never paths or secrets. Before/after sequence and typed primitive outcome
   make call order, short I/O and crash regions observable. Clock and entropy
   ports make timeout, age ordering, collision and correlation deterministic.
   Immutable public result DTOs expose only the safe owner outcome needed by
   HTTP mapping and verifier assertions.
   All event/status/type/category enums have exact case names and string backing
   values. Warning/exception adapters reduce native diagnostics to the closed
   primitive failure-code enum. Clock supplies deterministic integers only and
   has no failure result; entropy alone has a typed failure.
   Operation results and filesystem events expose exact `@internal public
   static` owner factories because PHP has no package-private/friend access;
   inspector results analogously expose inspector-only factories. Exact
   call-site architecture ratchets permit only the concrete real owner or exact
   inspector respectively. Tests assert accessors and independent effects but
   never call those factories, preserving the anti-self-attestation boundary.
10. A separate read-only Compose inspector resolves an operator-supplied
    managed root/instance, validates identity and emits canonical metadata/
    digests sorted by a stable non-secret key: lowercase SHA-256 of each complete
    raw basename. Literal basenames never leave the inspector; duplicate emitted
    keys fail closed rather than produce an ambiguous snapshot. It cannot
    create, lock, GC or repair. Restart proof joins its snapshots with real
    raw-HTTP behavior; neither inspector nor event recorder may claim
    authentication success.
    Its exact PHP class/result seam, CLI argv grammar, canonical JSON envelope,
    redacted output and exit `0|64|65|70` protocol have no predecessor fallback.
    An exact CLI application accepts inspection, filesystem, argv and output
    ports. The production executable unconditionally binds real/native ports;
    tests may inject owned argv/output and a throwing inspection implementation
    for exit 70, but cannot construct an inspection result. Real inspector plus
    filesystem wrapper drives deterministic unavailable/exit 65.
11. `ProductionPilotHttpEntrypointFactory::create` is the only production
    bootstrap path. Its explicit `createWithSessionStorageDependencies` sibling
    builds the same graph for verifier-owned raw HTTP with only the four ports
    replaced; no environment/request/Compose value can select it.
12. A successful `start` result is the sole owner-to-HTTP handoff for committed
    session bytes. `ownerStarted` accepts the current ID and exact opaque
    payload (empty for a new session), and `sessionPayload()` is non-null only
    for that result. The HTTP adapter decodes this in memory; it cannot reopen
    the committed path or invoke native session loading. The adapter uses one
    bounded whole-array PHP `serialize` codec with classes disabled on decode;
    malformed or unsafe shapes fail before application dispatch as exact
    `PAYLOAD_INVALID`/503. Payload is secret material and is excluded from
    events, logs, inspection and error responses.

Owning module — IdentityAccess session infrastructure. HTTP buffers/maps typed
results; domain modules/rapid-pilot do not own filesystem policy.

## Risks / Trade-offs

- [Native behavior differs by PHP] → narrow primitive adapter and image+host tests.
- [Existing volume mode/owner mismatch] → fail closed + runbook, no silent repair.
- [Commit buffering changes direct responders] → scoped output buffer with exact predecessor bytes tests.
- [Tombstone accumulation] → exact filename grammar, age-bounded locked GC and task-owned crash tests.
- [Concurrent GC/write] → handler lock/atomic rename policy fixed in executable Gate 1.
- [Injectable port drifts from native behavior] → production uses a thin native
  adapter and the same owner is exercised in host/image/Compose tests.
- [Observer leaks secrets or becomes production control plane] → closed safe
  event DTO, no-op production binding and no env/request selector.

## Migration Plan

Amended Gate 1 exact handler/file-lock/stage-tombstone-rename plus public
factory/ports/events/results/session-payload handoff/inspector PHP signatures → fresh independent
review and fresh exact-hash owner approval →
replacement RED per primitive/crash phase through the real owner → independent
test review → adapter + both consumers + Compose config → host/image/restart/
full verification → independent code review. Pre-amendment Gate 3 reviews do
not apply. Rollback before release may use old path; never copy committed
secrets into repository.
