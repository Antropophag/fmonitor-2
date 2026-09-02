# Restore pilot work navigation — independent planning rereview

- Date: `2026-09-02`
- Reviewer: separately tasked fresh agent `/root/planning_reviewer`
- Scope: superseding Gate 1 planning rereview only; no tests, production code,
  owner approval, or Done approval
- Prior review: `docs/operations/pilot-work-navigation-planning-review.md`

## Exact reviewed artifacts

```text
be58155887eaa916f4ebfaa95617ded0be20f8511756266beea9aa8e97fd42f9  openspec/changes/restore-pilot-work-navigation/proposal.md
7f5c6b89417b6e7c7857be66f07f107f3fcda03b6ba2f6a845845241b065365c  openspec/changes/restore-pilot-work-navigation/design.md
6036750ac7296c3ee4d6fa1705fc56b21f15da7f7a5f8ba5d2182804dc587eb1  openspec/changes/restore-pilot-work-navigation/specs/ui/pilot-work-navigation/spec.md
e3b65fe4c43311675b94df77689e78aa90c3a786f1b429233dd17c6c751a44ef  openspec/changes/restore-pilot-work-navigation/tasks.md
```

Reviewed inherited contracts:

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
b54412b14ca3d3e8ad63fc629d3dda7e5902209c52a1b2acd92dade5ba053531  specs/PILOT-UI-SHELL-001.md
```

The reviewer also checked `PRODUCT.md`, `CONTEXT.md`,
`docs/development-process.md`, the pilot contracts, current OpenSpec state and
the prior `CHANGES_REQUIRED` findings. The reviewed artifacts were not edited.

## Closure of prior findings

All three blocking findings are closed.

1. **The governed screen set is exact.** The delta spec enumerates configured
   successful `GET|HEAD` representations for root, object list/card/prepare/
   checklist, construction-control queue/checklist, installer directory, and
   admin user/role directories. Compatibility composition, assets, commands,
   redirects, errors and screens not using `PilotView::document` are explicitly
   outside the new successful-shell behavior. Tasks require a representative of
   every enumerated route family rather than silently narrowing to one page.
2. **Root and non-root DOM behavior is exact.** Exact `/pilot/` has one non-link
   `Моя работа` item with `aria-current="page"`, no `href`, and no duplicate
   label/destination. Every governed non-root screen has one ordinary same-origin
   `<a href="/pilot/">` and no `aria-current`. This is consistent with the
   inherited shell navigation/current-item contract.
3. **Inherited transport outcomes are preserved explicitly.** The spec and tasks
   retain exact `/pilot` trailing-slash redirect behavior and inherited
   `401/403/404/405/503` status, plaintext body and application-controlled
   headers, including `Allow`, `Location`, `Retry-After` and correlation headers
   where applicable. The navigation verifier does not re-own RBAC or method
   semantics.

## Additional checks

- The public seam remains successful rendered pilot HTML, not a private helper
  or database side channel.
- Permission parity does not turn navigation into authorization; route admission
  remains governed by existing policies.
- Repeat rendering and business/audit zero-mutation are explicit.
- Scope remains presentation-only. No persistence, domain, route-table,
  rapid-pilot or schema ownership is introduced.
- The tasks preserve exact owner approval before RED, fresh independent test
  review before GREEN, and fresh independent code review after verification.

## Verification

- `openspec validate restore-pilot-work-navigation --strict` — PASS
  (`Change 'restore-pilot-work-navigation' is valid`).
- `git diff --check` — PASS (exit 0, no output).

## Verdict

**READY_FOR_OWNER_APPROVAL**

The corrected four-artifact package is coherent and closes the prior planning
findings. Gate 2 remains closed until the owner explicitly approves the exact
reviewed hash set; this review authorizes no test or production edit.
