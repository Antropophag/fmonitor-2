# Independent test review — current pilot demo bootstrap

Verdict: **APPROVED** for the stable bootstrap candidate.

Reviewer: `/root/bootstrap_review`, independent from the test and production
authors. Review date: 2026-09-08 Europe/Moscow. Source HEAD at final review:
`f3e62399776ad77b9de51359bea5a5292e8046b4`.

Reviewed exact test and planning inputs:

- `pilot_demo_bootstrap_001_test.php`: `49379179ddbd6a3c9d07734ca1c1f73455cf3a6a5964e36231f9d80e3e2e79de`.
- Assertion map: `8082eaf228f353ef951ef498d65ecd6a25cba3d268ff1f096fe2757c0c2f83ff`.
- Design: `8290034d8d78c10ac82f50deeaaa27de27a303d239a032010e6a367e32f08653`.
- Delta spec: `13d963014494dc21ef8ced2ad855ac1ead0b5803a4739f98fa6c60b781e79a5a`.
- Author evidence: `1b75e1f5d17e9c05574bf6c7c488218396370a3c0290c71953b1e80a4eea3709`.

The mapping explicitly supersedes the v4/eight-table, manual registration,
separate apply and legacy artifact representation. It retains their security,
history and persistence meaning through the unconditional protected current E2E
and focused child contracts. The launcher verifier independently proves the exact
canonical v19 catalogue plus three shared-prefix source tables, configured active
actor and exact roles/grants, current selection through real HTTP, restart
persistence, status rejection of an incomplete catalogue, reset isolation,
two-anchor nonce ownership, namespace collision and foreign DB/filesystem
preservation.

Expected values are tied to current public behavior: the native prepare entry
redirects exactly to selection; browser array encoding uses `installerTabIds[]`;
the trusted literal-null request reaches the exact 422 validation result; the
successful selection creates exact installer and control-engineer facts. The test
does not weaken the product parser or reintroduce old registration/opening UI.

Evidence:

- Private body PASS: `bootstrap-body-017.log`, SHA256
  `026b91a851303ff21e44b51a915c6d8c56bb28c9e3c144d7a50ab7d4bcab73b6`.
- Full original caller PASS in 144.03 seconds: `bootstrap-whole-002.log`, SHA256
  `9f7ed5164ea7d95e7280d7f4e1f781acf03fdb8e98758213b9e088d16517feaf`.
  This run included the complete mandatory child loop and protected current
  browser journey without a skip or deadline increase.

The first whole run timed out at the existing 60-second migration-runner child
deadline. Its log is retained as SHA256
`263a684755f6ad4ef4e2e36b7712c83655da9deda659c1a8c4e57f7229d5faee`.
At that point the three-day-old disposable tmpfs held 2,332 tables across 38
schemas, and an isolated runner took 90.67 seconds. After recycling only the
disposable test environment, the unchanged deadlines passed. This is environment
load evidence, not a hidden timeout increase or a product-success claim.

No blocking findings.

