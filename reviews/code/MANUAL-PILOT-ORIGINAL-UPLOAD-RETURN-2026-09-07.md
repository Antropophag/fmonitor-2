# Manual-pilot original-upload return to card — independent review

- Reviewer: Codex agent `/root/auth_review`, independently tasked; did not author the production or test changes.
- Review date: `2026-09-07` (`Europe/Moscow`).
- Review base / current `HEAD`: `c02f1a23121058fb0046e4b8fec859c5754ecb6d`.
- Production scope: `app/PilotHttp/OriginalUploadView.php`.
- Test scope: `tests/InstallationProcess/original_upload_return_to_card_manual_test.php`.
- Test verdict: `APPROVED`.
- Code verdict: `APPROVED`.

## Exact reviewed identities

```text
60275629260d8ad1ce4ea13f22c09b3bcc19dc722bcde0d1a5e1dac179fc3a45  app/PilotHttp/OriginalUploadView.php
6cfd1feec1ad8fdfaa778fd955ed76d671e4fda6f62e415d2e9c732b57067b8ce  tests/InstallationProcess/original_upload_return_to_card_manual_test.php
608f04b99bd89ad531b248ed7c37e62b7aaff529efe066585c335115961f5834  OriginalUploadView.php.diff
dfa63227837812b014ff1093e5085992388ffba805aecb01de2f816f67f8d963  original_upload_return_to_card_manual_test.php new-file diff
```

## Findings

No blocking findings.

The view changes only the post-success navigation contract consumed by the
unchanged `original-upload.js`: `data-return-url` now points to the object card.
The form action remains the original upload/correction command endpoint, so file
validation, metadata, CSRF, replay, retry and accepted-result checks are unchanged.
The client still navigates only after the exact accepted/replayed response envelope.

The helper area now explains that the card opens after upload and offers one
nonempty accessible link to that card. It no longer directs an uploader into the
separate execution/application/opening flow. This matches the role/action
separation without hiding or invoking those actions elsewhere. No JavaScript,
authorization, command, persistence, original history, or application semantics
changed.

The focused test exercises both initial upload and correction rendering. It pins
the card return URL, unchanged original command endpoint, retained CSRF, absence
of an execution link, and exactly one helper card link with a nonempty accessible
label. Exact prose is appropriately left as presentation copy. Existing client
verification independently retains the accepted/replayed-only navigation and
4xx/new-intent versus retry behavior.

## Independent verification

```text
php tests/InstallationProcess/original_upload_return_to_card_manual_test.php
PASS original upload and correction return to object card

node tests/Verification/original_upload_client_001_test.mjs
ORIGINAL_UPLOAD_CLIENT_OK canonical metadata/File/in-flight/retry/new intent/success
ORIGINAL_UPLOAD_CLIENT_CORRECTION_OK replay/4xx/retry

PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/AssignmentOrderComposition/original_upload_http_prefill_001_test.php
PASS native yesterday template prefill/manager/form asset contract

php -l app/PilotHttp/OriginalUploadView.php
php -l tests/InstallationProcess/original_upload_return_to_card_manual_test.php
node --check app/PilotHttp/original-upload.js
git diff --check -- reviewed files
PASS

php rapid-pilot/verify-visual-contract.php
php rapid-pilot/verify-focus-contract.php
PASS

/Users/antropophag/.agents/skills/impeccable/scripts/impeccable detect --json app/PilotHttp/OriginalUploadView.php
[]
```

The review performed no code/test edits, upload, deployment, stand/data mutation,
remote action, or Bitrix action. The final browser POST/redirect proof remains a
separate pre-deployment check. This verdict is bounded to the exact artifacts above
and does not claim full `VERIFY_OK` or production readiness.
