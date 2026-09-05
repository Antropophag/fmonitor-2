# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker ID sequence Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved worker-output base: `563d171800f497587db49164be41f2ee8d35d15f`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

V17 closes exact DSN and failure-output contracts. A valid serialized worker
config still contains two unspecified public strings:

```text
rootIdSequenceCsv
revisionIdSequenceCsv
```

The executable specification defines neither their grammar nor one canonical
example. It does not state:

- whether whitespace, empty input, empty members or trailing commas are valid;
- maximum bytes/member count and delimiter/escape rules;
- opaque ID byte/code-point bounds and forbidden control/path characters;
- whether duplicate IDs are allowed and how exhaustion/collision is encoded;
- how one root versus multiple revision values are consumed;
- fixed exit/channel behavior for an invalid sequence before secret/DB access.

The only occurrences are DTO property declarations. Thus a valid five-FD
worker cannot be constructed independently: `original-0001`,
`original-0001,original-0002`, quoted CSV and JSON-like values are different
public inputs. Choosing one would invent the verification bootstrap contract;
an invalid/pre-secret matrix likewise lacks independently derived boundaries.

```text
$ rg -n "rootIdSequenceCsv|revisionIdSequenceCsv|SequenceCsv" specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md openspec/changes/replace-pilot-registration-with-original-upload/design.md openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:1410: public string $rootIdSequenceCsv,
specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:1411: public string $revisionIdSequenceCsv,
```

Smallest amendment: define literal comma-separated ASCII opaque-ID grammar,
member/count/total bounds, exact valid sequences for identical/different
two-worker scenarios, consumption/exhaustion semantics, and confirm invalid
sequence uses the V17 exit-70 stderr/empty-channel pre-secret contract.

Task 4.1 remains unchecked. Parser/evidence-reader partial RED remains valid but
the required worker/concurrency matrix cannot advance to Gate 3. No production,
test, specification or OpenSpec artifact was edited by this record.

```text
0b875d1a9aab30d8daaa1584f5961747a7f2fecf4e00ce77f6bdba2c5c0dd317  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
020263ca56236fbd510f9cedd940d15e3818402b7c12875e98dd1385a83c9c3f  openspec/changes/replace-pilot-registration-with-original-upload/design.md
bc0acf0b8cfbacc75ffe8f4ba578156c44a41ac6dcd55891d38f6789e06e7800  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
6ca100afde14c46384e2cf2ede6da9c389b028cd3270b473a4bc677f7f6767b9  docs/operations/assignment-order-original-worker-stderr-gate1-gap-2026-09-05.md
530c67f355f123f2de6541192b2c080c30a9f54280cbab3ec085acacabb01bfc  docs/operations/assignment-order-original-command-matrix-worker-dsn-gap-2026-09-05.md
```
