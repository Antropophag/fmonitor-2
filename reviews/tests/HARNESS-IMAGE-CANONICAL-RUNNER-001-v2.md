# Independent test rereview — HARNESS-IMAGE-CANONICAL-RUNNER-001 v10 alignment

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED**

The reviewer did not author or edit the reviewed test, Dockerfile, entrypoint or
bootstrap. This rereview covers only the completion-v10 baseline alignment of
the already-approved image harness.

## Reviewed hashes

```text
e35e07b0131d336169a958f393e98ed01d7c9f4cade67e15fd15343bbc46f3ce  tests/Verification/harness_image_canonical_runner_001_test.php
d23b3563c1921c9b54abe0d60134eaabb0a4aad367a2141e3203934757ebb10a  rapid-pilot/docker-entrypoint.sh
700393249fd0c0982564ede62e75f090810624b5bb82eeea8330590e3c0dc29f  rapid-pilot/docker-bootstrap.php
7f8370fa26797bc4a57da565e5d728270e7a9e77fe7ab76f934ea1a68f97f45e  Dockerfile
6466e5cb6dbc7ab8d2da97dd8ce70722562bea967bd46ce21e0c45fd40dae7bd  specs/HARNESS-IMAGE-CANONICAL-RUNNER-001.md
70561068c6b799e6e4ebe10cfb146ae4a73c315e93eb13358607063a8c8c03cc  reviews/tests/HARNESS-IMAGE-CANONICAL-RUNNER-001.md
```

The task handoff called the entrypoint `docker-entrypoint`; the repository
artifact is the executable shell file `rapid-pilot/docker-entrypoint.sh`, whose
hash is recorded above.

## Alignment review

The previously approved harness behavior is unchanged except for two exact
baseline pins required by the separately approved completion-v10 deployment
integration:

- the entrypoint pin advances to the canonical-startup script that computes the
  generation process prefix and invokes the real migration CLI before the
  bootstrap/runtime;
- a new exact bootstrap pin binds the image test to the reviewed fail-closed,
  DML-only completion-v10 bootstrap bytes.

This does not let the image verifier infer behavior from broad source matching.
Any entrypoint or bootstrap byte change now fails before Docker build. The
Dockerfile packaging-only invariant remains independently pinned: exactly one
physical canonical `COPY bin ./bin` is allowed, and deleting only that complete
instruction yields byte-exact approved baseline
`6489de91f81d26d5be615e597d6d7503a4edc580b7e726a29327325f96ba8702`.
The current Dockerfile passes that reconstruction despite its full-file hash
being `7f8370fa…`.

The original harness specification said this packaging slice itself must not
introduce implicit startup migration. That historical slice remains true: its
single Dockerfile delta is still only `COPY bin ./bin`. Canonical startup was
introduced later by the separately gated completion/deployment integration;
v2 records that new approved baseline rather than reclassifying it as an
unreviewed packaging change.

## Preserved sensitivity

The rereview confirms the following assertions remain present and unchanged:

- exactly one canonical two-argument COPY from repository `bin` to `./bin`,
  rejecting continuations, extra sources and unrelated COPY instructions;
- exact `USER fmonitor` and exact Dockerfile entrypoint declaration;
- fresh image build under a random owned tag;
- runtime UID `10001`, username `fmonitor`, readable real migration CLI and
  readable `app/InstallationProcess` dependencies;
- absence of `tests`, `reviews`, `specs`, `docs`, `tools`, `.git` and `.local`
  from `/workspace/fmonitor-2`;
- absence of `.env*`, dump/backup extensions, private key/certificate formats
  and primary `.msg` evidence, while retaining the approved application-SQL
  exception;
- public image runner execution with missing configuration: exit `64`, exact
  one-line `CONFIGURATION_INVALID` JSON stdout and empty stderr;
- Docker daemon/build/launch failures remain `SETUP_FAILURE`, distinct from
  `RED_ASSERTION` contract drift;
- random ownership label, CID-file verification, owned-container/image/artifact
  cleanup and no broad deletion target.

Neither the runner probe nor the readable/image-content probe receives secrets
or migration configuration. Updating the startup/bootstrap hashes therefore
cannot mask configuration-invalid or redaction behavior. The image is built
fresh from the current Dockerfile and the probes override the entrypoint only
for their explicitly approved inspection/runner public seams.

## Independent verification

```text
sed '/^COPY bin \.\/bin$/d' Dockerfile | sha256sum
6489de91f81d26d5be615e597d6d7503a4edc580b7e726a29327325f96ba8702

sha256sum rapid-pilot/docker-entrypoint.sh
d23b3563c1921c9b54abe0d60134eaabb0a4aad367a2141e3203934757ebb10a

php -l tests/Verification/harness_image_canonical_runner_001_test.php
No syntax errors detected

php -l rapid-pilot/docker-bootstrap.php
No syntax errors detected

git diff --check -- <reviewed artifacts>
exit 0, empty output

php tests/Verification/harness_image_canonical_runner_001_test.php
HARNESS-IMAGE-CANONICAL-RUNNER-001 passed
exit 0
```

The full verifier built a fresh image and reproduced the non-root, contents,
readability, entrypoint declaration and exact configuration-invalid public
runner contract. Verdict: **APPROVED** at the exact hashes above. Any later
entrypoint, bootstrap, Dockerfile or test edit requires another independent
rereview.
