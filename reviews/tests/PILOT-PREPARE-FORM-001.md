# Test review: PILOT-PREPARE-FORM-001 v0.1 — card PTO-absence correction

- Gate: 3 — fresh independent test review after Gate 2 correction
- Reviewer: separately tasked agent `/root/prepare_form_gate3_card_null`
- Test author/corrector: other separately tasked agents; this reviewer authored neither specification nor test
- Reviewed ancestry: HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`; dirty-tree bytes pinned below
- Specification: `PILOT-PREPARE-FORM-001 v0.1` (`APPROVED`)
- Public seam: raw HTTP form/card requests through `public/router.php` and isolated MariaDB
- Observed intended RED: expected `200`, actual `503`, at `card PTO absent "" remains valid`
- Date: `2026-08-28`
- Verdict: `APPROVED`

## Findings

No blocking findings remain.

The sole prior gap is closed. The nominal card request starts from the literal SQL `NULL` fixture and now independently requires exact `200`, `text/html; charset=UTF-8`, and exactly one canonical `Сформировать распоряжение` link. The correction matrix separately proves the same card behavior for empty string, whitespace, `0`, repeated zeros, and a zero-date prefix. An implementation that mishandles SQL `NULL` response metadata or any represented string absence can no longer satisfy the tracer.

## Independent assessment

- **Traceability and seam — pass.** The test cites `PILOT-PREPARE-FORM-001 v0.1`; literal fixtures and expected HTTP/DOM values come from sections 4–10 and inherited approved contracts. Requests cross the real router with a separate SELECT-only HTTP identity. No private production normalizer or rendered result supplies expected values.
- **Nominal form sensitivity — pass.** Exact route/status/content type/length, GET/HEAD parity, semantic form structure, installer and engineer identity/order, unchecked choices, eligible legacy prefill, separate unchecked confirmation, provenance, exclusions, query indifference and forbidden controls are asserted.
- **Card PTO absence — pass.** SQL `NULL`, empty string, whitespace, literal zero, repeated zeros and zero-date prefix are independently covered. SQL `NULL` has exact status/type/link assertions in the nominal card case; every string form repeats those assertions through literal mutations.
- **Malformed and real PTO behavior — pass.** Malformed PTO requires redacted `503` at both form and card seams. A real PTO date requires form `503` and a successful HTML card suppressing launch text, canonical route and every prepare URL. Completion values receive corresponding absent/malformed/real-fact form checks.
- **History-v5 and catalog integrity — pass.** Missing/stale/failed/in-progress sync metadata, delivered and missing-from-delivery provenance contradictions, malformed rows, eligibility exclusions, empty states and both hard ceilings are observable through HTTP with literal expectations.
- **Authorization/order and failure behavior — pass.** Missing identity, denied capability, unknown/non-imported object, wrong state, invalid route/method, DB failure, dangling case and combined corrupt-object/catalog non-enumeration are covered.
- **Mutation sensitivity — pass.** Every relevant success and rejection read is wrapped by `ppfRead`, fingerprinting all table rows, `AUTO_INCREMENT`, `.test-artifacts` and `../shlz-ui`. The HTTP database identity receives SELECT only.
- **RED validity — pass.** Current production reaches the intended corrected card assertion and fails because empty-string PTO is classified as integrity failure: expected `200`, actual `503`. CSS preflight and preceding nominal form/card assertions pass, excluding fixture/config/route failure.
- **Determinism, isolation and cleanup — pass.** Business date, source timestamps and expected values are fixed; database/user/CSS paths are randomized. The reproduced failure exited through `finally`; no `ppf-*` artifact directory or target PHP server remained.

Gate 3 is approved. Gate 4 may change production only enough to make this reviewed tracer pass without changing the approved expectations. Any test/spec input change invalidates this approval and requires a fresh independent Gate 3.

## Verification evidence

```text
$ php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_prepare_form_001_test.php
exit code: 0

