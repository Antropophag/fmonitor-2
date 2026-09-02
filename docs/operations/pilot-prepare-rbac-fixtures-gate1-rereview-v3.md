# Independent Gate 1 rereview v3 — PILOT-PREPARE-RBAC-FIXTURES-001 v2

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/session_v3_correction`  
Gate: Gate 1 exact-PHP-API rereview after the owner-approved GRILL-009 amendment  
Verdict: **CHANGES_REQUIRED**

The reviewer authored neither the reviewed executable/OpenSpec artifacts nor
their tests or production implementation, and did not edit any of them during
this review. This append-only review record is the reviewer's only change.

## Exact reviewed hashes

```text
4565edccce6cdc22d214c9410d6363db480d0484214e009ba662d3c0910d3545  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
d791104bf14b17911b4e23e90d0eef7a3e0f7f41cb12960c4ca4e9eec3fc9e97  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
948253968f7870c14344bbcd8fdd33260f55ce8a132bda0fea55702e53111407  openspec/changes/pilot-prepare-rbac-fixtures/design.md
237f466d90c40b72a61d28fc0b243b0d834ea305ca572fc68a88433289ac3314  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
53abcf57ade015dd3db719185f38a95fc140ae4298ba1fd570c2e64a4ff59b52  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
acc9d92e9a96b7bf066a78a35cee16d43d00c767403755660230fec07963291d  docs/operations/grill-009-owner-decision-2026-09-02.md
2e8ca8e3db80d0e4b4b214eff9c93ef291fcf305f53b02c0904b6de733a91a7e  docs/operations/grill-009-rbac-exact-hash-approval-2026-09-02.md
```

## Preserved and satisfactory semantics

The package preserves the two independent admission gates, their ordering,
GET/HEAD no-write snapshots, rejection redaction and the exclusion of POST from
this slice. It also preserves the owner-approved GRILL-009 observation model:

- the canonical factory, not a test graph, creates exactly one real
  `ProductionPrepareFormRenderer`;
- it calls one decorator exactly once and reuses the returned renderer at every
  canonical prepare-render position;
- `null` is the normal production call and makes the factory select the
  identity decorator internally;
- an explicit spy receives only the already-created renderer, not environment,
  dependencies, entrypoint or application graph;
- the spy must wrap/delegate the real renderer without changing input/output,
  while manual graph reconstruction, reflection, shadowing and replacement
  renderer evidence remain forbidden;
- allowed GET requires one real render and exact delegated bytes; every stated
  rejection requires zero renderer calls.

This is a narrow composition seam and does not grant the spy authorization,
process, persistence or graph control. Historical GRILL-009 owner approval is
retained as history, while task 1.4 correctly requires review and owner approval
of the newly clarified hashes before replacement Gate 2.

## Blocking finding: the declared exact PHP surface is not syntactically valid

The executable contract labels its block `Exact public PHP seam`, but its
concrete final identity decorator declares a bodyless non-abstract method:

```php
final class IdentityPrepareFormRendererDecorator implements PrepareFormRendererDecorator
{
    public function decorate(PrepareFormRenderer $renderer): PrepareFormRenderer;
}
```

PHP rejects that declaration: a concrete method must have a body. The same
executable block then declares
`ProductionPilotHttpEntrypointFactory::create(...)` outside a class or interface,
which is notation rather than valid PHP. The delta spec repeats that standalone
factory notation. Conversely, the design wraps `create` in the concrete final
factory class but still ends it with `;`, which PHP also rejects.

Because these documents call the signatures exact, Gate 4 would have to choose
unreviewed method bodies/declaration shapes merely to make the approved API
loadable. Correct all three artifacts to one syntactically valid representation:
concrete identity `decorate` and concrete static factory `create` declarations
must have bodies (implementation bodies may be explicitly elided inside `{}`),
and the executable/delta factory signature must be inside the exact final class.
The interface method correctly ends in `;` and needs no change.

No broader seam is required. In particular, do not add graph access, a renderer
factory supplied by tests, a production selector, or a second decoration call.

## Verification

```text
openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

git diff --check -- specs/PILOT-PREPARE-RBAC-FIXTURES-001.md \
  openspec/changes/pilot-prepare-rbac-fixtures
exit 0, empty output
```

Strict OpenSpec validity does not parse PHP embedded in Markdown and therefore
does not close the syntax finding.

## Gate decision

Gate 1 remains closed for the newly clarified package. Task 1.4 stays open;
tasks 2.1 and later must not start from these hashes. After the exact concrete
PHP declarations are corrected coherently, produce new hashes and request a
fresh independent Gate 1 rereview followed by explicit owner approval. Prior
approval and RED/review records remain historical and do not approve the new
surface.
