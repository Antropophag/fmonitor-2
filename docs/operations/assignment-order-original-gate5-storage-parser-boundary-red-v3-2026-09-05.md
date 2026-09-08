# Assignment-order original Gate 5 storage/parser/boundary RED v3

- Date: `2026-09-05`
- Gate: `2` correction after second Gate 3 review
- RED author: `Codex agent /root/command_gate5_red_storage`
- Supersedes only the production-factory sensitivity and transcript omissions
  of v2; v1/v2 evidence remains immutable.
- Prior correction: `bc1c917b108ab9ade17c2922e37c9739aa8ee6c4`
- Gate 3 finding: `735a903f38dfef8297676c825bc44af21133747b`
- Production baseline: `6c4fb5b70065cabb19adab23f6004714c1f0699a`
- Outcome: `INTENDED RED`.

## Exact executable identities

```text
f47a57ea01f37f6bb30b6a09770f2b22629156f67edca9b68ecb6bfc68131c61  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
10f29221e3576e159b4fed2342591eb45138f9e22352ad4b91851df5f8f5c2dd  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ca7f0f48a84c0b695cccd88fd76a7e01bb58d72aee11bba2603395628d1e1eb1  tests/Support/AssignmentOrderOriginalPdfCorpus.php
```

The corrected positive construction path prepares an isolated real MariaDB
schema exclusively through the approved migrations plus deterministic Example-A
fixture, records its table inventory, and passes a pre-created canonical `0700`
private root to `ProductionAssignmentOrderOriginalFactory::create`. Through the
returned exact `AssignmentOrderOriginalService`, it submits a shape-valid,
authorized INITIAL command with a structurally valid passive PDF and a safely
past explicit document date. It requires exact `ACCEPTED`, request echo,
independently computed SHA-256 and byte size.

After the public command returns, a fresh approved evidence-reader connection
must observe the exact accepted request/digest and private metadata. Independent
filesystem enumeration requires exactly one regular non-symlink file containing
the PDF's exact digest and size. The MariaDB table inventory must remain
byte-identical, proving the runtime factory/command performed no DDL. A service
with stub authorizer, composition, repository, parser, storage or IDs cannot
satisfy these combined durable effects. The prior negative factory/private-root,
positive and negative safe-log, orphan-fixture and cleanup oracles are preserved.

## Exact fresh transcript

```text
$ php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php

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
orphan collision: accepted
exit 255
```

The previously omitted literal `private root no-create: path created` is included
above. The approved schema/fixture setup completed before this intended RED; the
isolated database and filesystem root were removed by `finally`. PHP lint and
`git diff --check` both exit `0`. Parser/storage REDs and their v2 reachability
controls are unchanged. No production, specification, task or unrelated domain
test was edited.

Gate 4 remains forbidden until another separately tasked Gate 3 reviewer records
explicit `APPROVED` for this exact corrected batch.
