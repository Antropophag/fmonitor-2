# Pilot object/prepare RBAC fixtures — independent planning rereview

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit OpenSpec artifacts, specifications, code or
tests. This rereview supersedes the prior review only where closure is explicit.
No Gate 1 executable spec, test edit or implementation is authorized.

## Reviewed hashes

### `pilot-object-read-rbac-fixtures`

```text
68761da477db4f6aa7ccb7368446f07004328f3a6f69a336763df9036774eb4e  proposal.md
bd2cb1b9e48e2b8d8959d88d67b4591297d447f94856179ebaf8a1f18a7e891a  design.md
3128529b18a6226a6f66ebce2159bdf48ffb194f396869132cab179df99aabc2  specs/verification/pilot-object-read-rbac-fixtures/spec.md
34b0246214a3cea8b38d63a81613f3e972dd36f35cd1be0c0a46f871f80ccce6  tasks.md
```

### `pilot-prepare-rbac-fixtures`

```text
6cab8f3f769c680a40431d85216129c4c12c8b01aacae3b20eba3cf5c0b59ca3  proposal.md
d304fd0b499e4b8ef779ec23b4cf3b0d2d6c493e118fc5e80b146c8e3adbc937  design.md
3414505442bf49047782d8bca92954c64e606b271667738a0c505470338eb2cc  specs/verification/pilot-prepare-rbac-fixtures/spec.md
d5f6dafed1bee9824abc3097bad4e1e4fbb48b4824030d81046ee67b5cc74378  tasks.md
```

Prior review:

```text
be9fcf6b0720dcbf5d355a201a9714c991407e0f9d49fcbea3ab2e68c7daec95  docs/operations/pilot-object-prepare-rbac-fixtures-planning-review.md
```

## Closed findings

- The object delta/design/tasks now bind authorization requirements to exact
  `GET /pilot/objects`, exact `objects.read` and the trusted local actor ID.
  Card, prepare and generic shell are explicitly excluded from normative
  scenarios; list representation runs only after admission and cites approved
  list/UI contracts rather than current production HTML.
- Object negative planning retains legacy-only, inactive user/activation/role,
  missing/near-match, unavailable, committed revoke, unknown suffix,
  deterministic repeat, handler-read and mutation sensitivity.
- Prepare artifacts now distinguish local role permission
  `assignment_order.prepare` from the separate process capability with the same
  literal. Each one-sided missing-gate case is observable and `objects.read`
  does not imply prepare.
- Prepare normative scope is exact GET form composition; PUT method precedence
  is retained and POST/media/CSRF command semantics are explicitly excluded.
- Both designs retain explicit per-case actor environment/unset, isolated
  namespace/cleanup, positive controls, full rejected-state snapshots,
  no legacy/wildcard fallback, no production-derived DOM, no baseline growth
  and Gates 1–5 in correct order.

## Remaining findings

### R1 — prepare package requires production route migration while claiming fixture-only ownership

The proposal says the change will migrate exact
`GET /pilot/objects/{positive-id}/assignment-order/prepare` to a local route
permission gate before the existing process-capability gate. The delta requires
both gates and requires a local-permission denial to occur before process
capability/handler reads.

That is not current fixture alignment. Stable `LOCAL-RBAC-AUTH-CONTRACT-001`
connects only `GET /pilot/objects`; current prepare admission uses the existing
identity/directory path plus process capability. Adding a new local route gate
requires production HTTP/application wiring and establishes a new security
mapping.

However, the same package says:

- Impact: only prepare test/support fixtures are touched;
- Design ownership: tests/support, production IdentityAccess/InstallationProcess
  unchanged;
- proposal impact: production behavior does not change without a separate
  approved finding.

These statements contradict the normative two-gate requirement. Choose and
state one coherent change:

1. **Fixture-only:** preserve current prepare identity/process-capability seam;
   local RBAC rows may support actor resolution but are not asserted as a new
   route permission gate. Remove the two-gate production requirements.
2. **Route migration plus fixtures:** explicitly include production
   PilotHttp/authorization wiring, characterize the current mapping, provide an
   owner-approved exact `GET prepare → assignment_order.prepare` local mapping,
   update Impact/ownership/non-goals, and run the complete security delivery
   gates. A production security change cannot be hidden under a fixture-only
   title and rollback statement.

The second option may still retain the downstream process capability as an
independent gate, but it needs an explicit route-migration contract rather than
claiming only tests change.

### R2 — excluded POST remains in the revoked-grant scenario

The prepare delta correctly says POST/media/CSRF is outside the slice, yet
`Scenario: Revoked prepare grant` says the grant is removed before a new “GET or
POST”. Tasks and design now require exact GET only. Remove POST from that
scenario and bind revoke to a new GET invocation. Otherwise the normative delta
still imports an excluded command method and leaves Gate 2 scope ambiguous.

### R3 — object proposal retains stale card/UI-shell source and impact wording

Object design, delta and tasks are exact GET-only, but proposal Source oracle
still lists object list/card/UI-shell verifiers and Impact still says
object-read/UI-shell verifiers are touched. The same proposal declares
object-card/UI-shell authorization non-goals.

Replace those residual references with the exact object-list verifier/support
files. If a shared fixture helper is later consumed by card/shell tests, state
that as non-normative reuse without changing their authority or expected
behavior. This is a smaller coherence correction than R1, but required for an
unambiguous Gate 1 draft scope.

## Verification

```text
openspec validate pilot-object-read-rbac-fixtures --strict
Change 'pilot-object-read-rbac-fixtures' is valid

openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid
```

Strict validation does not detect the prepare production/fixture contradiction
or stale scope text.

## Verdict

**CHANGES_REQUESTED.** Exact route/method separation and observability are now
substantially improved. Before `APPROVED_FOR_GATE1_DRAFT`, the object proposal
must remove stale card/shell ownership, and the prepare package must decide
honestly between fixture-only alignment and a separately scoped production
local-route migration, then remove the last excluded POST reference. Request a
fresh planning rereview at new hashes; no implementation is authorized.
