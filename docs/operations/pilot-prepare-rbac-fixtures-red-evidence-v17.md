# PILOT-PREPARE-RBAC-FIXTURES-001 — Gate 2 sensitivity evidence v17

Дата: 2026-09-04  
Автор: `/root/prepare_v15_red`  
Base review: `c93e917b286f921fc5075b8c4d0390d066a3c752`  
Verdict: **QUALIFYING CURRENT RED / fresh Gate 3 required**

Mixed-provenance client oracle теперь проверяет обе source rows: exactly two,
ordered LI, exact three attrs/IDs/text; оба dynamic results получают exact
associated provenance third child after name/details. Independent atomic
mutation cases cover missing/extra/misordered rows, extra/missing attrs, wrong
tag, descendant, forbidden interstitial/text, wrong second ID/source/update/
text and cardinality; каждый требует hidden opener, visible fallback/source
list and zero hidden IDs. Prior v16 server/catalog and all earlier assertions
preserved. Production untouched; task 2.2 open.

Current head run `2026-09-04T11:02:09+03:00`–`11:02:12+03:00` gives genuine
new RED:

```text
Error: malformed provenance association 4 stays atomically fail closed:
expected [true,false,false,0], actual [false,true,true,0]
```

Case 4 is forbidden interstitial source-list text, currently ignored instead
of rejecting initialization. Direct exit 1, canonical exit 255.

Detached `~/code/fmonitor-2-prepare-v17-red` at exact `02e16bb` with tests-only
diff remains RED at earlier missing mixed-provenance initialization
(`expected hidden true, actual false`), direct 1/canonical 255,
`2026-09-04T11:02:47+03:00`–`11:02:50+03:00`. Patch reverse-applied, clean
verified, worktree removed/pruned and patch deleted.

Syntax, strict OpenSpec and diff checks passed.

```text
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
fae262571db508b02175a6c2f52cd67e8867b15b9ad7a572da05e2888f3c7ec8  tests/InstallationProcess/support/pilot_prepare_picker_client.js
00e7265ea0d1d16dd50b4590cccf1358d8c99c5ce4b9d0448f108ba0c8ad5546  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
```

Fresh Gate 3 is separately tasked; this is not self-review.
