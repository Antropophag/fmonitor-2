# Code rereview: OBJECT-DETAILS-EDITING-001 production-feedback hotfix

- Reviewer: independent agent `/root/reviewer_hotfix`; authored none of the reviewed specification, tests, production code, or evidence.
- Base: `3c242f34e8f30986f1b8354c4ef947a4c63936dc`.
- Exact reviewed executable source: `7d068ec356b5a417393fa047980b65924fc9c6903b195173fda8a7c5cb070012`.
- Contract: `specs/OBJECT-DETAILS-EDITING-001.md`, acceptance A16, with the matching OpenSpec hotfix scenario and tasks.
- Authorship: root authored the specification and regression tests; a separate executor authored the production view/JavaScript correction.
- Verdict: `APPROVED`.
- Exact-source CI, harness admission, publication, and deployment: `UNKNOWN`.

## Scope reviewed

The rereview covered the changed-field `FormData` filtering, server-rendered persisted baseline, failed-validation redisplay and retry, cancel/close/Escape/backdrop reset, native and `shlz-ui` custom-select state, varied unchanged legacy speed representations, and preservation of the earlier text-field save/reload/history browser flow.

The reviewed implementation compares submitted values with a separately serialized persisted/effective baseline rather than with the values currently rendered after validation failure. Unchanged legacy values are therefore omitted without special-casing any speed code, while an actually changed invalid value remains in the patch and continues through normal server validation.

## Prior-finding disposition

1. **Validation redisplay becoming the baseline — resolved.** The view now emits `data-object-details-baseline` from persisted/effective values before applying retained submitted values. After a `422`, correcting one invalid field preserves every other changed field in the atomic retry patch. An unchanged invalid submitted field cannot disappear merely because the rejected form was rendered again.

2. **Canceled edits leaking into a later save — resolved.** Cancel, close button, Escape, and backdrop all use the same reset path. It restores native controls plus the custom-select submitted value, `aria-selected` state, trigger styling, and visible label before closing.

3. **Earlier `zavnumber` browser regression removed — resolved.** The recovery browser scenario restores the `00123-А` exact patch and history assertion alongside the new A16 legacy-speed matrix.

No new specification, security, history, validation, browser-semantics, or maintainability finding was identified.

## Focused verification evidence

The root supplied a final browser-profile GREEN result for exact executable source `7d068ec356b5a417393fa047980b65924fc9c6903b195173fda8a7c5cb070012`:

```text
tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_object_details_editing_browser_001_test.php
PASS — duration 27.37s
```

That browser flow covers three distinct unchanged legacy speed values, exact material-only patches, cancel/reopen reset, a multi-field `422` correction/retry with both changes retained, and the prior `zavnumber` save/reload/history flow. The invalid attempt and cancel create no event; accepted saves create the expected events.

Independent bounded static checks on the reviewed worktree were also GREEN:

```text
node --check app/YiiRuntime/Assets/preopening.js
node --check tests/Support/object_details_editing_recovery_browser.cjs
php -l app/YiiRuntime/Views/object-card.php
php -l tests/Yii2/yii2_object_details_editing_browser_001_test.php
git diff --check
```

The harness reports the same `executable_source` as the supplied browser result. Its broader working-source identity also includes delivery metadata, so it is not substituted for the executable-source binding.

## Verdict

`APPROVED`

The A16 hotfix passes independent code/spec/test rereview for exact executable source `7d068ec356b5a417393fa047980b65924fc9c6903b195173fda8a7c5cb070012`. This approval does not convert missing exact-source CI, formal harness admission, publication, deployment, or live enforcement from `UNKNOWN` to GREEN.
