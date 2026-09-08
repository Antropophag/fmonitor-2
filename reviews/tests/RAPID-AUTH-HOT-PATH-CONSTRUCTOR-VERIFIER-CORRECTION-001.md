# Test review: RAPID-AUTH-HOT-PATH-CONSTRUCTOR-VERIFIER-CORRECTION-001

- Date: `2026-09-04`
- Reviewer: independent tasked agent `auth_hot_path_gate3`
- Test author: commit author of `6e51ce395cc6847ab3a4e2d7efa3ef29899781e6`
- Reviewed commit: `6e51ce395cc6847ab3a4e2d7efa3ef29899781e6`
- Specification and evidence: `specs/IDENTITY-ACCESS-SCHEMA-001.md` v0.1,
  `docs/operations/identity-access-schema-evidence.md`, and
  `docs/operations/rapid-auth-hot-path-constructor-verifier-correction-2026-09-04.md`
- Public seam: `php rapid-pilot/verify-auth-hot-path.php`
- Verdict: `APPROVED`

## Independence and reviewed bytes

The reviewer authored neither the verifier correction nor `RapidPilotLocalAuth`
and received this narrow Gate 3 task separately. The reviewed commit changes
only the verifier locator from the obsolete exact zero-argument signature to
the declaration prefix `public function __construct(`, plus its append-only
Gate 2 evidence.

Exact Git blob evidence:

- `rapid-pilot/verify-auth-hot-path.php`: predecessor
  `129e888a854f68985b84f3224b5343ed64995b09`, reviewed
  `c7b3fc6ea3c048409131d0fec669d23f60c6a0cd`;
- `rapid-pilot/LocalAuth.php`: predecessor and reviewed
  `e8039a8cae2b5ddb0f9da5621a0f72c59257a8fd`.

Thus the reviewed commit does not change production `LocalAuth`. Its working
copy also had SHA-256
`746f5167f3d7e1ae51cc140bc75a7cdf470c316aafbcf4cf09a41b52d4302ca1`
before and after the independent probes.

## Findings

Traceability and intended failure are sound. Current `RapidPilotLocalAuth`
declares `public function __construct(?FMonitor\IdentityAccess\PilotSessionStorage
$storage=null)`. Replaying the predecessor locator against those current bytes
fails with `LocalAuth constructor unavailable` and exit `255`, before any
security assertion can inspect the constructor. Matching the opening
parenthesis recognizes that optional canonical session-owner dependency without
depending on its type, variable name, or default.

The correction does not narrow the inspected source slice. `constructorEnd`
is still the following literal `public function handle(`, and `substr` still
covers every byte from the constructor declaration through the complete
constructor body and intervening source. The three existing negative checks
for `CREATE TABLE`, `INSERT INTO`, and `ensureSchema` are byte-identical, as is
the whole-file rejection of `private function ensureSchema`. All bootstrap,
credential, invitation, password, worker, upload-limit and queue assertions
remain byte-identical.

The verifier is deterministic and read-only in its repository execution. The
sensitivity probes used an isolated `mktemp` mirror outside the repository;
they changed comments only in the copied `LocalAuth.php`, never production or
verifier bytes in the worktree.

## Independent execution evidence

Baseline:

```text
$ php -l rapid-pilot/verify-auth-hot-path.php
No syntax errors detected in rapid-pilot/verify-auth-hot-path.php

$ php rapid-pilot/verify-auth-hot-path.php
PASS auth hot path is schema-mutation free
exit 0
```

Predecessor sensitivity against the current optional constructor:

```text
OLD_LOCATOR_STATUS=255
RuntimeException: LocalAuth constructor unavailable
```

Independent copied-source mutations were inserted immediately inside the
constructor body. Each was rejected by the intended unchanged oracle:

```text
MUTATION=CREATE TABLE STATUS=255
RuntimeException: request-time auth constructor contains DDL

MUTATION=INSERT INTO STATUS=255
RuntimeException: request-time auth constructor contains bulk synchronization

MUTATION=ensureSchema STATUS=255
RuntimeException: request-time auth constructor invokes schema bootstrap
```

Repository status was clean after the probes, and the production SHA-256 was
unchanged.

## Gate decision

`APPROVED`. The one-byte-suffix relaxation is the minimal correction needed to
follow the current optional canonical session-owner constructor. It restores
the verifier's reachability while preserving the complete constructor slice and
all schema-mutation detectors. No test expectation, production behavior, or
security invariant is weakened.

## Required changes

None.
