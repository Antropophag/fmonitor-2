# Assignment-order original Gate 5 storage/parser/boundary RED v2

- Date: `2026-09-05`
- Gate: `2` correction of Gate 3 findings
- RED author: `Codex agent /root/command_gate5_red_storage`
- Supersedes only the sensitivity gaps in v1 evidence; v1 remains immutable.
- Prior RED: `c9714ec25ef51933e5760c97b1a2f35980d26b8d`
- Gate 3 finding record: `df89440627b74ac9f063ca341024e6014f721c01`
- Reviewed production baseline remains: `6c4fb5b70065cabb19adab23f6004714c1f0699a`
- Outcome: `INTENDED RED`.

## Corrected executable identities

```text
10f29221e3576e159b4fed2342591eb45138f9e22352ad4b91851df5f8f5c2dd  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ca7f0f48a84c0b695cccd88fd76a7e01bb58d72aee11bba2603395628d1e1eb1  tests/Support/AssignmentOrderOriginalPdfCorpus.php
a8d6ab6b89ebbc6ea452a42be919d7f705164f0935e58c6a77b83927db8e9b9f  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
```

The production factory oracle now opens an isolated real MariaDB database and
passes a pre-created canonical `0700` private root to the exact public
`ProductionAssignmentOrderOriginalFactory::create(mysqli, config)` signature.
It requires the exact real `AssignmentOrderOriginalService` application owner,
invokes `submitAssignmentOrderOriginal` and requires the stable
`REJECTED/INVALID_COMMAND/false` result through that returned seam. It also
compares the database table inventory before/after construction/use to prohibit
runtime DDL. An absent class, empty class, missing method, wrong return, fake
application owner, unusable command or always-throw implementation fails. The
same public factory is invoked with loose-mode, symlink and unprotected-chain
roots and must fail closed. The existing positive owned-`0600` safe-log path now
writes and verifies one complete line, alongside the negative mode/symlink
cases. Database and filesystem resources remain task-owned and are removed in
`finally`.

The indirect active-content pair now has byte-identical objects. Its Catalog
contains no direct forbidden key: the reachable variant has only the allowed
`/Names 4 0 R` edge, and object 4 contains `/JavaScript 5 0 R`; the control
omits the Catalog edge while leaving the forbidden dictionary physically in the
file. The executable oracle requires reachable=`UNSAFE_PDF` and
unreachable=`PASSIVE_PDF`. Therefore global substring scanning and direct-key
scanning cannot satisfy the pair; bounded latest-object graph reachability is
the deciding signal. Separate escaped-name sensitivity remains independent.

## Fresh RED evidence

```text
$ php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
INTENDED_RED: approved production boundary is incomplete:
production factory: absent
private root no-create: accepted
private root mode: accepted
private root symlink: accepted
private root protected chain: accepted
safe log exact 0600: accepted
safe log symlink: accepted
orphan marker token: accepted
orphan production-root disjointness: accepted
orphan injected clock future: accepted
orphan injected primitive fault: accepted
invalid/fault fixture attempts changed task-owned inventory
orphan collision: accepted
exit 255
```

The isolated database was created and dropped successfully; the failure is the
approved absent production seam and fail-open adapters, not setup.

```text
Independent focused parser control on the same baseline:
reachable indirect active dictionary   => unsafe_pdf
unreachable identical active dictionary => unsafe_pdf
```

Thus the current implementation fails the newly pinned reachability control.
The complete parser suite still first reports its independent valid-`Prev` RED
(`PASSIVE_PDF` expected, `INVALID_PDF` actual, exit `255`). All three corrected
PHP files pass `php -l`; `git diff --check` exits `0`.

No production, approved spec, OpenSpec task or unrelated domain test changed.
Gate 4 remains forbidden until a fresh separately tasked Gate 3 reviewer records
explicit `APPROVED` for these corrected exact artifacts.
