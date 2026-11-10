# Поиск стройконтроля и требования пароля —29.10.2026

Owner authorizes continued short code → independent review → production cycle.
Navigation checkout stays read-only. This isolated checkout changes only the named
files; earlier OTIZ production work and other sessions must be preserved by exact
per-file pre-deploy hash checks. No full local make test/verify or deployment-wide CI
is required by this owner exception; never claim these passed.

## CONTROL-SEARCH-FOCUS-20261029

Declared bounded correction of existing server-filtered search, root authors test+fix.
Actual production control-queue.js SHA256683009ae39ebeaa0efb4f7bd0c2a590b016dc9e98b35fabe4a02557ab5107722.
RED reproduced with real browser GET navigation: first character submits after350ms,
then input is enabled but loses focus and selection (document.activeElement=body).
No further hypothesis loop is needed: the same red-capable command proves the cause.

- After typing, server filtering/navigation remain, while focus, caret and selected
  range restore only for that search, same route/query/user, within30seconds.
- Empty input is never disabled; omit empty query through FormData rather than disabling.
- Ownership/completed/reset controls remain functional and must not steal focus to search.
- IME composition must not submit a partial string. Filter change/manual submit cancels
  the pending debounce to avoid duplicate submits.
- Preserve pagination reset, scroll and all offline sync/storage code unchanged.
- Version the separate script URL with existing AssetVersion so cached old JS is replaced.
- Public regression: tests/Yii2/yii2_control_search_focus_20261029_test.php; independent
  review before production. Runtime scope: asset and construction-control view only.

## ACTIVATION-PASSWORD-20261029

Owner explicitly selected: minimum10characters and four groups for all FMonitor users.
Primary evidence: https://pw.shlz.ru page generator, captured outside Git. It generates
10ordinary/15shlz-a characters and includes lower/upper/digit/special. Owner selected
10uniformly, not the shlz-a branch. This is observed client generator behavior, not
claimed access to that site's server-side validation.

- New password during invitation activation:10–200UTF-8 characters, at least one ASCII
  lowercase a-z, uppercase A-Z, digit0-9 and ASCII punctuation/special character.
  Whitespace and Cyrillic letters alone are not the special group. Other characters
  may occur; do not silently trim/change password. Invalid UTF-8 rejected safely.
- Confirmation match and existing case-insensitive email-local-part exclusion remain.
- UI minlength10, maxlength200, clear visible four-group help and accessible linkage;
  server validates independently. Both password inputs have consistent requirements.
- Existing login passwords remain valid; no password resets, rehash/migration or changes
  to invitation permissions, expiry, single-use, session or hashing rules.
- Invalid password consumes no token and writes no identity/credential facts. Valid10
  activation stores Argon2id, permits normal login and cannot reuse invitation.
- Root supplies tests; separate sol/low executor implements; independent final review.