$ php tests/InstallationProcess/pilot_prepare_form_001_test.php
PHP Fatal error: Uncaught TestFailure: card PTO absent "" remains valid
Expected: 200
Actual: 503
... pilot_prepare_form_001_test.php(85)
exit code: 255

post-run .test-artifacts/ppf-* entries: 0
post-run target test/router processes: 0
```

## SHA-256 reviewed-input manifest

Captured `2026-08-28`. Set digests hash the `LC_ALL=C` binary-path-sorted per-file `sha256sum` manifest and pin bytes and membership. This review path is metadata because a self-hash is circular.

```text
35759ce4856d703e197c1e70e00a14ec316b3e94104ca959a5d4abf19c50c669  specs/PILOT-PREPARE-FORM-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
e434c0d18564a08c2ec4238bb645d5cd7458c9a617ad95fb9f6eccdae9440034  specs/WORKFORCE-CATALOG-001.md
40629b6f083dfad29cb414a935eab7128eee10627dfcc3da2f3baad27b139cc0  specs/PROCESS-USER-DIRECTORY-001.md
c9c020e8d083c0eaf50c3273bcd1c64718ee3b0374b6620879240076fdebc39e  specs/ORDER-PREPARE-*.md set (19 files)
721a3a6e06efb18da2702c379a785c5ed863c251622b059437bea95deccb5d54  docs/development-process.md
201885dc684287c1526c4657e5a9dd71f23d7dca74423fb5f329169e03fea358  PRODUCT.md
68f38cae8a69b33bb194e5b6f5d3809f4ddb90004d59af6b7a8a3c5b11870037  CONTEXT.md
24e106a8db1e9fbff41637da646eeb1fa411c78e71f95b1c9351a814af9ed7a3  docs/fmonitor-2-pilot-spec.md
59d2643200f6649c20f5ce6ea104d88591bf057a0afa64ab056ddd6562162886  docs/fmonitor-2-pilot-data-model.md
d25697ef31b94af822c77ace04e26eba129e8ecbcd1b145dfe06e8feb75e23d8  tests/InstallationProcess/pilot_prepare_form_001_test.php
652708eea6099b750b805b996da195c6c2b3c6eb8616270323f491591f3935f0  tests/bootstrap.php
ebd340371176c48a9ab8e12ed06bba87171587fe32380e5cd8b2cf20928faedf  tests/Support/* set (20 files)
3431c67d6e4151342e5fc928490c857d25cc319dee646da10adc4aca79a417d5  tests/InstallationProcess/*_test.php set (44 files)
250f582106c9e15db622473d0d9f13d0dc0a256592e3a4c4545b1cff49a06a27  public/router.php
e0ab09767ebc433ba01fa9a1206c605ed6765d82d15ba6815942df30d4cd635e  app/PilotHttp/production-entrypoint.php
c659c3fe7db40a6e066db922a34b64eae3726955f70ec9883db14bf38ef51e1b  app/PilotHttp/PilotHttp.php
a9aac2b9463b736b7d3eeab800a9c4d4c0a68acff97b67218708f19892b4279d  app/PilotHttp/*.php set (38 files)
0cbb6e423ca836f2d615141536b92bb6d48b507c76dbbab4faced291bb22d946  app/InstallationProcess/*.php set (26 files)
63ab387823c4f3525164f8a940509c58cbdde4809d7465b3b0df0f6ed0db0fb5  ../shlz-ui/docs/components/checkbox.md
36d175c32b9179c7b04a3946bb84fc809d9a15d4bdfb943376118d1151809834  ../shlz-ui/docs/components/radio.md
3bc14398cc8e8e5a6eba9b6c475a720f52fb1d28ede9e5f7530404b708b96236  ../shlz-ui/docs/components/link.md
METADATA  reviews/tests/PILOT-PREPARE-FORM-001.md
```
