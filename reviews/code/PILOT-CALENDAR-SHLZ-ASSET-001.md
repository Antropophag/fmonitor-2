# Code review: PILOT-CALENDAR-SHLZ-ASSET-001

- Reviewer: separately tasked Codex agent `/root/calendar_asset_code_review` (independent; did not author the implementation)
- Implementation author: separately tasked Gate 4 implementation agent
- Reviewed commit: working-tree `Dockerfile` diff against HEAD `db8e4d888abce28daa82d75135f6d6c5f5d63874`; reviewed `Dockerfile` SHA-256 `6489de91f81d26d5be615e597d6d7503a4edc580b7e726a29327325f96ba8702`
- Specification: [`specs/PILOT-CALENDAR-SHLZ-ASSET-001.md`](../../specs/PILOT-CALENDAR-SHLZ-ASSET-001.md), `APPROVED 2026-08-30`
- Approved test review: [`reviews/tests/PILOT-CALENDAR-SHLZ-ASSET-001.md`](../tests/PILOT-CALENDAR-SHLZ-ASSET-001.md), verdict `APPROVED`
- Verification commands: `php tests/InstallationProcess/pilot_calendar_shlz_asset_001_test.php` — PASS after a real Docker `shlz-ui-build` target build and artifact probe; `git diff --check -- Dockerfile` — PASS
- Verdict: `APPROVED`

## Findings

None.

### Standards

- The reviewed production change is limited to replacing the `SHLZ_UI_REVISION` value with the specification's full 40-hex commit identity. It introduces no unrelated refactor, duplication, abstraction, or maintainability smell.
- Dependency integrity remains fail-closed: the build checks out the exact detached revision and verifies `rev-parse HEAD` equality before the build stage consumes the tree. `npm ci` continues to enforce the dependency lock, and the generated artifact tree—not locally copied component source or styles—is passed to the runtime stage.
- The full immutable commit pin avoids mutable branch/tag resolution. No credential, permission, executable, runtime-user, or network-exposure behavior changes in this slice.

### Spec

- The one-line implementation exactly changes the stale `shlz-ui` pin to `a0a8ca6df60b84aa1fe10a1cb500de32dacd4516`, while preserving the existing verified checkout, package generation/build, and `COPY --from=shlz-ui-build /shlz-ui /workspace/shlz-ui` runtime boundary.
- The approved focused test is regression-sensitive to plausible failures in this slice: a stale or different pin, removal/bypass of checkout verification, a missing public `calendar-grid.js` export, missing generated Calendar Grid styles, a missing package export, or a changed runtime artifact-copy source.
- The real Docker target build and in-image probe passed. The test's specification and test hashes remain identical to the hashes approved at Gate 3, so Gate 5 did not rely on changed expectations.
- Process authorization, audit/history, and append-only domain invariants are not applicable to this dependency-artifact-only change and are unaffected.

## Required changes

None.

Gate 5 is approved for the reviewed working-tree `Dockerfile` bytes at HEAD `db8e4d888abce28daa82d75135f6d6c5f5d63874`. Approval does not cover the unrelated working-tree changes.
