# BATCHED-SCHEMA-SNAPSHOT-001 — independent Gate 5 code review

**Verdict: APPROVED**

## Scope and independence

- Review date: `2026-09-08`
- Reviewer: separately tasked Codex agent `/root/pr10_code_review`
- Independence: the reviewer authored none of the proposal, test, Gate 3 review,
  implementation, benchmark, or equivalence tooling.
- Reviewed commit: `287ebf6031b889fc63d718e03b6944bef81e0b54`
- Fixed point and merge base: `origin/main` at
  `102e6056d7e59f9ff32506cb89d9647234d4c588`
- Reviewed comparison: `git diff origin/main...HEAD`
- Superseding Gate 3: `reviews/tests/BATCHED-SCHEMA-SNAPSHOT-001.md` —
  `APPROVED`, including the companion inventory correction approval.

This review is limited to the test-only schema-snapshot batching slice. It does
not approve caching, shared databases, removed assertions, CI sharding, production
runtime changes, or completion of the remaining issue55 work. The untracked
`tests/InstallationProcess/issue55_snapshot_profile.php` equivalence tool is not
part of the reviewed commit or this approval.

## Exact reviewed hashes

```text
ce960bce5f0acecc0f98a47849890e385a6f2a6075b29f1017e1c9531fc00a4d  tests/Support/BatchedSchemaSnapshot.php
2837833894f26b10e440ab435c1979f5bfe673256d487fb894a5370d8ebc26bc  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ad6055e31cc8c66ecaf7e94eee0d0d123d92ba45e9b76acc24fe1849505216b2  tests/Verification/batched_schema_snapshot_001_test.php
99f7e7b2263eb81bad5f40e27b0716bfabd251a93fe65bbf14c178b6ea3cb947  tests/Verification/verification_inventory_001_test.py
12c7ce3beeb53be900ca5ead1bb454370f16866d5efe96ab7e560500703675e1  tools/verification/suites.tsv
39a2cc1a42e1e3032eed3f35580b8d7566d8c7aa609a191d37d82715ab511b4e  tools/verification/categories.json
28540384cb939484c176c2dabc2b1a256e9188450e2b36a93cbd07d9c92d0940  openspec/changes/optimize-integration-fixture-prerequisites/proposal.md
3f625f729f14f29cdef6921e7f0ad4ac5673276b807716fd24cfc23da17e72ec  openspec/changes/optimize-integration-fixture-prerequisites/design.md
272f14c68c31634c5ed5dc74ddac418464406c661e356fecc1d420151e7bc402  openspec/changes/optimize-integration-fixture-prerequisites/tasks.md
6fca14849ba170fdfad4993d4076333e1f80c632f879448d8b47334e55e85db8  reviews/tests/BATCHED-SCHEMA-SNAPSHOT-001.md
```

## Findings

No blocking findings.

`BatchedSchemaSnapshot::read()` preserves the legacy observer's complete return
shape and normalization. It returns tables in binary name order; each table keeps
the same `table`, `columns`, `keys`, `foreignKeys`, and `checks` fields. Column
order remains ordinal, `COLUMN_TYPE` remains lower-case, nullable SQL `NULL`
defaults retain the prior PHP-null correction, index kinds and composite-column
order are unchanged, foreign-key rows retain update/delete rules, and CHECK
clauses still pass through the caller-owned normalizer before sorting.

The implementation performs exactly five database-wide metadata queries on every
call: tables/properties, columns, indexes, foreign keys, and CHECK constraints.
There is no static or cross-call cache. Including both `BINARY TABLE_NAME` and
`TABLE_NAME` in the index grouping preserves distinct case-sensitive table names
without changing the grouped index shape. Per-table arrays are sorted where the
old closures sorted them, so database result order is not accidentally promoted
into the oracle.

The heavy database-setup test changes only its schema snapshot closure to call the
helper with the existing boolean CHECK normalizer. Its separate table inventory,
fresh `SELECT *` row reads, row normalization, state serialization, mutation
matrix, isolation checks, and assertions are unchanged. The slice therefore
reduces repeated metadata traffic without weakening data, schema, or history
preservation observations.

The independently approved literal regression covers every returned metadata
family and the important normalization distinctions. Repeated reads after table
addition/removal, column and index addition, foreign-key and CHECK removal, and
table collation conversion make caching or stale family reads fail. The retained
parent-table assertion also detects incorrect association across tables. Its
random bounded database name and nested cleanup keep the verifier isolated.

The inventory change registers exactly the new verifier in the DB suite and
integration category while retaining the historical baseline digest. No
production source, product contract, runtime configuration, CI topology, or
publisher behavior changes. The small test-support class centralizes an expensive
observer with a clear interface and introduces no material maintainability smell.

## Verification evidence

Fresh reviewer execution at exact commit
`287ebf6031b889fc63d718e03b6944bef81e0b54`:

```text
$ FMONITOR_TEST_DB_PORT=23365 php tests/Verification/batched_schema_snapshot_001_test.php
BATCHED_SCHEMA_SNAPSHOT_001_OK

$ python3 tests/Verification/verification_inventory_001_test.py
Ran 15 tests in 5.169s
OK

$ php -l tests/Support/BatchedSchemaSnapshot.php
No syntax errors detected in tests/Support/BatchedSchemaSnapshot.php

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ php -l tests/Verification/batched_schema_snapshot_001_test.php
No syntax errors detected in tests/Verification/batched_schema_snapshot_001_test.php

$ git diff --check origin/main...HEAD
# exit 0, no output
```

Reviewed delivery evidence records three passing baseline runs with median
`82.736s` and three passing after runs with median `13.350s`, an observed local
reduction of `69.386s` (`83.9%`) for the heavy test. It also records byte-identical
old/new schema snapshots for the first 502 real-matrix comparisons before the
binary-grouping refinement. Final exact-helper equivalence is being executed
separately by the parent against owned disposable databases; this review does not
substitute the earlier partial comparison for that final result. The parent also
reported architecture `7/7`, strict OpenSpec, inventory `15`, and CI-matrix `9`
checks passing. Full authoritative CI remains to follow and must be reported from
its actual result.

## Gate consequence

Gate 5 is **APPROVED** for `BATCHED-SCHEMA-SNAPSHOT-001` at exact commit
`287ebf6031b889fc63d718e03b6944bef81e0b54`. The implementation preserves the
approved schema observer while replacing per-table metadata queries with five
fresh batched reads; the approved regression and inventory controls are green,
and no blocking issue prevents the slice from proceeding to final equivalence and
authoritative CI.
