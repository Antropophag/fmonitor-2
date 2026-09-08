# ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001 — independent Gate 1 review v01

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed repository HEAD: `8d3f2625e7b2721d878f8165ef1720325b3efc05`
- Candidate SHA-256: `5f87fc98c57436261e15293dcbe7a78b6c1d8e3c71bec2104cf516d623c41199`
- Parent original-upload SHA-256: `d23b9cd924be6ce9deb905a0c742e7b0449eb8755fdb5af8d9caac094934fbf3`
- Scope: shared opaque opened-file owner and pure attribute policy only; no code, tests, OS/file fixture or database reviewed or executed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the candidate specification, parent specification
or OpenSpec artifacts. This append-only review is the only authored artifact.

## Determination

The shared-owner direction is technically valid and materially different from
both automatically rejected mechanisms. It uses an ordinary non-creating open,
real retained-handle `fstat`, an opaque private-constructor owner and stable-file
public tests. It does not require native interposition, metadata substitution,
an observer in the validation/open interval, permission transitions, timing
races, privilege manipulation or an alternate production selector.

The candidate also makes the evidence boundary honest. Stable black-box tests
prove public policy, acquisition, append and lifecycle behavior but cannot
distinguish real descriptor `fstat` from a repeated pathname observation. The
mandatory exact-SHA Gate 5 source proof supplies that missing structural
evidence and is an appropriate complement under the repository's Gate 5 code
review. Absence of that proof expressly prevents approval even if behavioral
tests pass.

Three remaining technical ambiguities prevent Gate 1 approval at the reviewed
bytes. None requires a new product decision; they clarify the existing exact
file-policy and fixed lifecycle/error contract.

## Blocking findings

### G1-SLO-01 — the policy permits special mode bits despite “exact 0600”

Section 2 defines access acceptance as:

```text
(mode & 0777) === 0600
```

That expression ignores setuid, setgid and sticky bits. Consequently regular
file modes `0104600`, `0102600` and `0101600` all pass the stated policy. The
owner resolution and parent contract require exact permission mode/bits `0600`,
and the candidate repeatedly describes the invalid case as mode other than exact
`0600`. Calling the narrower mask “not a new policy for special Unix bits” does
not resolve whether these modes must be accepted or rejected.

This is security-relevant at both the final pathname check and the retained
descriptor check. A Gate 2 author could reasonably assert rejection from
“exact 0600”, while an implementation following the literal pure-policy formula
would accept the same input.

Define the inherited rule explicitly. The consistent interpretation is that all
permission and special bits are exact, e.g. `(mode & 07777) === 0600`, applied
identically to pathname and descriptor observations. Add independently fixed
one-axis pure-policy cases for setuid, setgid and sticky additions. This enforces
the existing owner-approved outcome rather than introducing a new one.

### G1-SLO-02 — public explicit-close failure has no defined outcome

Section 2 exposes `close(): void` and `isClosed(): bool`. Section 4 says close
“attempts” to close the owned handle, permanently closes the owner and is
idempotent, while the destructor suppresses exceptions/output. It does not state
what the first explicit `close()` returns or throws when the native close reports
false, warns or throws, nor whether `isClosed()` must become true before that
failure is surfaced.

The distinction matters because the owner must never retry I/O or reuse/reopen a
possibly closed native resource. Two incompatible implementations currently fit
the prose: silently consume a failed close, or throw the fixed runtime error.
They produce different public behavior and different repeat-close observations.

Pin one exact contract. A constructible defensive rule is: detach/mark the owner
closed before attempting the one native close; normalize warning/false/Throwable
to the fixed `RuntimeException('safe log unavailable')`, code `0`, previous
`null`; retain `isClosed() === true`; and make every later `close()` an I/O-free
success. The destructor uses the same one-attempt close path but suppresses every
failure and output. Gate 2 should cover the public state machine only through an
approved safe public seam; if a stable ordinary file cannot cause native close
failure without a rejected mechanism, Gate 5 must explicitly verify this
failure-path structure alongside the already required handle proof rather than
inventing a timing or interception fixture.

### G1-SLO-03 — direct-import regressions do not fix owner loading

The already approved production-boundary regression loads
`AssignmentOrderOriginalRuntime.php` and `AssignmentOrderOriginalFileStorage.php`
directly, including in its existing controlled wrong-owner child, without using
the general application autoloader. The candidate allows a new public owner and
policy but does not say where they are declared or which existing direct import
must load them before the compatibility facade/factory references them.

