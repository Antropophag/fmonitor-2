# Code review: PILOT-PREPARE-FORM-001 v0.1 — final card PTO normalization

- Gate: 5 — fresh independent review after latest Gates 2–4 correction
- Reviewer: separately tasked agent `/root/prepare_form_gate5_card_zero_final`
- Parallel axes: `/root/prepare_form_gate5_card_zero_final/standards_axis`, `/root/prepare_form_gate5_card_zero_final/spec_axis`
- Authors: other separately tasked agents; this reviewer authored neither specification, test nor production
- Reviewed ancestry: HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`; dirty-tree bytes pinned below
- Specification: `PILOT-PREPARE-FORM-001 v0.1`, `APPROVED`
- Test review: fresh `APPROVED`
- Date: `2026-08-28`
- Verdict: `APPROVED`

The final blocker is closed. `MariaDbObjectCardReader` now applies the inherited optional legacy-date classification before deriving `hasPtoAct`: SQL `NULL`, trimmed blank, all-zero literals and the zero-date prefix are absence; a real date remains a fact; malformed non-absence becomes the existing redacted infrastructure failure. The canonical card launch remains available for every approved representation of absence and suppressed for a real PTO fact.

## Standards

Independent verdict: `PASS` — 0 hard findings.

The correction is minimal and preserves capability-first authorization, SELECT-only reads, redacted failures, escaping, integration boundaries and append-only behavior.

Non-blocking Fowler judgements: `optionalLegacyDate()` is duplicated between the form and card readers, and `MariaDbPrepareFormReader::read()` remains a dense possible Divergent Change / Long Function. Extraction should be a later slice, not an expansion of this minimal Gate 4 correction.

## Spec

Independent verdict: `PASS` — 0 findings.

Static trace confirms:

- card PTO classification at `app/PilotHttp/PilotHttp.php:231,244` implements absence, malformed and real-fact distinctions;
- the launch guard exposes the exact canonical link only for a capable `needs_assignment_order` card without a real PTO fact;
- the approved raw-HTTP tracer covers SQL `NULL`, every other approved absence literal, malformed PTO `503`, real PTO suppression and incapable-actor suppression;
- prior blockers remain closed: form completion/PTO normalization and provenance validation of every non-legacy history-v5 row, including `missing_from_delivery`, before exclusion;
- the current test hash exactly matches the fresh approved Gate 3 manifest.

No missing requirement, incorrect implementation or scope creep was found.

## Verification evidence and limitation

This review intentionally did not repeat the full suite or focused test. The user prohibited another long/flaky loop, and the latest delta is the tiny card normalization already covered by the freshly approved tracer. Existing Gate 4/root evidence:

```text
focused PILOT-PREPARE-FORM-001: PASS
PILOT-HTTP-AUTH-001: PASS
PILOT-OBJECT-LIST-001: PASS
PILOT-OBJECT-CARD-001: PASS
prior sequential baseline before the tiny correction: TOTAL_PASS=44
root controlled run: business assertions passed; only global artifact/shlz fingerprint guard failed
post-run matching leftovers: none
```

Fresh bounded static checks:

```text
php -l app/PilotHttp/PilotHttp.php: PASS
php -l tests/InstallationProcess/pilot_prepare_form_001_test.php: PASS
git diff --check -- scoped production/test inputs: PASS
Standards axis: PASS
Spec axis: PASS
```

The fingerprint failure is a harness/environment limitation, not a behavior failure: it concerned concurrent/global artifact and sibling `shlz-ui` state, left no matching leftovers, and contradicted no focused HTTP assertion. Under the explicit no-repeat constraint, focused PASS plus static byte-pinned review is sufficient relevant green evidence for this minimal correction.

## SHA-256 reviewed-input manifest

Captured `2026-08-28`. Set digests hash the `LC_ALL=C` binary-path-sorted per-file manifest. This review path is metadata because self-hash is circular.

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
3c2a59476c7e7911ace6a83c39db5dd6b786a8cd83a121eff20de8e2deed844b  reviews/tests/PILOT-PREPARE-FORM-001.md
652708eea6099b750b805b996da195c6c2b3c6eb8616270323f491591f3935f0  tests/bootstrap.php
082401f1c4692ee9b1e213993bfa1f239145711fdb323a346777a08ab4b63204  tests/Support/* set (20 files)
3431c67d6e4151342e5fc928490c857d25cc319dee646da10adc4aca79a417d5  tests/InstallationProcess/*_test.php set (44 files)
250f582106c9e15db622473d0d9f13d0dc0a256592e3a4c4545b1cff49a06a27  public/router.php
e0ab09767ebc433ba01fa9a1206c605ed6765d82d15ba6815942df30d4cd635e  app/PilotHttp/production-entrypoint.php
fc3784243f0705adffe008204566122ec984cbd4f3816eb2dac2a74c4e75e20d  app/PilotHttp/PilotHttp.php
9af82773f1ac481225d5044788d656631a72ec9b77231b99650fa1eceed187bc  app/PilotHttp/*.php set (38 files)
0cbb6e423ca836f2d615141536b92bb6d48b507c76dbbab4faced291bb22d946  app/InstallationProcess/*.php set (26 files)
63ab387823c4f3525164f8a940509c58cbdde4809d7465b3b0df0f6ed0db0fb5  ../shlz-ui/docs/components/checkbox.md
36d175c32b9179c7b04a3946bb84fc809d9a15d4bdfb943376118d1151809834  ../shlz-ui/docs/components/radio.md
3bc14398cc8e8e5a6eba9b6c475a720f52fb1d28ede9e5f7530404b708b96236  ../shlz-ui/docs/components/link.md

METADATA  reviews/code/PILOT-PREPARE-FORM-001.md
```

Summary: Standards — 0 hard findings, PASS; Spec — 0 findings, PASS. Overall Gate 5: `APPROVED`.
