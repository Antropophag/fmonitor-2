# YII2-STAND-TARGET-COMPOSE-001 — exact target и Yii2 Compose

## Простыми словами

Перед опасными операциями FMonitor получает однозначное описание стенда и единый
Yii2 Compose. Этот срез ничего не останавливает и не удаляет.

## 1. Seam

Actor — deployment operator. Public seams: `python3 tools/delivery/validate-stand-target.py <manifest.json>` и parsed `deploy/runtime/compose.yaml`. Source oracle — issue #76 и принятые Yii2 runtime/jobs/image contracts. Validator read-only: не вызывает Docker/DB, не пишет files и не читает secret values.

## 2. Exact manifest

Version 1 MUST содержать authorization id `owner-2026-09-13-issue-76-stand-reset`, absolute canonical compose path, non-default project, exact unique services `db`, `prepare`, `migrate`, `php`, `web`, `jobs-worker`, `jobs-scheduler`, exact database, unique named database/state/secrets volumes, immutable current/candidate `name@sha256:<64 hex>` images, absolute evidence root вне repository/home root и observed project/volume IDs.

Missing/extra keys, wrong types/version/auth, non-canonical compose path, relative or `/`/home/repository/symlink evidence root, mutable/missing digest, unresolved `${...}` anywhere, duplicate/unknown service/volume, project `default`, or missing/empty/non-unique observed IDs MUST return exit 64 and exact `{"ok":false,"reason":"TARGET_INVALID"}\n`, empty stderr, without reflecting rejected input. Freshness and comparison of IDs with Docker observations belong to the later control plane. Valid input returns exit 0 and exact canonical JSON with `digest`, `ok`, `outcome` plus newline; digest is SHA-256 of the accepted manifest serialized as UTF-8 JSON with sorted keys and separators `,`/`:`, independent of input key order/whitespace, locale, cwd and ambient environment.

## 3. Canonical Compose

`tools/delivery/compose.runtime.yaml.in` is canonical and generated `deploy/runtime/compose.yaml` MUST be byte-identical. Parsed services preserve accepted `PRODUCTION-HTTP-RUNTIME-001` topology: `db`, `prepare`, `migrate`, `php`, `web`, `jobs-worker`, `jobs-scheduler`. All application services use one image. `migrate` runs exact `php bin/yii schema-migrate/run --interactive=0`; jobs run exact `php bin/yii jobs/worker|scheduler --interactive=0`. `php` and jobs depend on successful migration; web depends on healthy php.

DML/migration principals, protected storage/secrets and volume wiring remain governed by and executable-tested in `tests/Runtime/production_runtime_compose_001_test.php`; this slice does not redefine them. No production command or entrypoint references `rapid-pilot`.

## 4. Rejections and Done

Invalid manifest or Compose topology is failure, never UNKNOWN/GREEN. Sрез performs no DDL/DML/domain/audit/deployment facts; replay is naturally idempotent. RED fails on missing validator/template and old topology. Gate 3 precedes executor; focused checks, Gate 5 and one exact-source CI precede merge. Backup/journal/reset/rollback/live stand remain separate.
