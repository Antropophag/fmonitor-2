# Code review: PILOT-OBJECT-LIST-001 v0.1 — final UI correction

- Gate: 5 — independent code review
- Reviewer/aggregator: separately tasked agent `/root/pilot_queue_gate5_final`
- Standards reviewer: `/root/pilot_queue_gate5_final/standards_axis`
- Spec reviewer: `/root/pilot_queue_gate5_final/spec_axis`
- Implementation/test authors: separately tasked agents, not these reviewers
- Reviewed ancestry: HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`; exact dirty-tree inputs pinned below
- Specification: `specs/PILOT-OBJECT-LIST-001.md` v0.1, `APPROVED`
- Corrective test review: `reviews/tests/PILOT-OBJECT-LIST-001.md`, `APPROVED`
- Date: `2026-08-28`
- Verdict: `APPROVED`

The prior Gate 5 blocker is fixed. The collection now uses the permitted semantic-list composition and public native `a.shlz-link`, without selecting the horizontally scrolling Table wrapper. The approved corrective test detects the former bare-table regression. This review changed no code, test or specification.

## Standards

Independent verdict: `PASS` — 0 blocking findings.

1. `app/PilotHttp/PilotHttp.php:95-103` renders one semantic `ul` of direct `li` items with native `a.shlz-link` and real card `href`. This matches the documented Link contract and avoids Table's `overflow-x: auto`, satisfying narrow document flow without copied tokens, application CSS or JavaScript.
2. All user/DB-derived output uses `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8')`. Existing CSP, cache and security headers remain centralized and unchanged.
3. The reader is SELECT-only, bounded at 501, fail-closed on overflow and corrupt/dangling/duplicate identity, validates approved fields and sorts deterministically by start date then numeric ID.
4. Non-blocking pre-existing Fowler observations: `PilotHttp.php` is a long multi-responsibility file and compressed renderers duplicate shell HTML. Spec section 10 explicitly defers this refactoring.

## Spec

Independent verdict: `PASS` — 0 findings.

1. The prior UI finding is resolved: exactly one semantic list contains direct items, each with one public Link and exact `/pilot/objects/{ID}` destination; no `table` or `.shlz-table-wrap` occurs in the collection.
2. Route/method parsing, ignored query, identity/CSS/user/list failure priority, exact outcomes and redaction conform. No failure leaks principal, object facts, count, SQL/schema/path or integrity reason.
3. Membership starts only from imported cases. Exact legacy identity, required values, finish fallback, duplicate/dangling integrity and 500/501 boundary are fail-closed; success is complete and canonically ordered.
4. GET/HEAD parity, GET-length-on-HEAD, empty success, escaping, navigation, forbidden controls and byte-equivalent DB state are exercised through raw HTTP.
5. The test catches the former bare table, horizontally scrolling Table, link/row association errors, membership/order drift, integrity omission, failure-priority changes, mutation, HEAD drift and escaping regressions. No scope creep found.

## Required changes

None. Gate 5 is approved for the exact manifest below. `PILOT-OBJECT-LIST-001 v0.1` is complete.

## Verification evidence

```text
php -l app/PilotHttp/PilotHttp.php
No syntax errors detected in app/PilotHttp/PilotHttp.php

php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

php -l tests/InstallationProcess/pilot_http_auth_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_http_auth_001_test.php

php tests/InstallationProcess/pilot_object_list_001_test.php
PASS: PILOT-OBJECT-LIST-001 public HTTP collection

php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary

all tests/InstallationProcess/*_test.php, sequential
43/43 PASS; 0 FAIL

git diff --check
PASS

production mutation-SQL scan under app/PilotHttp and public
0 statements

post-run .test-artifacts entries
0

post-run matching PHP router processes
0 (inspection shell excluded)
```

Focused-test `finally` cleanup removed its randomized schema, DB principal and CSS artifact; the suite retained no test artifacts.

## SHA-256 reviewed-input manifest

