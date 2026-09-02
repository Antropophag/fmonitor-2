# Test review: HARNESS-IMAGE-CANONICAL-RUNNER-001 v0.1

- Gate: 3 — independent test review
- Reviewer: separately tasked fresh agent `/root/image_canonical_runner_test_review_v3`
- Independence: this reviewer did not author the specification, test, or implementation
- Reviewed state: shared dirty worktree; reviewed bytes pinned below
- Public seam: repository `Dockerfile`, observed through a freshly built runtime image and `docker run --rm --entrypoint php <image> bin/fmonitor2-migrate.php`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

No blocking findings.

Traceability and seam coverage pass. Before any build, the test requires exactly one physical canonical `COPY` from repository `bin` to `./bin`. It removes only that complete instruction, including its line terminator, and requires the remaining bytes to equal the independently approved Dockerfile SHA-256. The parser accepts shell or two-element JSON COPY syntax plus an optional `--chown`, while rejecting continuation lines, additional sources, and unrelated COPY instructions. Consequently the reviewed GREEN can add the packaging line but cannot hide another Dockerfile change. The entrypoint's independently pinned byte hash, exact Dockerfile `ENTRYPOINT`, and `USER fmonitor` assertions prevent implicit migration startup or a runtime-user change.

The fresh-image probes exercise the public container boundary. As UID/name `10001`/`fmonitor`, the image must expose a readable real runner and readable `app/InstallationProcess` files. The runner must then produce the specification literal JSON plus newline, empty stderr, and exit `64` with configuration absent. Expected output is not derived from implementation.

Sensitive-material assertions reject forbidden repository trees, `.env*`, common database dump/backup extensions, private-key/certificate extensions, and primary `.msg` evidence anywhere in the built workspace. They deliberately do not reject application `*.sql` resources such as `schema.sql`, nor source merely because its filename discusses secret handling. This matches the stated exception without weakening the named forbidden-material checks.

Isolation and classification are adequate: the test uses a random image tag, label value, artifact directory, and CID files; removes only containers whose inspected ownership label matches; removes only its owned image/artifacts in `finally`; classifies daemon/build/launch failures as `SETUP_FAILURE`; and reports contract drift as `RED_ASSERTION`. A failed container probe cannot be confused with the expected runner exit `64`.

## Reproduced RED

Command:

```text
php tests/Verification/harness_image_canonical_runner_001_test.php
```

Result: exit `1` at the intended first assertion:

```text
RED_ASSERTION: Dockerfile must contain exactly one canonical COPY from repository bin to ./bin
Expected: 1
Actual: 0
```

This is behavior RED for the missing packaging instruction, not an environment or image-build failure. The current Dockerfile itself hashes to the approved pre-change baseline, and the entrypoint pin also matches.

## Additional verification

```text
php -l tests/Verification/harness_image_canonical_runner_001_test.php
No syntax errors detected in tests/Verification/harness_image_canonical_runner_001_test.php

git diff --check
exit 0
```

## Reviewed hashes

```text
6466e5cb6dbc7ab8d2da97dd8ce70722562bea967bd46ce21e0c45fd40dae7bd  specs/HARNESS-IMAGE-CANONICAL-RUNNER-001.md
e50a6a0fb12c5bec76221d118202adf3228dd0242defb0ace0e9a3e847028479  tests/Verification/harness_image_canonical_runner_001_test.php
6489de91f81d26d5be615e597d6d7503a4edc580b7e726a29327325f96ba8702  Dockerfile
61fa6249f6aee6866f662e2cc487382b15cde602444039a9fec155aff385b33d  rapid-pilot/docker-entrypoint.sh
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
```

Gate 3 is approved. Gate 4 may add the single canonical `COPY bin ./bin` packaging instruction without changing the reviewed specification or test.
