# Technical approval — assignment-order original database setup v10

Date: `2026-09-04`

Decision: **APPROVED for setup Gate 2**.

The owner directed autonomous technical decisions that do not change product
meaning, workflow, roles, authority, scope or approved public product contracts.
The v10 amendment only makes the already required isolated MariaDB verification
and additive migration executable. It introduces no HTTP route, product command
outcome, role/capability decision, composition application or opening behavior.

Approved reviewed commit:
`7192aa7cb177b1c7612086f7628c6ff82bd60a4c`.

```text
62b42d5b957dd628d09c13d6864152401c54998a5607b1c1835cc8a93ab9c3dd  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
0208895b4a605381ece9cc0bba4cee49ac79c1b17ffa1939f62601c05144051f  openspec/changes/replace-pilot-registration-with-original-upload/design.md
2010196d65210243c6bae5bb016db90caf35d23766b8655aaa9917fd72705979  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
a061abc535528436d3caaadd0f34e7618793fd3ae76f5e7ccb16fd40fbbf43b5  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
```

Fresh independent Gate 1 authority is commit
`9144a2cb7f56928c2f70d0d790578709755d13a8`, verdict `APPROVED`:

```text
d821da023aeeb6228928e8c2dfec446a23850102abe6938f9f07f4f808a6b487  docs/operations/pilot-assignment-order-original-database-setup-gate1-rereview-v10-2026-09-04.md
```

This approval permits only setup RED task 2.2 and its independent Gate 3. It
does not approve production implementation, command-matrix tests, Gate 5,
integration readiness or the blocked legacy `PILOT-E2E-FLOW-001` amendment.
