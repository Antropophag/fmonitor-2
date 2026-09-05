# Assignment-order original safe-log descriptor — safer verification alternative

- Recorded: `2026-09-05T13:28:20Z`
- Author: independently tasked read-only safer-design reviewer
  `/root/importer_authority_review`
- Reviewed repository HEAD: `d1a5d0938ac9939f7098d045e4054bdf30e1b343`
- Scope: technical proposal only; no specification, planning, test or production
  edit; no Gate approval or RED verdict
- Disposition: **CONSTRUCTIBLE_ALTERNATIVE_FOR_GATE_1_DRAFTING**

## Automatic rejection preserved

The delegated native-interposition RED task was rejected by automatic safety
review as a possible cybersecurity risk. It produced no RED and no verdict.
This record does not retry, rephrase, implement or bypass that technique.
Native interposition, syscall interception, loader injection and privilege
changes are excluded from the alternative below.

The earlier technical Gate 1 record remains append-only historical evidence for
the then-reviewed candidate. Its native-interposition verification mechanism is
not actionable after the automatic rejection. A revised exact candidate using
this safer mechanism needs its own fresh Gate 1 review before RED.

## Determination

The approved retained-descriptor invariants can be verified safely through one
explicit verification-only composition at the logger-open boundary. The same
production logger owner performs final pathname validation, opens the file,
performs descriptor `fstat`, compares identity and attributes, closes on
failure, and publishes the fixed factory error. Production construction always
uses an inert observer. The verification facade changes only which observer is
supplied to that same owner and preserves the production factory's safe-log-
before-database/private-root ordering.

The required mismatch is created with an ordinary permission change on one
revalidated task-owned regular fixture:

1. fixture starts as current-EUID-owned exact `0600`;
2. the real logger completes its final non-following pathname observation and
   validates regular/current-EUID/`0600`;
3. at the one verification phase, the test observer changes that same inode to
   exact `0640` using ordinary `chmod` by its owner;
4. the logger opens the same path normally; device/inode still agree, but real
   descriptor `fstat` observes mode `0640` and must reject it;
5. the logger closes the descriptor and the public factory returns only the
   existing fixed redacted configuration exception before any database or
   private-root access or diagnostic write.

This directly distinguishes the current mode-blind descriptor implementation
from the approved invariant without races, sleeps, repeated replacement,
interception, elevated credentials or external resources.

## Minimal exact candidate API

The smallest sufficient verification contract is:

```php
namespace FMonitor2\AssignmentOrderOriginal;

enum AssignmentOrderOriginalSafeLogConstructionPhase: string
{
    case FINAL_PATH_VALIDATED_BEFORE_OPEN = 'final_path_validated_before_open';
}

interface AssignmentOrderOriginalSafeLogConstructionObserver
{
    public function observe(
        AssignmentOrderOriginalSafeLogConstructionPhase $phase,
    ): void;
}

final class AssignmentOrderOriginalProductionFactoryVerification
{
    public static function create(
        \mysqli $database,
        AssignmentOrderOriginalProductionConfig $config,
        AssignmentOrderOriginalSafeLogConstructionObserver $observer,
    ): AssignmentOrderOriginalApplication { /* same production composition */ }
}
```

The phase is emitted exactly once only after the final non-following pathname
observation has passed exact device/inode, regular-file, current-effective-UID
and `0600` validation, and immediately before the real append open. It carries
no path, descriptor, credentials or mutable production object. The test already
owns the exact fixture path and needs no phase payload.

`ProductionAssignmentOrderOriginalFactory::create(mysqli, config)` must call
the same internal composition with a final inert observer; it accepts no new
parameter and exposes no environment, request, CLI, global or service-locator
selector. The verification facade is a named public verification composition,
not a second logger implementation. It must execute the same safe-log owner and
preserve exact production construction ordering and exception translation.

Observer `Throwable` is a technical construction failure: before open it has no
descriptor to close, must remain before DB/private-root access, and must surface
only the same fixed redacted configuration exception. Production's inert
observer cannot throw.

## Exact safe verification contract

