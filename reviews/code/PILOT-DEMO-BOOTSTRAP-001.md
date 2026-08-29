# Code review: PILOT-DEMO-BOOTSTRAP-001 v0.1

- Gate: 5 — fresh independent review for literal `Origin: null` in the trusted demo
- Reviewer: separately tasked agent `/root/bootstrap_null_origin_code_review`
- Independence: reviewer authored neither specification, approved test, nor implementation
- Implementation author: commit author `antropophag`
- Specification commit: `71e5e50`
- Approved test commit: `f614fa7`
- Approved test review: `c8a0609`
- Exact reviewed HEAD: `e6e309f`
- Review date: 2026-08-29
- Verdict: `APPROVED`

## Findings

### Standards

None. The three-line production change is the minimal correction authorized by the approved test review. It computes the existing `trustedDemo()` predicate once and gives explicit names to the expected Origin and the Origin decision. It introduces no documented-standard violation or actionable Fowler smell.

The existing trust predicate remains defense in depth at the HTTP adapter boundary: it requires the PHP loopback server SAPI, a server-injected 32-hex nonce, a canonical configured `127.0.0.1:1024..65535` Host, and exact equality with the validated request Host. Actor/session equality and Fetch Metadata checks remain in the same request-validation expression. Session, CSRF, body, revision, authorization, process commands, append-only history, and integration boundaries are untouched.

### Specification

None. Literal string `Origin: null` is newly accepted only by `($trustedDemo && $origin === 'null')`. An absent Origin and an exact scheme/Host Origin retain their prior behavior. Ordinary production still rejects literal `null`, including when its environment contains `FMONITOR_TRUSTED_REQUEST_HOST`; a valid but noncanonical loopback Host on the real demo listener also rejects literal `null` and its matching attacker-selected HTTP Origin.

The approved public-seam regression proves the narrow exception with the canonical demo Host, issued session cookie, fresh CSRF token, same-origin Fetch Metadata, and validation PRG. It also proves zero mutation after rejected and intentionally incomplete requests, then completes the full production browser journey. No requirement is missing or partial, and no scope creep was found.

## Required changes

None.

## Verification

```text
$ php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
PASS PILOT-DEMO-BOOTSTRAP-001 public launch, walkthrough, persistence, reset and cleanup

$ for test_file in tests/InstallationProcess/*_test.php; do php "$test_file" || exit 1; done
48/48 test files PASS

$ find app public bin tests/InstallationProcess tests/Support -type f -name '*.php' -print0 | xargs -0 -n1 php -l
148/148 PHP files lint PASS

$ git diff --check f614fa7..e6e309f
PASS

$ ps -eo pid=,args= | rg 'php -S 127\\.0\\.0\\.1|fmonitor2-pilot-demo\\.php'
No demo/router process residue

$ find .test-artifacts -mindepth 1 -maxdepth 3 -print
No test artifact residue
```

Gate 5 is `APPROVED` for exact reviewed commit `e6e309f`.
