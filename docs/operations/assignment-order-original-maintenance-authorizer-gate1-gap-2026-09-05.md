# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — production maintenance authorizer Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved maintenance-evidence base: `a9dcc257bd13921e4332b73cef24413cc85e89c1`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

V32 makes maintenance result/audit persistence independently observable. The
real production maintenance application still has no approved authorization
source capable of returning ALLOWED:

- command identity is arbitrary string `systemPrincipalId`;
- required capability is `assignment_order.original.storage.reconcile`;
- V14/V5 `fm2_process_user_capabilities` accepts only user-oriented prepare,
  registration/open/engineer and original upload/correct values, not reconcile;
- production maintenance factory accepts only mysqli and storage/table config;
  it receives no authorizer or trusted principal/capability mapping;
- no system-principal/capability table, bootstrap grant, exact principal literal
  or environment/config field is defined.

The verification factory can inject an allowed in-memory authorizer, but it
also requires a maintenance repository and reference repository; no public
factory exposes the real MariaDB repositories separately. Therefore that path
cannot satisfy the required real persistence/audit evidence through the
production repository. A production factory implementation would have to deny
all, hard-code a principal, reinterpret a string as user ID, or invent a config
source—observably different authorization contracts.

Smallest amendment: define one exact production system-principal registry/
bootstrap grant or add trusted maintenance-authorizer configuration to the
production factory, with principal grammar, exact test principal, fail-closed
lookup and migration. Alternatively expose a verification factory that binds
the real MariaDB repositories/storage while injecting only the authorizer and
clock. Publish one authorized real-MariaDB maintenance example.

Task 4.1 remains unchecked. Existing parser/evidence partial RED remains valid;
no production, test, specification or OpenSpec artifact was edited.

```text
6196700b1812d1487eed54a1549058d241111109019f4bf2b31f0c76bd7530c8  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
5bb0bc0447ebeb700b10a21348fbd031abd870bd7f3dc4bf833b9cf80c783b52  openspec/changes/replace-pilot-registration-with-original-upload/design.md
4b1ebbbbbae2bdf0776dadd6036f05106d01965249f16ac42123b8f4d182d389  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
d062162640c64669146e43df6cbd9f74c3c9f0a5bbc428a55e10745ea902b3bc  docs/operations/assignment-order-original-maintenance-evidence-gate1-gap-2026-09-05.md
```
