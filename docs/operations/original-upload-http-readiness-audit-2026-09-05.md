# Original-upload HTTP readiness audit — 2026-09-05

Read-only audit by `/root` at exact repository SHA
`aecd7bb73d1c8b1f52b73764c79cdb2d7289234a` (clean, integration branch
ahead of its locally recorded origin by 435 commits).

## Scope and result

The original-upload application/parser approvals do not prove pilot HTTP
readiness. The current HTTP coordinator still authorizes the registration
route with `assignment_order.confirm_registration`, and its signed-original
upload directly writes artifact/order/event/case rows and sets order status to
`registered`. The open path still checks that status. These are explicit
predecessor behavior, not evidence of the target separate upload/open contract.

`rg -n submitAssignmentOrderOriginal app/PilotHttp public rapid-pilot --glob
'*.php'` returned no matches. This proves absence of a literal reference in
those inspected PHP surfaces, not absence of every possible indirect call.
The direct SQL and registration capability in `PilotE2ECoordinator.php` are the
stronger evidence that this inspected route has not migrated to the new seam.

The active OpenSpec files inspected include
`replace-pilot-registration-with-original-upload`, but no change directories
for `expose-assignment-order-original-http`,
`apply-assignment-order-original-to-composition`, or
`open-installation-from-assignment-order-original`. These three downstream
changes are explicitly required by the pilot spec. They need their own
planning, executable contracts and independent delivery gates before a golden
path through the new command can be claimed.

## Exact inspected file identities (SHA-256)

```text
f6491662738821743976e06086bcb988269c78a4b3d87b9899df4f65575b30b0  app/PilotHttp/PilotE2ECoordinator.php
ca9da8ce99cc5e07fb6262a5c29d71c683f0947d7cb691acfbe94f9d24ede399  app/PilotHttp/PilotRouteAdmission.php
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
```

## Restrictions and next work

No production/test/spec files, protected legacy PILOT-E2E-FLOW-001, remote
branch or PR were changed by this audit. The descriptor-integrity finding
remains unresolved; a read-only delegated investigation was stopped by the
automatic security filter in this turn. That stop is not a Gate verdict and
does not establish that owner approval is technically necessary.

Safe independent work remains: prepare the downstream HTTP/composition/opening
planning packages from the approved pilot truth. Complete command Gate 5 and
the first full literal VERIFY_OK still precede Quality Graph integration and
the bootstrap CI PR. Launch readiness remains unproven.
