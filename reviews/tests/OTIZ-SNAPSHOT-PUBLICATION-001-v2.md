# OTIZ-SNAPSHOT-PUBLICATION-001 — independent Gate 3 rereview v2

- Date: `2026-09-08`
- Reviewer: separately tasked agent `/root/otiz_review`
- Reviewed commit: `b9b77e0da55c8089e9d7412492e4eb3768903c04`
- Scope: concurrency additions and corrected runtime-schema RED following v1
- Verdict: **APPROVED for the bounded A01 atomic-publication implementation loop; complete Gate 3 remains CHANGES_REQUESTED**

The added input-cut case is sensitive to the agreed constructor invariant. Its
input closure reads through the same `mysqli` connection passed to
`MariaDbSnapshotStore`, a second connection updates the source row between two
reads, and the expected published amount requires the original value. It will
therefore reject a store that fails to establish `REPEATABLE READ` plus a
consistent snapshot before invoking the closure. Production composition must
preserve that same-connection invariant for `NativeOperationalPremiumInputs`.

The two subprocess build workers overlap and use the same actor, date and
operation UUID. They require one returned identity and one publication event.
The two acceptance workers require exactly `OK` plus `IMMUTABLE` and one
acceptance event. These are bounded, relevant concurrency checks and satisfy
the previously missing replay/acceptance race shape without expanding into a
rare-case matrix.

The runtime-schema test correction is sound. The populated legacy evidence
family is now created from literal historical DDL, independently of production
`ensureSchema`, so changing runtime setup to readiness-only cannot invalidate
the fixture. The receipt fixture now uses the normative
`otiz-publication-v1`. Syntax and diff checks pass, and its intended RED remains
the absent canonical v20 migration.

The valid HTTP A01 RED and absent application-class RED recorded in v1 remain
the implementation basis. Production implementation may now begin for the
single transaction, rollback, replay serialization, complete-publication
receipt and incomplete-acceptance guard. This is deliberately a bounded
approval so the priority slice can progress while the remaining ordinary
oracles are completed independently.

Complete Gate 3 still requires:

1. Assert receipt publication metadata, exact manifest version/golden digest,
   and reject same-count stored-content corruption.
2. Execute complete-draft `BLOCKERS`, plus denied `read` and `history`.
3. After each injected build failure, assert zero orphan snapshot/object/
   allocation/issue/event/receipt rows, rather than relying only on `history()`.

## Exact rereviewed hashes

```text
79c79992f4a3b53a05da7a6255a83f0bd1028da6a321cfa1e987863aabdb05ec  tests/Otiz/snapshot_publication_001_test.php
4b4b92e3377f945049c4f81c9be152cc0e5eb49bc4f92cc13835ec152059ae61  tests/Support/OtizPublicationWorker.php
11bc7c8e080608b112acb751e814d5ee866c3d388315f31467cf48c2ec545156  tests/Otiz/runtime_schema_001_test.php
```

Task 1.4 remains unchecked until a later append-only rereview approves the
complete focused contract. This v2 verdict authorizes the bounded production
work above and does not establish GREEN, Gate 5, full CI, or readiness.
