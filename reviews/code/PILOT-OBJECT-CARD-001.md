# Code review: PILOT-OBJECT-CARD-001 v0.2

- Gate: 5 — fresh independent final release review after volatile SAPI `Date` oracle correction
- Aggregator: `/root/object_card_gate5_date_release`
- Standards reviewer: `/root/object_card_gate5_date_release/standards_axis`
- Spec reviewer: `/root/object_card_gate5_date_release/spec_axis`
- Reviewed ancestry: HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`; ancestry only
- Fixed point: exact approved Gate 3 manifest in `reviews/tests/PILOT-OBJECT-CARD-001.md`
- Approved test SHA-256: `4577d14d1323844cc36aab3935269e708c1adb854e4f1d6e9f036950d66c45dc`
- Current production SHA-256: `34e294b65e30499293687414fdf8f87791cbca752c9a1c8830f8094cebe07661`
- Verdict: `APPROVED`

Both independently tasked axes approve the exact production and approved-test bytes. Final release verification is green. No blocking finding remains.

## Standards

Independent verdict: `APPROVED` — 0 findings.

No violation of `AGENTS.md`, `docs/development-process.md`, product/domain terminology, append-only/read-only boundaries, or the `shlz-ui` public-export boundary was found. Production remains SELECT-only, parameterizes values, validates the sole interpolated legacy prefix before use, escapes database-derived HTML, fails closed on inconsistent projections, and preserves request cleanup and redaction. No actionable smell-baseline finding was identified. `MariaDbObjectCardReader` is dense but cohesive around the single read-model seam; further abstractions would be speculative for this slice.

All prior production blockers remain resolved: unique highest/current order with no fallback, durable event-ID ordering, complete team/event tuples, normalized registered number, semantic group/cardinality rules, complete fail-closed state combinations, and calendar-valid RFC3339 round trips.

## Spec

Independent verdict: `APPROVED` — 0 findings.

No missing or partial requirement, scope creep, wrong implementation, security regression, invariant bypass, or test-sensitivity defect was found. The reviewer independently rechecked route and failure priority, opaque `404` versus fail-closed `503`, imported legacy identity, unique current order selection, immutable snapshots, all five states, dates and RFC3339 validation, durable event ordering, escaping and semantic output, SELECT-only/zero mutation, GET/HEAD parity, cleanup, and resource isolation.

The `Date` correction is narrow. `pocResponseWithoutVolatileDate()` removes only the parsed lowercase SAPI `date` header, and is called only by the repeated and concurrent successful-GET equality oracles. Status, body, `connection`, `host`, and every application-controlled header remain compared. General security, success/error, GET/HEAD parity, and request-identity oracles are unchanged.

## Verification evidence

```text
focused public HTTP/MariaDB test, repetitions 1..3               3/3 PASS
all 42 InstallationProcess tests, sequential                    42/42 PASS
parallel xargs -P8, initial series                              runs 1-2: 42/42 PASS
parallel xargs -P8, initial run 3                               unrelated auth close-probe race
pilot_http_auth_001_test.php after zero-process/artifact check   PASS
parallel xargs -P8, clean rerun series                          3/3 at 42/42 PASS
all 131 PHP files under app/bin/public/tests, php -l             PASS
git diff --check                                                 PASS
production write-SQL scan                                        0 statements
security/debug scan                                              only approved guarded error reporter
post-run .test-artifacts entries                                 0
post-run matching test/xargs processes                           0
```

The initial parallel run 3 reproduced the previously known independent `PILOT-HTTP-AUTH-001` probe race: its global connection observation briefly saw another concurrently running test connection (`39137`). The object-card test passed in that run. The failed series was not counted. After confirming zero residual processes and artifacts, the auth test passed alone and a fresh required three-run `-P8` series passed `42/42` in every run.

## Exact reviewed-input manifest

Captured `2026-08-28`. Set digests hash the `LC_ALL=C` binary-path-sorted complete per-file `sha256sum` manifests. The output path is metadata because a literal self-hash is circular.

```text
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
721a3a6e06efb18da2702c379a785c5ed863c251622b059437bea95deccb5d54  docs/development-process.md
014bf3f5726ef7913816ebb536a0b57946b1203e96809c2ecb14f49d4d0e3d19  reviews/tests/PILOT-OBJECT-CARD-001.md
4577d14d1323844cc36aab3935269e708c1adb854e4f1d6e9f036950d66c45dc  tests/InstallationProcess/pilot_object_card_001_test.php
652708eea6099b750b805b996da195c6c2b3c6eb8616270323f491591f3935f0  tests/bootstrap.php
f15a1d73d5fb577116343c2c7c581ec4cbc44dfad3c9dab6a72c6de7f67e0ee9  tests/Support/real_http_entrypoint_spy.php
250f582106c9e15db622473d0d9f13d0dc0a256592e3a4c4545b1cff49a06a27  public/router.php
34e294b65e30499293687414fdf8f87791cbca752c9a1c8830f8094cebe07661  app/PilotHttp/PilotHttp.php
e0ab09767ebc433ba01fa9a1206c605ed6765d82d15ba6815942df30d4cd635e  app/PilotHttp/production-entrypoint.php
370ee53363feb3905a16e110dbc81bacceeb74a7d9944677b8a536226e9bd26a  app/PilotHttp/*.php (38 files)
0cbb6e423ca836f2d615141536b92bb6d48b507c76dbbab4faced291bb22d946  app/InstallationProcess/*.php (26 files)
ebd340371176c48a9ab8e12ed06bba87171587fe32380e5cd8b2cf20928faedf  tests/Support/* (20 files)
49bf8e4710ff96ba03916135dea8cce3ac6bd66868092a91bdc8da8832af5451  tests/InstallationProcess/*_test.php (42 files)

METADATA  reviews/code/PILOT-OBJECT-CARD-001.md
```

Summary: Standards — 0 findings, `APPROVED`; Spec — 0 findings, `APPROVED`. Overall Gate 5: `APPROVED`.
