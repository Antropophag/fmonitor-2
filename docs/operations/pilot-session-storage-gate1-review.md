# PILOT-SESSION-STORAGE-001 — independent Gate 1 review

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or edit the executable specification, OpenSpec
artifacts, production or tests. This record is the only edit. Gate 2 is not
authorized.

## Reviewed hashes

```text
de284565f4f7e1132a92da3a0ed6e3b3821f786ddeda4fcea614d776520db5da  specs/PILOT-SESSION-STORAGE-001.md
4d926e4cdf39675bb1ae404142b1c1b5db5af8e8f35e6364b6e9ae671b432a04  openspec proposal.md
a3b7abf872ac5d2f8e78956629e3feee46176b7c247b44056d34110aeeba6356  openspec design.md
c0de7be4c07b49a0291a3dee05e55b288c98858ec640cfcb1edcff6ef8dfb8f7  openspec delta spec.md
13071d8611f1fee0c32410d7573891628a8ada3286ea731b6cab24893318bb8e  openspec tasks.md
1127914219cd37856d2f9e3dcdc395e7ab67d6b55129d5b088eaa709d3189ac1  pilot-session-storage-planning-review.md
9c5b15bd8f6a9f2168ab10b2c11918b27dcea9991bbb5329b8e435ef4ca53888  pilot-session-storage-planning-rereview.md
81b9aca0193ed62d7e16d95cb7b00c8f6a1fddd974e2d7a46921c10805dab2f6  pilot-session-storage-planning-rereview-v2.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
c792b7bd3c707b0b9bd4fe2e934c677d44235ce2da41839688383391d47f3ec5  specs/PILOT-E2E-FLOW-001.md
d5c776a36a27377972c7b5f897ebcd95bead8154612cfdac8b1fbb98a869f406  rapid-pilot/LocalAuth.php
33b298c2f28a7c9ee493270ded4b7d54c9192cd0a1d529ea4a7daeb5a7697f1a  rapid-pilot/UserAccessView.php
b075db40047c604e5f71f992379e2caeafcf7f945acb80062d9b62b645008727  compose.yaml
ee3b6fd4e82441b6066f1adfc3020ebde4f8ba576f95db81796f4ec50d5ad16d  compose.test.yaml
```

## Required findings

### R1 — new session ID generation and no-clobber publication are undefined

The contract validates an incoming ID but never defines who generates the first
anonymous/new/regenerated ID, its entropy/length/alphabet, retry limit or
collision result. `regenerate(old,new,data)` accepts a `new` ID but does not
require `old != new`, does not require the new committed path to be absent, and
does not state what happens when it already exists.

This is security-critical because ordinary POSIX/PHP `rename(stage, committed)`
replaces an existing destination. The current text could overwrite another
valid session while still satisfying the stated second rename. Locks serialize
that known ID but do not make overwriting it safe.

Define one implementable policy:

- adapter-owned cryptographically secure ID generation with exact alphabet,
  length/entropy and bounded collision retries;
- validation and `old != new`;
- new ID lock acquired before existence check;
- exact collision outcome (`INVALID` or regenerated candidate), never overwrite;
- an atomic no-clobber publication primitive/strategy available in supported
  host/image PHP, with false/warning/Throwable mapping and directory fsync;
- deterministic Gate 2 collisions for committed new IDs and identity swaps.

The random stage and tombstone names also need CSPRNG ownership and collision
handling. PHP rename may overwrite an existing `.revoked-*` target; specify
exclusive name reservation/retry or another no-clobber design rather than
assuming 32 random hex characters prove absence.

### R2 — NOT_FOUND semantics for start, regenerate and destroy are missing

`start(id?)` says an absent ID starts anonymous empty state, but does not
distinguish no cookie from a syntactically valid cookie whose committed file is
missing/revoked. Strict-mode fixation protection requires an exact decision:
reject/rotate unknown supplied IDs rather than accepting them as a new session.

Likewise:

- first successful login may regenerate an anonymous ID for which no committed
  old file was ever written;
- concurrent expiry/destroy may make old disappear before regeneration;
- logout/destroy may be invoked with no committed file.

Specify exact typed and public outcomes for each. Decide whether anonymous
regeneration has an explicit no-old branch, whether missing old after an
authenticated start is `NOT_FOUND`/503, and whether destroy of an already absent
session is idempotent success with deletion cookie or failure. Gate 2 cannot
derive login/logout behavior from the current generic `NOT_FOUND` token.

### R3 — `UNAVAILABLE(category, correlation)` is not an exact typed contract

Sections 3/6 require a safe enum and exact log, but never enumerate categories
or state which primitive failures map to each. Without a closed list, tests can
accept production-derived categories or leak phase/path information under a new
label.

