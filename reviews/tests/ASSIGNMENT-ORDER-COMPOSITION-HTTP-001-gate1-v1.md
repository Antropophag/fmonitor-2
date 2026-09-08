# Independent Gate 1 — ASSIGNMENT-ORDER-COMPOSITION-HTTP-001 v0.1

- Verdict: **APPROVED**
- Reviewer: `/root/composition_http_review`, separately tasked agent; did not author the specification, tests, implementation, or fixture plumbing.
- Date: 2026-09-07
- Reviewed repository HEAD: `2948146225fbbb9a028396d4ff2edbe1f0d1daa3`; planning worktree artifacts are identified by the exact hashes below.
- Review scope: executable specification and OpenSpec planning coherence only. No tests or production code authored or approved by this record.

## Reviewed artifacts

SHA-256:

| Artifact | Hash |
|---|---|
| `specs/ASSIGNMENT-ORDER-COMPOSITION-HTTP-001.md` | `b53615e2387d7c32bfd0c49789a82349a577d4c9fcd30c0b714b484e7df7a757` |
| `openspec/changes/expose-assignment-order-composition-http/proposal.md` | `a84a038eb8c5690ce6c216c79286dc359a624a4f8b6f14bd44d6c2c0757a1fd4` |
| `openspec/changes/expose-assignment-order-composition-http/design.md` | `1efa3ee301d88903739a9aa92ae4b2f5089c6313b7b02cfb2b894d715ccf9419` |
| `openspec/changes/expose-assignment-order-composition-http/tasks.md` | `c0b968926ea834d957165384b19d373ed1dad4d2269c0bc1149ebde415c5d565` |
| `openspec/changes/expose-assignment-order-composition-http/specs/pilot/assignment-order-composition-http/spec.md` | `5d3c795007715f290419981d21a01b20917b5ce01d51dc0043fa0d103659fa33` |

Reviewed against `AGENTS.md`, `docs/development-process.md`, `PRODUCT.md`, `CONTEXT.md`, and the pilot specification/data model. Reused existing approvals in `reviews/code/ASSIGNMENT-ORDER-SELECTION-NATIVE-001-native-v1.md`, `reviews/code/ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001-binding-v1.md`, and `reviews/code/ASSIGNMENT-ORDER-TEMPLATE-GENERATE-001-v1.md`. Their domain behavior is inherited, not reopened for review here.

## Findings

No material ambiguity blocks RED for this bounded slice.

The public seam is confirmed: raw HTTP through `rapid-pilot/router.php` and canonical `PilotHttpEntrypoint`, native session/LocalAuth and production factories. The composition owner owns the new authorized, coherent read projection; HTTP owns transport decoding and rendering, and introduces no domain SQL or alternate mutation owner.

Actor, exact authority, body admission, CSRF, success redirects, rejection/status mapping, safe errors, and retry intent preservation are observable at that seam. Worked identities 81/82, revisions 0/1/2, immutable snapshots, silent replay, stale rejection, and post-original `new_order` supply independent expectations. Real native setup must be shown working before recording a missing-behavior RED.

The author's final admission clarification was reviewed before approval: LocalAuth invalidation of an inactive session follows the existing 303 login path; missing permission/role yields 403, and actor revocation after admission yields application denial/403. This preserves the native session contract and distinguishes it from command authorization.

Selection and optional template generation remain separate actions. Existing owners retain append-only selection and generation audit semantics. PDF response headers/bytes, absence of stored template artifacts, latest date, and unchanged selection/original/application/opening/checklist facts are observable. Original acceptance is a public-command fixture precondition for the post-original selection case, not new upload HTTP behavior.

The explicit fresh flag and enumerated predecessor denials prevent the addressed old writers from becoming alternative fresh selection paths. The existing prepare link redirects to the new form. No historical migration, original HTTP, application, opening, grant bootstrap, or deployment scope is added. Public shlz-ui alone follows the owner's explicit instruction; the superseded ServiceDesk-source requirement is not a gate.

## Nonblocking interpretation notes

- `lastTemplateDate` is scoped to the latest selected identity and inherits the approved date reader's greatest-event-ID semantics. Replacing a selection cannot borrow its predecessor's template date.
- Missing object/case is absence/404; malformed or unavailable read dependencies remain failure/503 under the stated fail-closed rule. An empty candidate list is not a substitute for source failure.
- The sole accepted confirmation value is `yes`; omitted confirmation is the explicit 422 case, and a supplied invalid value falls under shape-invalid 400. A null engineer/empty installer list retain the specified required outcomes.

These follow the reviewed contract and inherited owners; they introduce no new acceptance scope or domain decisions.

## Gate decision

Gate 1 is **APPROVED** for the exact specification above. Proceed to demonstrated raw-HTTP RED and independent Gate 3. This record does not approve tests, implementation, UI QA, integration, deployment, or launch; subsequent gates and their required verification remain mandatory.
