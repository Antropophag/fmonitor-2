## Context

См. `proposal.md`. Existing `deploy/runtime/compose.yaml` уже имеет isolated runtime evidence, но service naming/template и отдельный exact target contract не поставлены.

## Goals / Non-Goals

**Goals:** pure read-only validator; canonical parsed Compose; deterministic safe output; no rapid-pilot runtime dependency.

**Non-Goals:** backup, lease/journal, migrations execution orchestration, reset, rollback, stand access или deployment.

## Decisions

1. Validator — отдельный Python CLI без Docker/DB calls; canonical JSON digest является входом будущего control plane.
2. Compose template — source owner, rendered file генерируется существующим dependency renderer и проверяется byte-for-byte.
3. Parsed-config test проверяет exact services, explicit shared image без mutable default, entrypoints и successful-migration dependencies; deployment manifest отдельно требует immutable digest. Existing runtime Compose test сохраняет principals, secret files, volumes и executable adjacency.
4. Persistence owner отсутствует: срез read-only и не выполняет DDL/DML.

## Risks / Trade-offs

- [Manifest устареет до deployment] → следующий control plane повторно сверит observed IDs; этот срез не заявляет freshness.
- [Compose syntax проходит, runtime нет] → сохраняется existing isolated executable Compose test.