Define the exact safe categories (for example configuration, path metadata,
lock timeout, read, write/flush/fsync, rename/destroy, close and GC as intentionally
grouped), exact 12-lowercase-hex correlation generation/fallback, and mapping for
every warning/false/Throwable/fault-injection point. State whether GC best-effort
failures log a category without failing a request, and how reporter failure is
contained. Public 503 must carry no correlation header if that is intentional;
the current response table should state this explicitly.

### R4 — trusted HTTPS boundary for `Secure` remains undefined

“Secure only under trusted HTTPS boundary” is not executable. Current LocalAuth
accepts either `HTTPS=on` or raw `HTTP_X_FORWARDED_PROTO=https`; the latter is
not safe unless a trusted outer server strips and replaces client input.

Pin the authoritative server variable/configuration, grammar and precedence,
including direct HTTPS, trusted proxy termination, absent/malformed/duplicate
forwarded input and local cli-server behavior. State which layer rejects a
spoofed client header before cookie creation. Gate 2 must prove untrusted
`X-Forwarded-Proto` cannot choose the cookie attribute and that real trusted TLS
does set `Secure`.

### R5 — persistent lock files have no lifecycle and can grow without bound

Every new session ID creates `l-<sha256>.lock`, but GC explicitly scans/deletes
only committed/stage/revoked filenames and production otherwise never removes
files except destroy. Lock files therefore accumulate permanently even after
session/tombstone expiry, creating an inode-exhaustion path from repeated session
creation.

Define safe lock-file retirement. It must acquire the lock nonblocking, prove no
committed/stage/revoked state still needs that ID, revalidate ownership/identity/
age, unlink and fsync the directory without breaking mutual exclusion. If lock
files are intentionally permanent, bound the total namespace and document an
operator cleanup seam; the current unbounded behavior is not acceptable for a
604800-second lifecycle.

### R6 — GC selection can starve expired entries and is ambiguous about limits

“Scans at most 100 binary-sorted exact filenames” does not say whether 100 is a
directory-entry scan limit, eligible-candidate limit or deletion limit. Taking
the first 100 lexicographic entries can repeatedly ignore expired sessions later
in the namespace while earlier entries are newer/locked, especially across the
three filename prefixes.

Specify a deterministic progress policy: e.g. scan all names under a bounded
directory ceiling and process the oldest 100 exact candidates, or maintain a
safe cursor/rotation with exact restart semantics. Pin how unknown/wrong-mode
files affect the budget, how lock files participate, and what happens when the
directory exceeds the supported ceiling. Tests need >100 mixed new/old/locked/
unknown entries to prove no truncation-as-success or starvation.

## Confirmed properties

- Configuration keys, absent versus explicit-empty behavior, instance grammar,
  exact path derivation and path-input exclusions are coherent.
- The managed-root trust boundary and exact root/descendant modes are feasible
  for current Compose; same-uid/openat limitations are honestly residual.
- Session/lock/stage/revoked filename grammars, ID grammar, 1 MiB byte bound,
  euid/non-symlink modes and operation-adjacent identity checks are otherwise
  precise.
- Exclusive hash-lock acquisition, 2-second monotonic timeout and binary
  multi-ID order provide implementable cooperating-process serialization.
- Normal staged write, fflush/fsync, same-directory publication, directory fsync
  and pre/post-rename failure distinction are well specified once no-clobber is
  added.
- The tombstone regeneration state machine correctly prevents dual-valid IDs:
  before old rename old remains; between renames neither ID is addressable;
  after new publication only new is valid. Post-publication tombstone cleanup is
  correctly not a rollback condition.
- Destroy-by-rename before cookie/redirect, buffered response commit, disabled
  native shutdown writes and exact GET/POST/HEAD 503 mapping are coherent. The
  literal headers/body, forbidden header surface and redacted log format are
  strong apart from the missing category enum/correlation statement.
- Cookie name/port, lifetime, Path, HttpOnly, SameSite, strict ID, CSRF and safe
  return-to preservation are appropriate; only the trusted Secure signal needs
  exact definition.
- Host/URI and known/unknown static asset priority, two-consumer ownership,
  architecture ban on alternate native primitives/roots, persistent restart,
  production-versus-test cleanup separation and Gates 1–5 are sound.
- The DRAFT marker correctly prevents Gate 2 and no current task is marked done.

## Verification

```text
openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid

git diff --check -- <reviewed executable spec and OpenSpec package>
exit 0, empty output
```

## Verdict

**CHANGES_REQUIRED.** The core filesystem transaction and HTTP failure design is
substantially complete, but safe ID publication/collision, missing-file
semantics, closed typed categories, trusted HTTPS and bounded lock/GC lifecycle
must be exact before owner approval. Any normative revision requires a new hash
and fresh independent Gate 1 review; Gate 2 remains prohibited.
