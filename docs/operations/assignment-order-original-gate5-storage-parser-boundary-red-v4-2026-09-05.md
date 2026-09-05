# Assignment-order original Gate 5 storage/parser/boundary RED v4

- Date: `2026-09-05`
- Gate: `2` evidence-only correction after third Gate 3 review
- RED author: `Codex agent /root/command_gate5_red_storage`
- Gate 3 finding: `d900b712`
- Corrected RED commit under review: `3590010cb253cf4b0e09a5c0f2382bf1e8d881cb`
- Executable test SHA-256: `f47a57ea01f37f6bb30b6a09770f2b22629156f67edca9b68ecb6bfc68131c61`
- Production baseline: `6c4fb5b70065cabb19adab23f6004714c1f0699a`
- Outcome: `INTENDED RED`.

This record corrects only the inaccurate `Exact fresh transcript` label in v3.
No prior evidence was edited. The following command captured the two process
channels separately and wrote the decimal exit status with one final LF:

```sh
php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php > /tmp/aoou-boundary-v4.stdout 2> /tmp/aoou-boundary-v4.stderr
printf '%s\n' "$?" > /tmp/aoou-boundary-v4.exit
```

Exact capture identities:

```text
9f2b05a837f06e93c6767fb3dba97389628ceccbbd70aca8a56aab0b45c70f0e  stdout (882 bytes)
377653a12f2d24485c2a169578940c26895c47191d2f5a936430de34946ab3b6  stderr (886 bytes)
ce8bafb38615aeb5d44ebbabe78ec14ac35a5de87bdc5ad5ea82a72656024ce4  exit-status file (4 bytes, exact bytes `255\n`)
```

## Complete literal stdout

The block begins with the actual leading LF and ends with the actual final LF.

```text

Fatal error: Uncaught TestFailure: INTENDED_RED: approved production boundary is incomplete:
production factory: absent
private root no-create: accepted
private root no-create: path created
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
orphan collision: accepted in /Users/antropophag/code/fmonitor-2/tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php:29
Stack trace:
#0 {main}
  thrown in /Users/antropophag/code/fmonitor-2/tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php on line 29
```

## Complete literal stderr

The block has no leading blank line and ends with the actual final LF.

```text
PHP Fatal error:  Uncaught TestFailure: INTENDED_RED: approved production boundary is incomplete:
production factory: absent
private root no-create: accepted
private root no-create: path created
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
orphan collision: accepted in /Users/antropophag/code/fmonitor-2/tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php:29
Stack trace:
#0 {main}
  thrown in /Users/antropophag/code/fmonitor-2/tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php on line 29
```

## Complete literal exit-status file

```text
255
```

This evidence-only correction adds no test, production, specification or task
change. The isolated MariaDB database and filesystem control root were absent
after the captured run, as enforced by the executable `finally` cleanup.
