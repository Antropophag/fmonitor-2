# Code review: HARNESS-IMAGE-CANONICAL-RUNNER-001 v0.1

- Gate: 5 — independent code review
- Reviewer: separately tasked fresh agent `/root/image_canonical_runner_code_review`
- Independence: this reviewer did not author the specification, approved test, or implementation
- Reviewed ancestry: HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`; dirty-tree bytes pinned below
- Specification: `specs/HARNESS-IMAGE-CANONICAL-RUNNER-001.md`, version `0.1`
- Approved test review: `reviews/tests/HARNESS-IMAGE-CANONICAL-RUNNER-001.md`, verdict `APPROVED`
- Production artifact/public seam: repository `Dockerfile`, observed through a freshly built runtime image
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Standards

No findings. Relative to the approved byte baseline, the implementation adds exactly one physical `COPY bin ./bin` instruction and no other bytes. It reuses the canonical repository runner without generation, wrappers, startup coupling, or speculative packaging logic. Existing `USER fmonitor`, work directory, and rapid-pilot entrypoint remain unchanged.

Although the shared worktree's Dockerfile also differs from HEAD in the already-present SHLZ UI revision, that revision is part of the independently approved pre-change Dockerfile baseline for this slice. Removing the sole canonical bin COPY from the reviewed bytes reproduces the pinned baseline SHA-256 exactly, so the implementation delta under review is unambiguously the one authorized line.

## Specification

No findings. A fresh runtime image contains the real `bin/fmonitor2-migrate.php` and its existing `app/InstallationProcess` dependencies. The image probe runs as UID `10001`, user `fmonitor`, and confirms both the runner and all dependency files are readable. With migration configuration absent, overriding the entrypoint with PHP reaches the public CLI and produces exactly `{"ok":false,"reason":"CONFIGURATION_INVALID"}` plus newline on stdout, no stderr, and exit `64`.

The Dockerfile still declares `USER fmonitor` and `ENTRYPOINT ["rapid-pilot/docker-entrypoint.sh"]`; the entrypoint's pinned bytes are unchanged and no instruction invokes the migration CLI during build or startup. The built-image check excludes the named repository-only trees, environment files, dump/backup formats, private key/certificate formats, and primary `.msg` evidence while retaining the explicitly permitted application SQL resources.

Setup/build/container-launch failures are distinguished as `SETUP_FAILURE`; contract drift is a `RED_ASSERTION`. The verifier allocates a random image tag, ownership label, artifact directory, and CID files. Cleanup inspects the ownership label before removing a container, removes only its exact random image and artifact subtree, and the post-run check found no owned container or `fmonitor2-hicr` image remaining.

## Verification evidence

The reviewer ran:

```text
php tests/Verification/harness_image_canonical_runner_001_test.php
HARNESS-IMAGE-CANONICAL-RUNNER-001 passed

php rapid-pilot/verify-deployment-contract.php
PASS deployment contract

php -l tests/Verification/harness_image_canonical_runner_001_test.php
No syntax errors detected

make lint
PASS

make architecture-check
ARCHITECTURE CHECK PASSED (6 rules)

git diff --check
PASS
```

The focused verifier performed a fresh Docker build, public non-root readability probe, exact runner contract probe, sensitive-material scan, and ownership-safe cleanup. The independently approved baseline reconstruction was also reproduced: one canonical COPY was removed and the remaining Dockerfile hash was `6489de91f81d26d5be615e597d6d7503a4edc580b7e726a29327325f96ba8702`.

## Reviewed hashes

```text
6466e5cb6dbc7ab8d2da97dd8ce70722562bea967bd46ce21e0c45fd40dae7bd  specs/HARNESS-IMAGE-CANONICAL-RUNNER-001.md
70561068c6b799e6e4ebe10cfb146ae4a73c315e93eb13358607063a8c8c03cc  reviews/tests/HARNESS-IMAGE-CANONICAL-RUNNER-001.md
e50a6a0fb12c5bec76221d118202adf3228dd0242defb0ace0e9a3e847028479  tests/Verification/harness_image_canonical_runner_001_test.php
7f8370fa26797bc4a57da565e5d728270e7a9e77fe7ab76f934ea1a68f97f45e  Dockerfile
61fa6249f6aee6866f662e2cc487382b15cde602444039a9fec155aff385b33d  rapid-pilot/docker-entrypoint.sh
```

Gate 5 is approved for the reviewed bytes. HARNESS-IMAGE-CANONICAL-RUNNER-001 satisfies its Done definition.
