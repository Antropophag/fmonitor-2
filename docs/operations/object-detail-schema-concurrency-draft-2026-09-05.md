# Object-detail schema v0.3 — review correction candidate

Date: 2026-09-05. Author: `/root`.
Responds to independent readiness review `0b5c985` (see full commit ancestry).

The draft keeps concurrency in scope and specifies a database/prefix named
lock, exact acquisition/release/error rules and deterministic two-worker
acceptance. It retains post-both-CREATE verification failure: a separately
constructed verification observer closes its owned DB connection after the
second real CREATE, so final inspection cannot return success. The first-CREATE
partial failure uses restricted table-scoped DDL privileges as recommended by
the reviewer.

This proposed verification composition adds no runtime selector. It has not
received Gate 1 approval and is not permission for tests/production changes.
OpenSpec design/tasks must be reconciled with this candidate and independently
reviewed before RED. The prior verdict remains CHANGES_REQUIRED until a fresh
review explicitly disposes of both findings.

Exact draft SHA-256:
`b97c55a843557d71c60f0c6a2d2f246a848b6e8e78718744802b642be917e257`
(`specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md`).
Diff check exited 0; production/tests/registered migration frontier unchanged.
