# Remove pilot work navigation item — independent planning review

- Date: `2026-09-02`
- Reviewer: separately tasked agent `/root/grill009_rbac_update`
- Scope: planning/Gate 1 readiness only; no tests, production code, owner
  approval or Done approval
- Owner decision:
  `docs/operations/pilot-work-navigation-owner-decision-remove-item-2026-09-02.md`

## Exact reviewed artifacts

```text
595831c7b13c241af87ddae93ce0c3024c5a8dc37b5d0815180c87f58eceaf21  docs/operations/pilot-work-navigation-owner-decision-remove-item-2026-09-02.md
48e95d58aaf9546c955eb22c99c899f19b8554970a3e5f251570431f32e6f6e0  openspec/changes/remove-pilot-work-navigation-item/proposal.md
f8d242f0d3c1888c20117a2d1ffaadb2e89ad9724cc808a9bbb67855005482d0  openspec/changes/remove-pilot-work-navigation-item/design.md
32100798cfae2674f8a7d32880c3cc963373f1df2bb60f3d3e81d020fef73fc1  openspec/changes/remove-pilot-work-navigation-item/specs/ui/pilot-work-navigation-item-removal/spec.md
8ca1ec0bbfb17ea7fe3ad37a94dd61566f2e2348063fcc2757c0ccb68af107de  openspec/changes/remove-pilot-work-navigation-item/tasks.md
```

The reviewer read the owner decision, all four OpenSpec artifacts, the
superseded `restore-pilot-work-navigation` proposal/tasks and its prior review.
The reviewed artifacts were not edited.

## Conformance checks

The planning intent is coherent with the owner decision:

1. Removal is limited to the shared-navigation item: exact visible/accessibility
   label and the `/pilot/` navigation destination/current marker are absent.
   Hidden, renamed and icon-only substitutes are rejected.
2. The governed configured route families cover every current
   `PilotView::document` caller family: root, object list/card/prepare/checklist,
   construction-control queue/checklist, installers and admin users/roles.
   Compatibility composition and non-success responses are explicitly outside
   the new DOM behavior.
3. Exact `/pilot/` remains a successful queue route. Queue content/filtering,
   authorization, business facts and persistence remain inherited and
   unchanged. Exact `/pilot` redirect plus inherited `401/403/404/405/503`
   status, body and application headers are preserved.
4. Other navigation items retain exact order, labels, destinations and
   current/disabled semantics. Minimal/broad actor parity, GET/HEAD behavior,
   repeat determinism and zero business/audit writes are explicit.
5. `restore-pilot-work-navigation` and its reviews are named as superseded
   historical evidence and do not confer approval on this opposite behavior.
6. Tasks preserve RED before GREEN, fresh independent test review, focused and
   full verification, architecture check and fresh independent code review.

## Blocking finding

`tasks.md` correctly leaves task 1.1 open and requires an exact executable spec
`PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001`, but no such stable executable spec
exists under `specs/`. Repository-wide lookup finds the identifier only in the
OpenSpec proposal/tasks. Under `docs/development-process.md`, OpenSpec planning
does not replace the approved executable specification. Consequently there is
no executable-spec hash to review or present for Gate 1 owner approval.

Required correction: create the stable executable specification with its
`Простыми словами` section and normative public-seam contract coherent with the
reviewed delta, then obtain a fresh independent review of the complete exact
hash set. No tests or production changes are authorized meanwhile.

## Verification

- `openspec validate remove-pilot-work-navigation-item --strict` — PASS
  (`Change 'remove-pilot-work-navigation-item' is valid`).
- Scoped `git diff --check` — PASS (exit 0, no output).
- `specs/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001.md` — absent.

## Verdict

**CHANGES_REQUIRED**

The four OpenSpec artifacts are internally coherent and accurately encode the
owner's removal decision, but Gate 1 cannot be approved until the separately
required stable executable specification exists and is included in a fresh
exact-hash review. This review authorizes no Gate 2, test or production edit.