This can produce a false result on invalid-config cases: an undefined owner
class thrown inside the production factory's broad `Throwable` translation can
become the expected fixed `AssignmentOrderOriginalProductionConfigurationUnavailable`
instead of proving wrong-mode or wrong-owner rejection. It also makes the direct
valid construction depend on an unstated include-order choice.

Specify one production declaration/load boundary. The shared owner and policy
must be available after the existing direct Runtime/FileStorage imports used by
the public regression and its child, without a test-only require or dependence
on the general autoloader. Gate 2 must assert `class_exists` for the new owner
and policy before invalid factory cases and must first complete the valid
owner/factory control, so a missing declaration cannot masquerade as expected
invalid configuration. This preserves the existing wrong-owner regression as
an inherited control; it does not authorize a new privilege probe or a change
to that fixture.

## Confirmed constructible properties

- The PHP declaration block is syntactically valid with the inherited interface.
- The public owner has a private constructor, no raw stream/FD, no arbitrary
  adoption, no mutable public property, no opener/metadata-provider callback and
  no clone/serialization escape.
- Acquisition preserves the trusted configured path, uses non-creating and
  non-truncating `r+b`, obtains current EUID from `posix_geteuid`, requires a
  final non-following pathname identity, calls real `fstat` on the new handle,
  and retains that same handle for count/append/close.
- Acquisition failures close an opened handle, expose one fixed internal error,
  do not write or repair the file, and remain before database/private-root
  access. Production maps them to the existing fixed redacted configuration
  exception.
- Locked seek-to-end, one write, flush and attempt-always unlock preserve the
  existing cooperating-writer append contract without claiming a new global
  sequence. Initial line counting and subsequent writes use the retained handle
  without reopening the pathname.
- The compatibility `AssignmentOrderOriginalFileSafeLog` is constrained to a
  typed-owner facade with no second open/write implementation. Worker and
  evidence-reader configuration identities and resource order remain separate
  and unchanged.
- The independently fixed correlation hashes recompute to `11e594f48195` and
  `e79acd97ac88`; the sequence and exact appended JSON examples are coherent.
- The parent spec replaces the former observer/permission-transition contract
  rather than retaining it. The four OpenSpec artifacts carry the same shared
  owner, stable behavioral evidence and mandatory structural-proof boundary.
  Strict OpenSpec validation passes.
- The existing controlled wrong-owner regression remains inherited evidence;
  this review did not invoke it. Its direct Runtime/FileStorage import model
  requires the explicit loading correction in G1-SLO-03.
- No new product action, role, configuration field, user-visible result or
  exception policy is introduced. No owner approval is required for the three
  corrections above.

## Verification

```text
$ git rev-parse HEAD
8d3f2625e7b2721d878f8165ef1720325b3efc05

$ shasum -a 256 specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001.md
5f87fc98c57436261e15293dcbe7a78b6c1d8e3c71bec2104cf516d623c41199

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ extracted PHP declarations with inherited safe-log interface stub | php -l
No syntax errors detected in Standard input code

$ independent correlation recomputation
11e594f48195
e79acd97ac88
```

No RED is authorized at these bytes. Correct all three technical contracts coherently
in the owner spec, parent and relevant OpenSpec artifacts, then obtain a fresh
independent Gate 1 rereview before writing or running tests.

## Exact reviewed hashes

```text
5f87fc98c57436261e15293dcbe7a78b6c1d8e3c71bec2104cf516d623c41199  specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-OWNER-001.md
d23b9cd924be6ce9deb905a0c742e7b0449eb8755fdb5af8d9caac094934fbf3  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
9c1944bb773c0f8939e609b4e02387e05f5e946084aa7100fe66b7dd9b5564da  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
ce09df99b0fcd26578945d1e1f1a7c6e1b6e58d164bf855946288ad35a851ff1  openspec/changes/replace-pilot-registration-with-original-upload/design.md
477719ece4e5a05527d4c9e4a2d7a9f18e4ab78638c4ad2a0bbbbad5e87b136c  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
8407caa07d7532569d25cc04b3bf1726b3ab28c1148f3cba7bd8a5b8da0528d5  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
253aafaf2e7654f152d8af97653fcd67d63173acd0698815dcbe589489e2d011  docs/operations/safe-log-shared-owner-amendment-2026-09-06.md
e5eba523ea22514020338678fa206dc817bdc98c8042ba757527af644542feeb  docs/operations/safe-log-shared-owner-feasibility-review-2026-09-05.md
```

This review omits its own circular hash and does not alter historical rejection
records. It is not Gate 3, implementation approval, combined original-command
Gate 5 or closure of `G5-SAFELOG-2`.
