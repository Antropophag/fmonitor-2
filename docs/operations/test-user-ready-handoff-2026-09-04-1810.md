# TEST-USER-READY handoff — 2026-09-04 18:10 MSK

Status: **NO-GO / bootstrap CI not opened / no VERIFY_OK**.

This is an append-only restart checkpoint. Repository and remote state, not
this narrative, remain authoritative after restart.

## Exact active contour

- Owning worktree: `/home/antropophag/code/fmonitor-2-remove-navigation-v2`
- Branch: `codex/remove-pilot-work-navigation-v2`
- Pre-handoff exact head: `8f531a86726d4ddfb60382c5e829069104c21a90`
- Pre-handoff branch equals origin and the working tree is clean.
- All twelve listed Git worktrees were clean at `2026-09-04T18:10:00+03:00`.
- Exactly one PR is open: draft PR #10, head
  `3ae214f75b898d171c68bb127dec10f17e03117a`, with no checks.

## Completed bounded slices

### Prepare/RBAC

- OpenSpec `pilot-prepare-rbac-fixtures`: 13/13 complete in the integrated
  navigation contour.
- Owning branch head: `9fcd960e79d8f4887ddecb11e3941a12c188d15f`.
- Independent Gate 5: **APPROVED** at
  `bfc096aa99ce01a5fac72fcbbdd227a944fe49b8`.
- Focused prepare/RBAC/auth, architecture 7/7 and browser picker contracts are
  GREEN. Repository-wide GREEN is not implied.

### Object card

- Fresh upload-first/cross-source integration Gate 5: **APPROVED**.
- Final review commit:
  `d2759ea7c74a634ce549d5cad7dff995615f01b5`.
- Permissionless active legacy users can read the card; local facts do not
  become an extra card gate; prepare action remains separately capability
  gated. Configured/compatibility shells and CSP scripts are reviewed.

### UI shell

- Fresh Gate 5 v4: **APPROVED** at
  `8b99f6696d6dbe00f9d76c96146948262159c4d6`.
- Production CSS lineage includes responsive/P0 corrections and exact browser
  evidence: canonical 12/12, picker 3/3, configured consumers 8/8, 200% text
  scaling, tablet/mobile non-overlap, identity/queue spacing and footer
  containment.
- Actual CSS ownership verifier is GREEN; no `.shlz-*` selector ownership,
  copied `--shlz-*` definitions or `!important` remain.

### Navigation removal behavior

- Gate 3 v6: **APPROVED** at
  `ff55373594794b03a96480321d6bf581ec73beae`.
- Production removal commit:
  `1cb26a2b321643597dff0f7f6593f86f2871222f`.
- Exhaustive ten-state navigation, card, prepare and UI-shell focused tests are
  GREEN; configured `Моя работа` item is absent while `/pilot/` content/route
  and compatibility renderers remain.
- Gate 5 remains **CHANGES_REQUESTED** at
  `dc91b50badba0959df1c6ab7fc5c6fcac5484625` for two delivery blockers:
  missing explicit owner approval of reviewed v2 exact hashes and absent
  repository-wide `VERIFY_OK`.

Owner approval still needed for this exact unchanged v2 Gate 1 batch:

```text
ffb72c0602a26e24aa86f7df339bcc209f6b0ce894f8a41988527c62e9db8c65  specs/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001.md
44724732faad0fa0aae318ee64df41a53b496b1231b1997aa1f3a793903c4230  openspec/changes/remove-pilot-work-navigation-item/proposal.md
6dd91e84e023b21f82ff5884ca181e228c7e6b43f006ceec4b9490926e7d11b1  openspec/changes/remove-pilot-work-navigation-item/design.md
888bfabec7f079c9a5bc21ebf1093cded10c08dde131e6169fd9f37b24225504  openspec/changes/remove-pilot-work-navigation-item/specs/ui/pilot-work-navigation-item-removal/spec.md
```

## Active object-list Gate

- Change `pilot-object-read-rbac-fixtures`: 4/9.
- Latest independently approved Gate 3 v11 review/head:
  `8f531a86726d4ddfb60382c5e829069104c21a90`.
- Reviewed test head:
  `513d00debbf547e74bb35be9656627405fc913e0`.
