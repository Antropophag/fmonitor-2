# PILOT-SESSION-STORAGE-001 v4 — independent constructible-API Gate 1 rereview

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/rbac_fixture_name_diagnosis`  
Gate: 1 rereview after the v3 exact-PHP-API findings  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or edit the reviewed executable specification,
OpenSpec artifacts, tests, support harness or production code. This append-only
review record is the reviewer's only change to the slice.

## Exact reviewed hashes

```text
d1200cb0e9cec05e5660149e82d5a2306f80185f7e80066d50e5ac89753478d4  specs/PILOT-SESSION-STORAGE-001.md
aead6a0db1b37cbe6f8f47e939da40bd76339c6fcda04f659b25f9a841b70348  openspec/changes/define-pilot-session-storage-contract/proposal.md
833084dcb7ac64652ed61ab900ab1042a41ec273b71cabfd2055db5c0aa11448  openspec/changes/define-pilot-session-storage-contract/design.md
70f0e21ed94e6902b31e8c50f704b494409b8f2f813afc6c0e47c97b0e2ba788  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
920878778afd506bd8c2fa19529537ffb9a699c7769c4dad1a87574aa8f1b2ce  openspec/changes/define-pilot-session-storage-contract/tasks.md
fb4feb4047ded992eb220a0cf65b888fcdf26fced99d62595cc16c5e90b53931  docs/operations/pilot-session-storage-gate1-rereview-v6.md
```

## Preserved and satisfactory semantics

The reviewed package preserves the previously reviewed v2 filesystem lifecycle,
locking/no-clobber publication, crash regions, response buffering, cookie and
route priority, bounded GC and read-only non-secret Compose inspection. It also
preserves the v3 anti-self-attestation boundary: Gate 2 must invoke the real
factory-created owner; primitive wrappers can return only primitive-shaped
results; observer/event recorder and inspector cannot supply or attest an owner
result; HTTP and material state remain independent evidence.

The v3 factory-class syntax finding is partly closed: the concrete factory
constructor and `create` method now have bodies, and that class declaration
parses. Primitive and entropy result values now have public creation operations,
the filesystem port can receive an opaque handle identity, and an independently
authored adapter can construct file metadata from the exact scalar stat fields.
`PilotSessionOperationResult` correctly remains owner-created only.

## Blocking findings

### 1. The exact config DTO example still is not valid PHP

The same normative code block calls its contents the exact public PHP surface,
but concrete final class `PilotSessionStorageConfig` still declares a bodyless
constructor ending in `;`:

```php
final readonly class PilotSessionStorageConfig
{
    public function __construct(
        public string $stateRoot,
        public string $instance,
    );
}
```

PHP 8.5 rejects this before any test can load the API:

```text
$ printf '<the exact config declaration>' | php -l
PHP Fatal error: Non-abstract method PilotSessionStorageConfig::__construct()
must contain body
Errors parsing Standard input code
```

Use the valid promoted-property constructor ending in `{}` (or an explicit
body). The factory declaration with implementation bodies independently parses,
so this is specifically the remaining config declaration defect, not a PHP
version or unrelated type-resolution problem.

### 2. The alleged exact named-factory signatures do not state that they are static

The immutable entropy result, primitive result and opaque handle all have no
public constructor, while their only public creation operations are written only
as `ok(...): self`, `failed(): self`, `nativeFalse(): self`, `warning(...):
self`, `exception(...): self`, `shortIo(...): self` and `mint(): self`.
Neither the executable spec nor the delta states `public static function`.

This distinction is executable in PHP: a non-static method cannot be invoked
without an instance, and these types deliberately provide no public constructor
from which an independent filesystem/entropy adapter could obtain that instance.
Gate 2 would therefore have to guess that “named factory” means static, invent a
different bootstrap path, or use reflection/private coupling. That fails the v3
requirement to pin exact public creation signatures.

State the exact callable signatures, including `public static function`, for
all three creation surfaces. The factory invariants, operation-bounded values,
safe exception-code reduction and lack of owner outcome in primitive results
are otherwise sufficient and preserve the anti-self-attestation boundary.

## Verification

```text
openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid

git diff --check -- specs/PILOT-SESSION-STORAGE-001.md \
  openspec/changes/define-pilot-session-storage-contract
exit 0, empty output

php -v
PHP 8.5.4 (cli)
```

## Gate decision

Gate 1 remains closed for v4. Correct the concrete config constructor and pin
the static named-factory signatures coherently in the executable specification
and OpenSpec package, produce new hashes and request a fresh independent Gate 1
rereview. Do not start replacement Gate 2 or seek owner approval for the hashes
reviewed here. Earlier v2/v3 approvals and pre-amendment Gate 3 records remain
historical only.
