# INSPECTION-ITEM-COMPLETE-001 — local-auth/session integration RED v2

- Date: `2026-09-04`
- Gate: `2` correction after independent review
- Prior review: `reviews/tests/INSPECTION-ITEM-COMPLETE-001-local-auth-integration-v1.md`, `CHANGES_REQUESTED`
- Production changes: none

V1 merely removed legacy identity and never entered the callback. V2 uses the
explicit injected production factory with real native session adapters, creates
an opaque authenticated payload through `PilotSessionStorageFactory`, supplies
a valid fictional corporate local user/credential and sends the exact
`fm2auth` cookie on every public request.

The test-owned router records only post-callback facts from actual server
globals: exact actor `7301` and boolean 64-hex CSRF validity. All four requests
(page, malformed item POST, forbidden non-item POST, sync-context GET) produce
that tuple. A separate preflight count proves the local user/credential setup;
it is not an admission success oracle.

The unchanged public response expectations still fail after the callback trace
passes: `PilotHttpEntrypoint` invokes LocalAuth with a copied server array, then
constructs `PilotHttpRequest` from the stale copy. POST and sync-context see no
identity and return plaintext authentication denial instead of their approved
JSON outcomes; the UI page is likewise not admitted. Thus production alone can
make the test GREEN by propagating only callback-produced trusted values into
the request array.

```text
$ php -l tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
No syntax errors detected

$ php -l tests/Support/inspection_item_complete_local_auth_router.php
No syntax errors detected

$ php tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
PRIMARY: JsonException: Syntax error
exit 255
```

The trace cardinality/tuple assertions complete before this intended failure.
Attempt-all cleanup removes the task database, artifact root, session root and
server process. Client identity headers, legacy rows and direct environment
actor authority are absent.
