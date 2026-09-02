# Restore pilot work navigation — independent planning review

- Date: `2026-09-02`
- Reviewer: separately tasked agent `/root/work_nav_planning_review`
- Scope: Gate 1 planning review only; no tests, production code or Done approval
- Verdict: `CHANGES_REQUIRED`

## Exact reviewed artifacts

```text
fdaa39285450c97ad109ee7c41eb3ca7179bf9bde76abb139a5b1e73fa661b5f  openspec/changes/restore-pilot-work-navigation/proposal.md
6277bd7c9e31dc74fec927bf393d3eee9db055e2d9227d591be995841bd0352c  openspec/changes/restore-pilot-work-navigation/design.md
4585d2a682b27bb457921645a1e9243e4d2670a4c71a67cc51dddde38164150f  openspec/changes/restore-pilot-work-navigation/specs/ui/pilot-work-navigation/spec.md
d2d9b45577cf51170c0a047d487698558c1682a16d106d8e0404a99c12db5649  openspec/changes/restore-pilot-work-navigation/tasks.md
```

Reviewed predecessor contracts:

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
b54412b14ca3d3e8ad63fc629d3dda7e5902209c52a1b2acd92dade5ba053531  specs/PILOT-UI-SHELL-001.md
```

Diagnosis evidence reviewed:

```text
fb75e5318ff4ef4407e1c54e693ae2cd642b96e4  parent of regression commit
5da1aff41263664c93ede0814971742e833bde94  regression commit
b39e87878564900264a79ca4d5fc53b39875007a4654ce145ba8090b0426546d  exact PilotView.php diff, 5da1aff^..5da1aff
```

`openspec validate restore-pilot-work-navigation --strict` is structurally
GREEN. Structural validity does not close the behavioral findings below.

## Findings

### 1. Scope and executable coverage do not identify the governed screen set

The delta requirement governs **every** successful штатный pilot HTML screen
with the shared shell. Its examples name only `/pilot/objects`, exact `/pilot/`
and an undefined “specialized screen”. The task plan asks for “non-root” and a
single focused renderer/public HTTP RED, but does not enumerate the routes or
state an explicit representative-equivalence rule by which one non-root render
proves every current shared-shell caller.

This is material because the current shared renderer serves object list/card,
prepare, checklist, construction-control, installer and admin screens with
different route permissions. An implementation can satisfy the named list/root
examples while another shared-shell screen still violates the universal SHALL,
or a test can silently narrow the SHALL to one route.

Required correction: in the delta spec and tasks, either enumerate the exact
configured shared-shell route families governed by this slice, or define the
public renderer equivalence explicitly and require root plus representative
non-root renders covering every distinct composition/caller class. Keep
compatibility composition and non-shared/error responses explicitly outside or
inside scope, rather than leaving that inferred.

### 2. Root current-state oracle is not exact enough for the promised Gate 1

The proposal/tasks promise exact count/href/current behavior, but the delta says
only “отмеченный current доступным для assistive technology способом”. It does
not fix the observable markup (`aria-current="page"`), the element kind
(non-link current item versus self-link), or whether a `/pilot/` href is allowed
on that sole current item. The scenario merely forbids a *second* destination,
so multiple incompatible implementations satisfy it. This matters because
`PILOT-UI-SHELL-001-A` fixes exactly one item with `aria-current="page"` while
the change intends to avoid duplicate/self-navigation ambiguity.

Required correction: state the exact root DOM contract, including count,
element semantics, exact `aria-current="page"`, and whether the sole current
item has an `href`. State exact non-root absence of `aria-current` on “Моя
работа”.

### 3. Error preservation is under-specified relative to the inherited seam

The error scenario lists `401`, `403`, `404`, and `503`, but the inherited HTTP
seam also has method rejection (`405`, including `Allow`) and exact `/pilot` to
`/pilot/` redirect behavior. The design says authorization/error behavior is
unchanged, yet neither the delta nor task verification makes these inherited
regression obligations explicit. “status/redaction/security contract” also
omits the exact body/header priority promised by the predecessors.

Required correction: cite the inherited route/method/error contract explicitly
and include `405` plus redirect preservation, or name the existing regression
suite that proves them and require its evidence at Gate 3. The navigation test
must not re-own RBAC semantics.

## Checks that pass

- Diagnosis is coherent: `5da1aff` replaced the approved `/pilot/` link with
  presentation-owned navigation that currently renders “Моя работа” disabled.
- The public seam is correct: successful public HTML/rendered navigation, not
  private helpers or database inspection.
- Root/non-root, minimal/broad actor parity, repeatability, zero business/audit
  mutation and error preservation are recognized; the findings request exact
  observable boundaries, not a broader feature.
- RBAC ownership is properly separated: the link is not an authorization grant,
  destination admission remains with route policy, and object-list RBAC remains
  a downstream consumer.
- The owner is the presentation boundary; no domain, persistence, migration or
  rapid-pilot ownership is introduced.
- Gate ordering is preserved: executable Gate 1 and owner approval before RED;
  independent Gate 3 before GREEN; independent Gate 5 after verification.

## Gate consequence

These exact planning hashes are **not ready for owner approval**. Revise the
three gaps, rerun strict validation, and obtain a fresh independent review over
the new exact four-artifact hash set. Gate 2 for
`PILOT-WORK-NAVIGATION-001` remains closed.
