# Code review: PILOT-CALENDAR-SHLZ-ASSET-001 restoration

- Gate: 5 — fresh independent code review of the restored approved pin
- Reviewer: separately tasked Codex agent `/root/calendar_pin_code_review` (did not author the implementation or approved test)
- Review date: 2026-08-31
- Verdict: `APPROVED`
- Reviewed base: HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`
- Reviewed production artifact: working-tree `Dockerfile` SHA-256 `6489de91f81d26d5be615e597d6d7503a4edc580b7e726a29327325f96ba8702`
- Specification: [`specs/PILOT-CALENDAR-SHLZ-ASSET-001.md`](../../specs/PILOT-CALENDAR-SHLZ-ASSET-001.md), SHA-256 `57830cdd60ce3b47f9b0bd808b600618de5457ed82738b286445a39a3cf20fb5`, `APPROVED 2026-08-30`
- Approved test: [`tests/InstallationProcess/pilot_calendar_shlz_asset_001_test.php`](../../tests/InstallationProcess/pilot_calendar_shlz_asset_001_test.php), SHA-256 `e31db00fb0e2cdaa8352e3a074702357d6865d20bfd3e29c9eb22bce87face7f`
- Approved test review: [`reviews/tests/PILOT-CALENDAR-SHLZ-ASSET-001.md`](../tests/PILOT-CALENDAR-SHLZ-ASSET-001.md), verdict `APPROVED`

## Verdict

`APPROVED`. No Standards or Spec findings. The reviewed one-line restoration returns the image dependency to the exact immutable `shlz-ui` revision required by the approved specification, without changing the reviewed test or widening the slice.

## Standards

No finding.

- The production diff changes only `SHLZ_UI_REVISION` from `7ae8b5d6f42fa3143af078a8814fda2b36d80aa4` to the approved full commit identity `a0a8ca6df60b84aa1fe10a1cb500de32dacd4516`.
- The existing fail-closed detached checkout and `rev-parse HEAD` equality check remain intact. The generated `shlz-ui` artifact tree remains owned by the dependency build and copied through the established runtime boundary; no component source or style is duplicated into FMonitor.
- The diff introduces no unrelated behavior, abstraction, duplication, dependency-direction change, or actionable code smell. `make architecture-check` passes all six rules, and `git diff --check` passes.

## Spec

No finding.

- The restored pin exactly matches the revision mandated by the observable contract. Existing Docker stages still generate the public packages and copy the probed tree from `shlz-ui-build` to `/workspace/shlz-ui`.
- The independently approved test remains byte-identical to Gate 3. It is sensitive to a wrong pin, checkout-verification bypass, missing/nonempty Calendar Grid behavior artifact, missing generated Calendar Grid selector, missing public package export, and a changed runtime copy source.
- The focused test passed after a real Docker build of `shlz-ui-build` and an ephemeral in-image artifact probe. This supplies direct evidence for the public artifact seam rather than relying only on Dockerfile text.
- Authorization, audit, append-only history, and state-changing application seams are not exercised or altered by this dependency-artifact restoration.

## Verification evidence

- `php tests/InstallationProcess/pilot_calendar_shlz_asset_001_test.php` — PASS: `PILOT-CALENDAR-SHLZ-ASSET-001 passed`.
- `make architecture-check` — PASS: `ARCHITECTURE CHECK PASSED (6 rules)`.
- `git diff --check` — PASS.
- `git diff -- Dockerfile` — exactly one deletion and one insertion in the revision argument.
- Current specification and test SHA-256 values exactly match the hashes recorded by the approved Gate 3 review.

Gate 5 is approved only for the reviewed `Dockerfile` bytes (`6489de91f81d26d5be615e597d6d7503a4edc580b7e726a29327325f96ba8702`) against HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`. Unrelated working-tree changes are outside this verdict.
