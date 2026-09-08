# Code review: BITRIX-STARTUP-CONFIG-001

- Reviewer: `/root/review_startup` (independent agent)
- Implementation author: `/root`
- Reviewed commit: `3f824533e71873806523b8d60782fc44d56282a4`
- Runtime implementation commit: `1e9cbb1ce407464f4c491c7c44f38498e623c6dd`
- Baseline: `1dd62c2e24d3abb35f68573c18cc500f17d7d387`
- Specification: `specs/BITRIX-STARTUP-CONFIG-001.md` v0.1
- Approved tests: `reviews/tests/BITRIX-STARTUP-CONFIG-001.md`, `reviews/tests/BITRIX-STARTUP-CONFIG-001-characterization-correction.md`, `reviews/tests/BITRIX-STARTUP-CONFIG-001-json-escape.md`
- Verdict: `APPROVED`

## Reviewed artifact hashes

```text
25682fafd34ff526593f0f91a7c0d971bd282bd4b1cc4d1d8df3e7d7ffc01b29  bin/fmonitor2-prepare-bitrix-config.php
4c0e3264cd9a96e74e48825876cff1d5d31cdfeed4c476a092c83b9be47839de  Makefile
5441e12ee9cf3839f9fea709d39b957dbb8700c1c172f2bfb68b6d73fc041070  compose.yaml
214cbd23d2344181ab6ef039857878de75cdf58b3e6bc601fdc938c289228e17  tools/delivery/compose.yaml.in
cd1914b3605fdff76b8423079e5e3e23220015148d8ea7a7ffb8e2dc70eb968c  .dockerignore
0f99624b50ae0e6c8f4ec891f9915a5fef2424160ca3af96b6d2a297283ba79e  .gitignore
df8606f5068a40df0b198223bca0f7fe377583fde94ecdc229910bc3b3cf5944  .env.example
aca38b9f3d4bb116250aa523b8c32b79b0adb62c31f60e1abf6cce8510154a1c  README.md
8aa6f5b15b0baa251e2e71dcd91fe14438e0735fb245d6e02ed5d752fdbc6c9f  rapid-pilot/README.md
df50e9ff18ed6d344d8b626ca3b665628bc074d5f0accadbf82680ee56dc0942  docs/bitrix-startup.md
3cdc972579361cec82f21f5e75cd344bb66e04ae58c06f08250db1a0888b405b  specs/BITRIX-STARTUP-CONFIG-001.md
e6c2afbb3fca4659ad125da733cd5e4e72c813af3eb343b96d38e3ea1981c7b1  tests/Deployment/bitrix_startup_config_001_test.php
ae6fdcf4c98054aec2f79d5db2ed4037886012556836a43e44efd313b6aea8cc  rapid-pilot/verify-deployment-contract.php
deee9ed58eecb0e14808fb8d2da16ecdb68ee009967700408f5faebb02d1e7aa  docs/operations/bitrix-startup-config-001-green-2026-09-08.md
39b3ed61317833294fb3fad3272d697922343779bad01500d79df2a5f12a598f  openspec/changes/explicit-bitrix-startup-configuration/tasks.md
```

`rapid-pilot/export-legacy-bitrix-secret.php` is deleted by the reviewed commit.

## Findings

The implementation conforms to the approved seams. `make up` checks the local input, builds the application image without Compose parsing the secret first, runs the preparation CLI in a networkless container with read-only `.env`, validates Compose quietly, starts the pilot/database, and force-recreates the standard worker. The separate `up-bitrix` target, opt-in profile, host PHP call, sibling checkout dependency, and legacy exporter are removed.

Secret handling is fail-closed. Target values are parsed as literal single-line assignments without shell evaluation or interpolation. Backslashes are rejected before JSON decoding. The generated document is validated by the existing `WorkerConfiguration` and existing delivery configuration limits, so the adapter does not introduce a competing webhook validator. Preparation has no database or network access. It writes a mode-0600 temporary file inside a mode-0700 directory and atomically renames it only after validation; failures remove only the temporary file and retain the prior output.

The secret remains outside the image and runtime environment. `.env`, `.env.*`, and `.local` are excluded from Git and build context, with only the synthetic `.env.example` restored. Make arguments contain mount paths rather than values, Compose configuration diagnostics are suppressed in favor of a generic remediation message, and the worker consumes the existing file secret. Force recreation is required after atomic replacement because an existing bind mount can retain the prior inode; the reviewed lifecycle includes it while preserving the database and pilot volumes.

The public worker and synchronization application remain unchanged. The change therefore does not create a new writer, alter authorization, rewrite history, modify schema, or broaden the Bitrix transport behavior.

One blocking finding arose during review: JSON escape sequences in a department string were decoded into an otherwise accepted numeric ID despite the literal-input contract. An independently approved regression reproduced the issue. The implementation now rejects raw backslashes, and the focused suite is green. No blocking findings remain.

## Verification evidence

Independently rerun against the reviewed source:

```text
php tests/Deployment/bitrix_startup_config_001_test.php
BITRIX-STARTUP-CONFIG-001 tests passed.

php rapid-pilot/verify-deployment-contract.php
PASS deployment contract

php tests/InstallationProcess/workforce_worker_cli_manual_pilot_test.php
workforce_worker_cli_manual_pilot_test: PASS

python3 tests/Verification/verification_inventory_001_test.py
Ran 15 tests
OK
```

Recorded candidate checks are `python3 tools/delivery/render-dependencies.py --check` PASS, architecture check PASS with 7 rules, lint exit 0, OpenSpec strict PASS, and diff check PASS.

The isolated real-Docker evidence in `docs/operations/bitrix-startup-config-001-green-2026-09-08.md` used image `sha256:f0f4d13e2b47bf2b73057e905408cb95a2d0125560435edc1f3a8d64be8ae2ca` and a local HTTPS fixture. It confirms three healthy services, 51 delivered employees over two pages, idempotent repeat with zero material changes, token rotation observed by a recreated worker, preserved database container and catalog checksum, safe failures before Compose startup, byte-identical stale configuration and unchanged running containers, and no marker in output, container inspection, exported build context, or image layers. The installed manual stand and real Bitrix were not touched.

Full PR CI and task 3.3 remain pending and are not implied by this Gate 5 approval.

## Required changes

None.