- Controlling result: local actor ID/active local role/exact `objects.read` owns
  admission and representation identity. `REMOTE_USER` and legacy rows do not
  supply positive authority or display identity. Missing local actor with a
  legacy decoy remains 401.
- Earliest approved RED before the last experiment: healthy local RBAC with
  missing configured CSS returns 200 instead of required 503 + Retry-After 60.
- Downstream RED: query `?origin=migration` changes the list; every query must
  be byte-equivalent and ignored. Exact ceiling is 500; 501 is fail-closed 503.

The last production experiment implemented CSS validation/query ignore/500
ceiling and made the complete object-list verifier GREEN, but exposed a stale
predecessor fixture: `local_rbac_objects_route_admission_001_test.php` uses
`rapid-pilot/pilot.css` as `FMONITOR_SHLZ_CSS_PATH` for its positive real
handler invocation. Its basename is not `shlz.css`, so honest CSS validation
returns 503 instead of its expected 200. The entire production experiment was
restored byte-for-byte from HEAD with `apply_patch`; current worktree is clean.

**Next safe action after restart:** return that predecessor test to Gate 2.
Give its positive handler case a valid task-owned/public `shlz.css` descriptor;
keep auth-only denial cases downstream-inaccessible. Demonstrate RED/fixture
sensitivity, obtain a fresh independent Gate 3, then reapply the already
demonstrated minimal object-list production changes and finish Gate 4/5.

## Latest full verification

Latest navigation full run on `af9f38cdce20b1ef9bfe893fc3c0980dc266dc61`:

```text
FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test
```

Navigation/Prepare/Card/UI-shell/TCPDF/artifact-store/production-composition
were GREEN in that run. Remaining classes:

- checklist/session sequential integration and UserAccess 503/payload failures;
- object-list/local-auth successors described above;
- blocked legacy `PILOT-E2E-FLOW-001` with obsolete manual registration and
  pre-upload-first expectations; do not silently repair without approved
  executable amendment;
- rapid auth-hot-path and rapid visual-adapter drift.

Literal `VERIFY_OK` is absent.

## Remaining release sequence

1. Complete object-list Gate 2/3 correction, minimal GREEN and fresh Gate 5.
2. Record navigation v2 owner exact-hash approval if the owner confirms it;
   final navigation Gate 5 still waits for repository `VERIFY_OK`.
3. Close uppercase/local-auth and checklist/session sequential REDs.
4. Finish original-upload totality/replay and application-identity findings,
   full verification and fresh Gate 5.
5. Amend the blocked target E2E only through owner-approved Gate 1; remove
   obsolete manual registration in favor of original upload/opening.
6. Reach first literal repository-wide `VERIFY_OK` in a hermetic contour.
7. Finish Quality Graph real receipt, representative-PR parity and phase-B
   publisher proof; obtain fresh Gate 5.
8. Open one bootstrap CI PR, keep it draft until GitHub Actions is GREEN on the
   exact SHA. Do not merge PRs.
9. After workflow reaches main, validate PR #10 checks, then integrate session
   architecture/consumers in order.
10. Finish approved fictional TEST-USER seed and Compose restart/golden-path
    proof without real credentials.

## Preserved branch heads

```text
integration                   c2e729d582c0b665936fdf7fdf74b6662275cfbd
object-list original          3b93f650fc6910b69848798ede036e8dc097908a
original upload/handoff       32096d6791a82cf745e00a4c1088576ed99c8bd2
prepare                       9fcd960e79d8f4887ddecb11e3941a12c188d15f
quality graph                 f07548135fe930e7a8fb9bb97271c9f05a8ebfc1
navigation old                3878bbded92e89d92ecf4fc603a050b40b0c71e2
session architecture          03d3720ed79d27308745987ad9e5b639e72f4c75
session consumers             1ec876e7fbb12ee1738da178974ab8f0b55e87a0
session route admission       3ae214f75b898d171c68bb127dec10f17e03117a
```

Do not delete worktrees, rewrite append-only evidence, reuse old approvals for
changed integration bytes, merge PR #10 or open bootstrap CI before Quality
Graph Gate 5 and repository-wide GREEN.