Captured `2026-08-28`. Set digests are SHA-256 over each `LC_ALL=C` binary-path-sorted per-file `sha256sum` manifest. This review file is metadata because a self-hash is circular.

```text
f48e56a6f0a65541e47d5ab2839238e508d6890cb139c57c45cce1d4748e4798  AGENTS.md
201885dc684287c1526c4657e5a9dd71f23d7dca74423fb5f329169e03fea358  PRODUCT.md
68f38cae8a69b33bb194e5b6f5d3809f4ddb90004d59af6b7a8a3c5b11870037  CONTEXT.md
721a3a6e06efb18da2702c379a785c5ed863c251622b059437bea95deccb5d54  docs/development-process.md
24e106a8db1e9fbff41637da646eeb1fa411c78e71f95b1c9351a814af9ed7a3  docs/fmonitor-2-pilot-spec.md
59d2643200f6649c20f5ce6ea104d88591bf057a0afa64ab056ddd6562162886  docs/fmonitor-2-pilot-data-model.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
5b010abd57f94f840c27947741700ca4b265c5f8a1f4345db7c8f50c657bfc20  reviews/tests/PILOT-OBJECT-LIST-001.md
f3b9de5783b9930cc614703bd059243524c133f5bb0883a14ffd8e20e3ce219a  tests/InstallationProcess/pilot_object_list_001_test.php
d745b585192b0e808fe346a61913fcce3d05a79129c4c872d9ee68c847c7acc7  tests/InstallationProcess/pilot_http_auth_001_test.php
652708eea6099b750b805b996da195c6c2b3c6eb8616270323f491591f3935f0  tests/bootstrap.php
250f582106c9e15db622473d0d9f13d0dc0a256592e3a4c4545b1cff49a06a27  public/router.php
16643afa96477b0a2c4ca93aa144cecb6084e8de2a529bfc8bcc2aad42eb4118  app/PilotHttp/PilotHttp.php
e0ab09767ebc433ba01fa9a1206c605ed6765d82d15ba6815942df30d4cd635e  app/PilotHttp/production-entrypoint.php

3bc14398cc8e8e5a6eba9b6c475a720f52fb1d28ede9e5f7530404b708b96236  ../shlz-ui/docs/components/link.md
470b0c2f6e57b061205d98f0926c676b01ecb02e8c9bb112928962eb30beab4d  ../shlz-ui/docs/components/table.md
8d09c935da2d5d3ffe7b432082712a1e2067a7d20d962e3bff93f8fadcc720cf  ../shlz-ui/packages/styles/components/link.css
a38cba53863519f64c99e1f2bbbbac8336ebfdfc98402832abc043ab95e27b10  ../shlz-ui/packages/styles/components/table.css
b8594c95ce1c06de00960fe6e603693f9c5028d8038307614798e81eb0290c40  ../shlz-ui/packages/styles/shlz.css
6754800e39cdaeb83d0800f3d2f2e781762203dbd04324c5ca67dfa1a6c05a64  ../shlz-ui/packages/styles/dist/shlz.css

dc126aa1787543b121264f46b674a1aada3430d5dc151a1c102487240a69a11b  app/PilotHttp/*.php sorted membership manifest (38 files)
0cbb6e423ca836f2d615141536b92bb6d48b507c76dbbab4faced291bb22d946  app/InstallationProcess/*.php sorted membership manifest (26 files)
ebd340371176c48a9ab8e12ed06bba87171587fe32380e5cd8b2cf20928faedf  tests/Support/* sorted membership manifest (20 files)
d739ef7cedadf2793cc16dd391b3f9780be4757543cec25a02fac88da1d2b904  tests/InstallationProcess/*_test.php sorted membership manifest (43 files)

METADATA  reviews/code/PILOT-OBJECT-LIST-001.md
```

Summary: Standards — 0 blocking findings, PASS; Spec — 0 findings, PASS. Overall Gate 5: `APPROVED`.
