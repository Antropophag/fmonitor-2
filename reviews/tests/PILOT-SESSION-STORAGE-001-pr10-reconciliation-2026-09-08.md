# Independent Gate 3 test review — PILOT-SESSION-STORAGE-001 PR10 reconciliation

**Verdict: APPROVED**

## Scope and reviewed artifact

- Review date: 2026-09-08
- Reviewed worktree HEAD: `3ae214f75b898d171c68bb127dec10f17e03117a`
- Independence: the reviewer did not author the reconciled test or production
  implementation.
- Scope: only the regression added to
  `tests/InstallationProcess/pilot_session_storage_protocol_001_test.php` for
  `/pilot/objects/1/checklist/operations` and
  `/pilot/construction-control/objects/1/checklist/operations`.
- Production remained equal to `origin/main` during RED execution; this review
  does not approve a production implementation or any unrelated PR10 changes.

## Exact reviewed hash

```text
9490cfaa06551d8c74720aa494e106166db6efdb1132eda06a626f3cf1e241ce  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
```

The governing specification observed during review was:

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
```

## Findings

No blocking findings.

The regression exercises both real public raw-HTTP routes with fixed JSON bytes
and deliberately unavailable session storage. PILOT-SESSION-STORAGE-001 section
7 allows only pure known-route recognition before authentication/session
admission. It therefore requires each known login-required command route to
reach the session failure before body handling. The expected exact status/body
pair, `[503, "Service unavailable.\n"]`, comes from the specification rather
than current production output.

The test is sensitive to the reconciliation defect. Current production invokes
the completion handler during route recognition and returns status `303` with
the completion rejection JSON. A fix that recognizes only the first route, or
that merely changes the final status after command execution, cannot satisfy
both loop iterations and the exact-body assertions. The added absence checks
for `Set-Cookie` and `Location` also cover the section 7 prohibition on session
or redirect effects in this failure path.

The seam and scope are appropriate: loopback raw HTTP covers routing,
authentication/session priority, response status, body, and relevant headers
without depending on a private predicate or planned implementation name. Fixed
paths, request bytes, and the verifier-owned server make the case deterministic
and isolated. Existing controls in the same tracer reject malformed Host/URI
and unknown routes before this assertion, so a blanket early failure cannot
make the complete test pass. No expanded rare-case matrix is needed for this
reconciliation.

## Fresh independent RED evidence

Syntax and whitespace checks:

```text
$ php -l tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_protocol_001_test.php

$ git diff --check -- tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
exit 0
```

Normal route order:

```text
$ php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
Fatal error: Uncaught TestFailure: /pilot/objects/1/checklist/operations admission reaches authentication before body handling
Expected: array (
  0 => 503,
  1 => 'Service unavailable.
',
)
Actual: array (
  0 => 303,
  1 => '{"status":"rejected","message":"Последние 15% закрываются актом ПТО и декларацией в карточке объекта."}',
)
exit 255
```

Because the first failure stops the PHP tracer, the reviewer also executed the
same file in memory with only the two-element route iteration order reversed;
no repository file or index was changed:

```text
Fatal error: Uncaught TestFailure: /pilot/construction-control/objects/1/checklist/operations admission reaches authentication before body handling
Expected: array (
  0 => 503,
  1 => 'Service unavailable.
',
)
Actual: array (
  0 => 303,
  1 => '{"status":"rejected","message":"Последние 15% закрываются актом ПТО и декларацией в карточке объекта."}',
)
exit 255
```

Both failures occur at the new public-response assertion after prior setup and
route-priority controls pass. Post-run inspection found no matching PHP
loopback server and no fresh `fmonitor2-session-http-*` directory.

## Gate consequence

Gate 3 is **APPROVED** for the exact test hash above. Minimal implementation may
make recognition of both checklist command paths pure while preserving the
existing section 7 ordering and exact storage-unavailable response. Any change
to the reviewed test expectation or seam requires fresh independent test
review; production still requires focused GREEN evidence and independent Gate
5 review.

## Superseding Gate 3 addendum — corrected authentication oracle

**Verdict: APPROVED**

The approval above used a stale `503` expectation for the no-cookie POST. The
current authentication contract deliberately rejects that request without
starting a session: exact `303`, empty body, `Location: /pilot/login`, and no
`Set-Cookie`. This addendum supersedes the earlier verdict and approves the
corrected narrow regression only; the historical error remains recorded above.

```text
0d91453a7bff6c8f4714cc8328cd50367ec3d158e2e9798501048214bf976010  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
```

For each of the two checklist URLs, the corrected test first proves that the
no-cookie POST redirects without business output or session creation. It then
sends a syntactically valid route-specific cookie,
`fm2auth_<decimal-port>=<64 lowercase a>`, so authentication must inspect the
present-empty session root. That case independently retains the specified exact
`[503, "Service unavailable.\n"]` response and forbids both `Set-Cookie` and
`Location`. The split oracle matches the existing LocalAuth behavior while
keeping the intended invariant: route recognition must emit no checklist
business response before authentication/session admission.

Fresh production-baseline RED is sensitive in both branches. The ordinary run
fails the first route's no-cookie assertion with exact status/location `303`
but unwanted completion-rejection JSON in the body. In-memory review probes
that allowed this known bad anonymous body only to reach the cookie assertion
then observed status `503` with the same unwanted JSON prepended to
`Service unavailable.\n`; reversing only route iteration reproduced that
cookie-bearing failure for the construction-control URL. No repository file or
index was changed by those probes.

```text
$ php -l tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_protocol_001_test.php

$ php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
Expected: [303, '', ['/pilot/login']]
Actual:   [303, completion-rejection JSON, ['/pilot/login']]
exit 255

cookie probe, each URL:
Expected: [503, "Service unavailable.\n"]
Actual:   [503, completion-rejection JSON + "Service unavailable.\n"]
exit 255

$ git diff --check -- tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
exit 0
```

No blocking findings. Gate 3 is **APPROVED** only for test SHA-256
`0d91453a7bff6c8f4714cc8328cd50367ec3d158e2e9798501048214bf976010`.
