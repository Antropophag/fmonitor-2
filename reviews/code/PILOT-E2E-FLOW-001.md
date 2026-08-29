# Code review: PILOT-E2E-FLOW-001 v0.4

- Gate: 5 — fresh independent code review
- Reviewer: separately tasked agent `/root/e2e_code_review_linearizable`
- Specification: `d211c92eea2e4980e6ebee5c2765d677cce76f14`
- Approved test: `7daac263c9ef90b36b80682c1389fd03a50358a2`
- Approved test review: `089a9226c134af1a22d62e885569633c827dcf10`
- Exact reviewed HEAD: `9deec3b7c8987659bf702b1347e6022475c3ed92`
- Full-slice fixed point: `bc5947f^`
- Correction fixed point: `ecedb20f848b1a92f5bcdf4bf07662e0c614a332`
- Review date: 2026-08-29
- Verdict: `APPROVED`

## Verdict

`APPROVED`. The reviewed implementation conforms to `PILOT-E2E-FLOW-001 v0.4`, preserves the inherited process and artifact contracts, and passes the focused and complete regression suites. No blocking correctness, security, integration-boundary, or specification finding remains.

The previous TOCTOU rejection is not sustained under the specified concurrency model. For a missing shard component, `leafDirectory(..., false)` observes absence, revalidates the configured root and the same parent's device/inode/type/owner/mode/read-and-traverse access, and performs a final `lstat`. These observations provide a defensible absence-classification linearization point. A namespace or permission change after that point does not retroactively make the response false. Traversal and blob-read failures observed during the request fail closed as `ArtifactStoreUnavailableException` and therefore HTTP `503`; stable missing artifacts and integrity failures remain non-enumerating `404` as section 4 requires. No concrete reproducible interleaving within the specification's HTTP revision-race and store-outage model was found that is misclassified or exploitable at the chosen observation point.

## Standards

1. **MINOR, non-blocking — possible Divergent Change / Repeated Switches.** `app/PilotHttp/PilotE2ECoordinator.php:26`–`108` uses route kind repeatedly for method, capability, body schema, command dispatch, exception classification, violation mapping, and redirect behavior. A later command addition would require coordinated edits across this dense class. Per-command handlers behind one route descriptor would improve maintainability, but this does not justify refactoring outside the approved pilot slice and does not block Gate 5.

No documented-standard, security, integration-boundary, or artifact-store linearizability violation was found.

## Spec

Pass — zero findings. The complete browser journey, authorization/CSRF/PRG contracts, production `InstallationProcess` commands, immutable artifact downloads, manual registration, opening transition, queue/card projections, and failure mappings conform to the approved executable specification. No missing or partial requirement, incorrect behavior, or scope creep was identified.

## Verification

```text
$ php tests/InstallationProcess/pilot_e2e_flow_001_test.php
PASS: PILOT-E2E-FLOW-001 configured real HTTP journey

$ php tests/InstallationProcess/artifact_store_001_test.php
PASS: ARTIFACT-STORE-001

$ for test_file in tests/InstallationProcess/*_test.php; do php "$test_file"; done
47/47 test files PASS

$ find app public tests/InstallationProcess -type f -name '*.php' -print0 | xargs -0 -n1 php -l
PHP lint PASS

$ git diff --check
PASS

$ git diff ecedb20...9deec3b --check
PASS
```

## Reviewed-input hashes

```text
2c9ae79f73e5a3bf8d93c81fad3f431bd810a5d63c2648fa7dfab16f646839ab  specs/PILOT-E2E-FLOW-001.md
2929830a6808fac914557b75b9689f13f2b9ae95beb69dfc3ed8d71887ad3a14  tests/InstallationProcess/pilot_e2e_flow_001_test.php
e496719fcdd71d04c5189156b0e2261ce08d2d70930cd0f6b8e5ac6cf5a8fb99  reviews/tests/PILOT-E2E-FLOW-001.md
3eb776bdd7706ec5790571813009504c7a2d7bf55f283573cc8f22613d789d7b  app/InstallationProcess/ContentAddressedArtifactStore.php
0b1eb3ba3cc67d000b8347b850803187d1ffca8a0b6c5266c7a1185840ea1484  app/InstallationProcess/AssignmentOrderArtifactService.php
91faf7cd38bad1792df5e6d40f705a18c3203d2ed0ff000d417413a0d7ef2e54  app/PilotHttp/PilotE2ECoordinator.php
```

## Required changes

None. Gate 5 is approved for exact reviewed commit `9deec3b7c8987659bf702b1347e6022475c3ed92`.
