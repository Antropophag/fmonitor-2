# Owner approval — LOCAL-RBAC-AUTH-CONTRACT-001

Date: 2026-09-02  
Decision: **APPROVED**  
Owner response: `ok` after plain-language explanation  
Reviewed executable SHA-256:
`f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392`

Approved scope: one common read-only local-RBAC authorization seam and first
vertical consumer `GET /pilot/objects → objects.read`. Exact active local user,
active activation, active assigned role and byte-exact permission are required;
active-role permissions form a union. Legacy/name/wildcard/authenticated-only
fallback is prohibited. Safe external `401/403/503`, correlation diagnostics
and current committed snapshot behavior are approved.

Other routes remain follow-up slices and MUST reuse the seam. Gate 2 RED is
authorized. Production changes remain prohibited until independent test review
returns `APPROVED`.