Before qualifying RED, a revised executable contract should require all of the
following:

- a first unchanged control call through the verification facade with an inert
  test observer succeeds far enough to prove the exact safe-log construction;
  its task-owned database connection and valid private root are independently
  prepared, and the observer records exactly one phase;
- the mismatch call starts with the same revalidated inode, regular type,
  current effective UID, exact `0600`, fixed bytes and independently captured
  metadata; the observer alone changes mode to exact `0640` and proves the
  change succeeded before returning;
- expected result is exactly
  `AssignmentOrderOriginalProductionConfigurationUnavailable`, message equal
  to the class basename, code `0`, `previous=null`, with no path, mode,
  credential or underlying exception detail;
- database spy/counters remain zero, the private root is neither validated nor
  touched, safe-log bytes remain identical, and metadata differs only by the
  deliberate mode change until test cleanup restores `0600`;
- after the exception, the live child enumerates its PHP stream resources,
  applies `fstat` read-only, and proves no newly retained stream has the exact
  fixture `(device,inode)` identity. This comparison uses a before inventory and
  is completed before child exit, so process teardown cannot masquerade as
  logger closure;
- cleanup first revalidates the exact task-owned file/root identities, restores
  mode when necessary, closes resources, and removes only enumerated owned
  artifacts. Setup, phase, permission-change, inventory or cleanup failure is
  `SETUP_FAILURE`, not RED;
- runs are bounded by monotonic parent deadlines with terminate/reap on failure.

PHP's `get_resources('stream')` plus read-only `fstat` is a suitable ordinary
child-local observation for the exact descriptor identity. A revised Gate 1
review should pin the exact inventory algorithm and treatment of streams that
cannot be statted so an uninspectable resource cannot be silently ignored.

## Type and effective-UID coverage

The safe mode disagreement is sufficient to demonstrate that descriptor
attributes must be checked after open; it does not reduce the production
invariant to mode. The normative implementation still must check, from the same
real descriptor `fstat`, all three attributes: regular-file type, exact current
effective UID and exact `0600`. Gate 5 must reject mode-only implementation.

This proposal does not attempt unsafe ownership changes or manufacture special
file descriptors. Separate type/UID mutation tests are unnecessary for this
narrow safe RED only if the executable contract keeps the three checks exact
and code review verifies their common descriptor-stat source and strict
comparisons. Any proposal to weaken or omit type/UID would change the inherited
owner outcome and is outside this alternative.

## Required process disposition

This record is not approval. Before work resumes:

1. replace native-interposition language coherently in the executable spec and
   all four OpenSpec artifacts with the exact verification facade, phase,
   ordinary `chmod` fixture and live descriptor-inventory contract;
2. obtain a fresh independent technical Gate 1 verdict over the new exact
   hashes; no new owner approval is needed because the security invariant and
   external failure outcome do not change;
3. only after Gate 1, demonstrate RED and obtain fresh independent Gate 3 before
   adding the verification composition or descriptor checks;
4. keep the automatic rejection and superseded mechanism records intact.

## Exact reviewed SHA-256

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
9781ab565bf30e4a857cbf64886d8db43e15e773dab6fd3cb00ee6f4a3b75e56  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
eaca2dc34a2587f8220250ff019c6209934497adf35897338f1ab986a3b12339  docs/operations/assignment-order-original-safe-log-descriptor-gate1-review-2026-09-05.md
0e430ef58c62076feb1291b6742dd5cd61b1f3adce156dc60bf79055e018b97f  docs/operations/assignment-order-original-production-safe-log-descriptor-integrity-audit-2026-09-05.md
4c893c34377546ded04fc094bf5cbfd8dd5647655416ec25a8e6e28c65ef114d  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
```

## Final disposition

**CONSTRUCTIBLE_ALTERNATIVE_FOR_GATE_1_DRAFTING.** A verification-only phase
observer plus ordinary owner `chmod` and live exact descriptor inventory can
fully observe the approved mode mismatch, fixed no-resource failure and
descriptor closure while preserving production ownership and ordering. It
avoids the rejected technique. It does not authorize RED or implementation.
