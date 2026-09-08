# PILOT-OBJECT-CARD-001 route-CSP correction GREEN v1

Date: 2026-09-04  
Gate 3 review: `reviews/tests/PILOT-OBJECT-CARD-001-csp-correction-v4.md`,
**APPROVED**  
Base after approved Prepare/RBAC predecessor merge: navigation worktree merge
commit immediately preceding this record  
Verdict: **focused CSP predecessor GREEN; card content still RED**

The minimal production change appends one source-only external script to the
configured object-card document, after the shared navigation script:

```text
/pilot/assets/navigation.js
/pilot/assets/object-details.js
```

No test, authorization, persistence, domain or rapid-pilot file changed.

Canonical run with the repository test MariaDB reached beyond the formerly
intended script assertion. The exact previous failure `Expected: 2 / Actual: 1`
is gone. Execution now stops later at the independently owned card presentation
contract:

```text
Example A broad reader without capability visible literal/order: 77-000123
Expected: true
Actual: false
```

This record does not claim the full object-card verifier GREEN or Gate 5. The
card presentation and upload-first successor assertions must be reconciled
through their own Gate 2/3 evidence before further production changes.
