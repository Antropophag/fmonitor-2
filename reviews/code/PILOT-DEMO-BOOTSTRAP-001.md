# Code review: PILOT-DEMO-BOOTSTRAP-001 v0.2

- Gate: 5 — fresh independent final production review
- Reviewer: separately tasked agent `/root/bootstrap_code_review_end`
- Independence: reviewer authored neither specification, approved tests, nor implementation
- Specification: `919383966f962fb9811a5bf6350536310de03683`
- Inherited asset specification: `PILOT-SHLZ-ASSETS-001 v0.2` at `331b8ac9616b99162fe75b7bc501e1dc223a9d73`
- Approved bootstrap test review: `209886952c255e728466827549e07aa7b1ddf56c`
- Approved asset test review: reviewed corrective test `2414f54392226b2d55f84bfa434f2c9160871d8e`
- Exact reviewed implementation HEAD: `df765325fe1a3823dcffc534a7fc05b376328657`
- Implementation commits relevant to the final corrective chain: `2186132`, `545fdfa`, `df76532`
- Browser evidence: `~/code/fmonitor-2-visual-tools/evidence/final-pilot-acceptance-df76532/`
- Review date: 2026-08-29
- Verdict: `APPROVED`

## Standards

No blocking standards, security, integration-boundary, cleanup, or residue findings.

The corrective production delta closes every blocking finding from the prior Gate 5 review. `ShlzCssManifest` accepts the approved trusted owner boundary (effective UID or UID 0), requires owner read/search on directories and owner read on files, retains every opened member descriptor through whole-graph directory/path/descriptor/size/hash revalidation, closes descriptors attempt-all, and serves only captured bytes after successful graph validation. The global CLI-server `503` timing workaround has been removed. No cross-request SysV/cache/lock/temp/sentinel/guardian mechanism is introduced.

Non-blocking maintainability observations: security-critical graph capture remains compressed into dense methods; `CssDescriptorOpener` is retained while the new graph capture uses native streams; and older `ManifestCssAsset` identity machinery remains alongside the captured response path. These are judgment-call Mysterious Name/Divergent Change, Speculative Generality/Middle Man, and duplication concerns. They do not weaken the reviewed public behavior and should not expand this delivery slice.

## Specification

No missing, partial, incorrect, or scope-crept requirement found in the cumulative bootstrap plus stateless asset behavior.

The public bootstrap provisions the approved real-data fixture, performs the full transitive asset smoke, preserves the redacted `SHLZ_ASSETS_UNAVAILABLE` classification for post-bind graph failure, exposes the complete browser journey, preserves state across restart, resets into a fresh generation, and cleans up only owned resources. The inherited stateless asset contract is enforced per request without process-global identity or bytes: descriptors are opened once, retained through global revalidation, rewound and reread on the same handles, closed once attempt-all, and the selected response is constructed from the captured graph bytes.

Exact-head Chromium evidence records the full queue → object → composition → prepared/downloadable artifacts → 1C DO registration → opening → updated queue journey. Both immutable HTML artifacts are nonempty; all recorded CSS responses are `200` with exact CSS MIME; final queue/card evidence at `1440×900`, `768×1024`, and `320×568` has no horizontal overflow, visible keyboard focus, applied `.shlz-button`, a public `--shlz-*` property, and the application `.fm2-shell` rule. The two Playwright `ERR_ABORTED` entries are the normal browser download handoff and are paired with two successful saved artifacts, not failed asset/application requests.

## Verification

```text
php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php   PASS
php tests/InstallationProcess/pilot_shlz_assets_001_test.php     PASS
tests/InstallationProcess/*_test.php                             49/49 PASS
PHP lint: app bin public tests                                  PASS
git diff --check 9193839...df76532                              PASS
demo/router and mutation-worker process residue                 none after verification
SysV shared-memory residue                                      none
task-owned psa/bootstrap fixture residue                         none
exact browser evidence final-pilot-acceptance-df76532           present and HEAD-matched
```

Gate 5 is approved for `PILOT-DEMO-BOOTSTRAP-001 v0.2` at exact reviewed commit `df765325fe1a3823dcffc534a7fc05b376328657`.
