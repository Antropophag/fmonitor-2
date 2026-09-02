# PILOT-SESSION-STORAGE-001 v5 — independent constructible-API Gate 1 rereview

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/rbac_fixture_name_diagnosis`  
Gate: 1 rereview after the v4 constructible-API findings  
Verdict: **READY_FOR_OWNER_APPROVAL**

The reviewer did not author or edit the reviewed executable specification,
OpenSpec artifacts, tests, support harness or production code. This append-only
review record is the reviewer's only change to the slice.

## Exact reviewed hashes

```text
9418800f5146845ae289d4e4fdcaf6179e113b06a97779605ca10ca7447e88f0  specs/PILOT-SESSION-STORAGE-001.md
f7d62b555dda987dc207039d0207bb8b312d1f820ddf4c2271930b17dc26deec  openspec/changes/define-pilot-session-storage-contract/proposal.md
1d8317fb4399b6d982d1e87e230c3ae72ae531e4f4bdaaab2955a965e919dcff  openspec/changes/define-pilot-session-storage-contract/design.md
2f79ae9dcb3b3997de47972512a8776001f7e86331dc3a1f84370e1c1f6a5779  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
ca5b383a81d6c42d48fdaffcf5232e8a2dd8dac5e0106ce2cd063c28d0afe5b9  openspec/changes/define-pilot-session-storage-contract/tasks.md
e351b312f3effcab55678a5e3512435fc3fc7db0cd39f8e40b2553951e924471  docs/operations/pilot-session-storage-gate1-rereview-v7.md
```

## Prior findings closed

The concrete final readonly `PilotSessionStorageConfig` now uses a valid
promoted-property constructor with an explicit body. The concrete factory
constructor and five-dependency `create` method retain valid implementation
bodies. The declarations parse under the repository PHP 8.5 runtime; no
abstract/bodyless concrete method remains in the exact examples.

Every independently constructible value whose public constructor is forbidden
now has an exact callable creation surface:

- `PilotSessionEntropyResult::ok` and `::failed` are explicit public static
  functions;
- all five `PilotSessionPrimitiveResult` named factories are explicit public
  static functions with their exact parameter unions;
- `PilotSessionFileHandle::mint` is an explicit public static function and
  promises one new opaque identity.

`PilotSessionFileStat` remains independently constructible through its exact
public scalar constructor. The port can therefore return successful stat and
opaque-handle values, safe native-shaped failures, short reads/writes and
entropy failure without reflection, private coupling or an invented API.
`PilotSessionOperationResult` correctly remains constructible only by the real
owner, preventing a verifier from manufacturing owner success.

## Semantic and boundary review

The v5 amendment changes only constructibility/signature precision. The
reviewed package preserves the approved v2 lifecycle: exact managed paths and
modes, lock ordering/deadline, atomic no-clobber write, regeneration/destroy
crash regions, response buffering and exact 503 mapping, cookie/route priority,
bounded locked GC and persistent Compose restart behavior.

The GRILL-009 and v3 anti-self-attestation boundary remains complete. Both HTTP
consumers use the single factory-created owner. Tests may inject primitive,
clock, entropy and observer implementations, but primitive results cannot carry
an owner/session outcome, the observer can only observe or block, and the
read-only inspector cannot attest authentication. Exact public events plus
independent material/raw-HTTP observations make production ordering and failure
mapping regression-sensitive without a test dispatcher.

The executable spec, proposal, design, delta spec and tasks consistently name
the v5 surface and static factories. Task 1.4 remains open for explicit owner
approval of these hashes; replacement RED and Gate 3 remain prohibited until
that decision.

## Verification

The exact corrected config, entropy-result, primitive-result and opaque-handle
declaration fragments were each passed to `php -l` under PHP 8.5.4; all returned
`No syntax errors detected in Standard input code`.

```text
openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid

git diff --check -- specs/PILOT-SESSION-STORAGE-001.md \
  openspec/changes/define-pilot-session-storage-contract
exit 0, empty output
```

## Verdict

No blocking Gate 1 finding remains. The exact v5 package above is
**READY_FOR_OWNER_APPROVAL**. This verdict does not approve future tests, RED
evidence, production implementation, GREEN, code review or Done. Any normative
change requires a new hash and fresh independent Gate 1 review.
