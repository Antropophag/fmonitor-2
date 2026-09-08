# INSPECTION-ITEM-COMPLETE-001 — independent local-auth integration Gate 3 rereview v2

Date: 2026-09-04  
Reviewer: `/root/inspection_local_auth_gate3` (independently tasked; did not
author the reviewed test, router, evidence, specification or production)  
Reviewed commit: `c72440b431a2479535890997a0109ab9a3519561`  
Verdict: `APPROVED`

## Exact reviewed artifacts

- Approved executable spec `specs/INSPECTION-ITEM-COMPLETE-001.md`: SHA-256
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Cross-cutting local RBAC contract
  `specs/LOCAL-RBAC-AUTH-CONTRACT-001.md`: SHA-256
  `f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392`.
- Raw endpoint/UI verifier: SHA-256
  `e0d9f2204a4df01bd44bb2a5af60827e6f6a4a4ad9b4b4bf97eb33cfaf71e2fb`.
- Injected production-factory router: SHA-256
  `8936f7bb20f188b3f6219ad1a73d1292b9de099eb76ac0fec76102911867e59b`.
- RED evidence v2: SHA-256
  `9f55ad3070265a1141e369227d0f640cdb0a51bdef36f9e81a670598e217e6cb`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.
- Prior independent v1 review: commit
  `bbb0337aaadc43b23b7f67f8cc036071d0d03a00`, verdict
  `CHANGES_REQUESTED`.

No production or reviewed test file was edited by this review.

## V1 finding closure

`LAI-01` is closed. The verifier no longer relies on the ordinary production
factory path that omitted LocalAuth. Its test router constructs the same
production entrypoint through the explicit injected-dependency factory, which
binds one `LazyPilotSessionStorage` owner and passes the corresponding
`RapidPilotLocalAuth` callback to `PilotHttpEntrypoint`.

Before the server starts, the test creates an opaque session ID using the real
`PilotSessionStorageFactory`, commits a canonical payload containing actor
`7301`, fictional `inspection.engineer@shlz.ru`, and a 64-hex CSRF token, then
sends only the opaque `fm2auth` cookie. The canonical local user is active and
activated, its credential row has the same fictional corporate email and a
non-null password hash, and `MariaDbLocalAuthRepository::activeUser` rechecks
that complete tuple on every callback. The actor's assigned active role grants
only exact `inspection.item.complete`; the assigned control engineer remains a
different user.

There is no `REMOTE_USER`, `FMONITOR_AUTH_USER_ID` or
`FMONITOR_AUTH_CSRF` in the child environment and no client identity header.
Legacy user/role tables are empty decoys and supply no positive authority.
Thus the only source of the post-callback actor and CSRF values is the real
owner-backed LocalAuth path.

## Independent RED reproduction

The existing Compose MariaDB was healthy. Both syntax checks passed:

```text
php -l tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
No syntax errors detected

php -l tests/Support/inspection_item_complete_local_auth_router.php
No syntax errors detected
```

Ordinary public HTTP RED:

```text
tools/verification/run.sh red \
  tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
PRIMARY: JsonException: Syntax error
RED_ASSERTION: expected failing behavior observed
```

Before the JSON decode at verifier line 344, line 343 read the completed trace,
required exactly four callback records, and required every decoded record to be
exactly `{"actor":"7301","csrfValid":true}`. Therefore page, malformed item
POST, forbidden non-item POST, and sync-context GET all completed LocalAuth and
produced trusted actor/CSRF globals before the intended response failure. The
subsequent stale request-array copy still produced plaintext authentication
denials for JSON endpoints, so JSON decoding is the first ordinary assertion
failure.

UI/client public RED also reproduced:

```text
FMONITOR_TEST_ITEM_UI_CONTRACT=1 tools/verification/run.sh red \
  tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
PRIMARY: TestFailure: Item-only UI/client contract:
- public checklist page is HTTP 200
- root exposes item-only completion capability
- all 42 item completion controls are usable
...
RED_ASSERTION: expected failing behavior observed
```

The UI mode intentionally evaluates the unchanged page contract immediately
after the page response, so it stops before the later four-request aggregate.
The router appends its post-callback tuple before emitting that response; the
ordinary run independently proves the exact tuple for the same page request as
well as all three downstream requests. The UI failure is therefore the same
stale-copy admission defect, not an absent callback or broken fixture.

Both runs completed attempt-all cleanup. Post-run probes found zero `t_iea_%`
schemas, no owned `iea-*` artifact/session root, and no owned PHP router.

## Sensitivity, security and scope

- If the callback is absent or skipped, the router trace contains null actor and
  invalid CSRF; the exact trace assertion fails before JSON decoding. This
  prevents the v1 false-positive from recurring.
- Direct environment bypass cannot satisfy the test because no actor, CSRF or
  legacy principal is supplied there. The only client credential is an opaque
  owner-created session cookie. The trace observes values after the real
  LocalAuth callback, and the credential join makes the fictional session
  identity insufficient without matching current local user/credential facts.
- The copied `$server` passed into `PilotHttpEntrypoint::handle` remains stale
  while the callback changes only trusted PHP globals. Propagating the three
  callback-produced values into that same request array is sufficient to turn
  the intended RED green; accepting request headers/environment identity,
  adding a second session owner, or bypassing LocalAuth is neither necessary nor
  admitted.
- Existing public expectations are unchanged: item-only UI availability,
  malformed item exact 422 JSON, non-item exact 403, sync-context 200/revision
  zero, exact schema/rows/artifact immutability, capability independent of
  assignment, and bounded verdict-sensitive cleanup.

## Gate decision

The corrected test reaches the intended production LocalAuth/session seam,
proves callback-produced identity before the stale-copy failure, is sensitive
to callback omission, and admits no client, environment or legacy positive
authority. The RED is deterministic and cleanup-isolated. Gate 3 v2 is
`APPROVED` for the exact hashes above. Gate 4 may implement only the minimal
trusted callback-to-request handoff without changing these tests.
