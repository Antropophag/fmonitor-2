# Protected PILOT-E2E-FLOW-001 blocker — planning audit

- Recorded: `2026-09-05T13:50:49Z`
- Auditor: `/root/importer_authority_review`, read-only planning scope
- Current HEAD: `f594c4fa62b6e02fb85915c27242c11a7bccdc82`
- Evidence log: `/tmp/fmonitor2-verify-d1a5d09-prepared.log`
- Disposition: **RBAC GATE 1 ALREADY APPROVED; OLD E2E v0.5 APPROVAL PACKAGE STALE**

No protected specification, test, dependency or production file was edited or
executed for this audit.

## Current failure

The prepared verification log records twice:

```text
actor18 admission reaches one exact canonical object link
Expected: 1
Actual: 0
tests/InstallationProcess/pilot_e2e_flow_001_test.php:153
```

The immediately preceding assertion requires the same response status to be
200. Therefore this exact failure is after local actor admission, during list
membership/DOM evidence. It is not evidence that actor 18 lacks
`objects.read`, and it is not yet a PDF, original-upload, registration or
opening failure. The assertion label alone must not reclassify zero links as an
authorization denial.

## RBAC fixture authority is complete through Gate 3

`PILOT-E2E-RBAC-FIXTURES-001` v2 is not awaiting owner approval. The owner
approved its exact amended hash batch in
`grill-009-rbac-exact-hash-approval-2026-09-02.md`. Tasks 1.1–1.3 and 2.1–2.2
are recorded complete, and `reviews/tests/PILOT-E2E-RBAC-FIXTURES-001-v6.md`
approves the exact protected test hash
`a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6`.

The remaining RBAC work is Gate 4/5, not another owner question:

1. diagnose why the approved actor-18 200 list projection contains no exact
   object 4512 link under current fixture data and public list reader;
2. implement only the already approved deterministic canonical local role/user/
   grant seed and trusted actor propagation or fixture/projection prerequisite
   actually exposed by that diagnosis;
3. preserve actor-19 legacy-only denial, isolated revoke/repeat, full snapshots,
   cleanup and all downstream assertions;
4. obtain focused GREEN, architecture/full verification as applicable and fresh
   independent Gate 5.

No protected test edit is authorized merely because the assertion currently
fails. If diagnosis shows the approved expected membership itself is wrong,
return to exact Gate 1 rather than weakening it.

## Prepared E2E v0.5 package awaiting approval

The historical package marked `READY_FOR_OWNER_APPROVAL` is the joint
`PILOT-E2E-FLOW-001` v0.5 plus `PILOT-E2E-COMBINED-PDF-001` batch reviewed in
`pilot-e2e-combined-pdf-gate1-rereview.md`. Its reviewed hashes were:

```text
c792b7bd3c707b0b9bd4fe2e934c677d44235ce2da41839688383391d47f3ec5  specs/PILOT-E2E-FLOW-001.md
a28c7a8bfeabdf9f41bc05ac4f17faa22c3ef2956c62573323f83e9dc809ebd3  specs/PILOT-E2E-COMBINED-PDF-001.md
0355a370bdc95e99b178756edfe96c8badc04291e0daf5bed4fed0e910b5ce2a  combined proposal
33329327e0322a5f0875231c36ccbbcf980ff6c3d5d063af20bb4317bdf80102  combined design
fd40dd591a4ee194a3c4d871a949398dec4cef1c05fcd309a38b2226f2756b07  combined tasks
db27c4fc023c9e10850fc056fc0fb9363aa985bf32e5a3016ebd2eada91d3c8f  combined delta spec
```

That package must not be presented to the owner now:

- current `PILOT-E2E-FLOW-001.md` hash is different and explicitly says its
  manual registration route/number and `registered` gate are legacy;
- current combined tasks and delta-spec hashes also differ from the reviewed
  batch, so the old review cannot approve current bytes;
- more fundamentally, v0.5 only supersedes the two-HTML artifact contract with
  one combined PDF. Its journey still says combined PDF → manual 1C registration
  number → opening, while current owner-approved product truth requires optional
  template or direct signed-original upload, no manual registration number, and
  a separate explicit opening action after accepted original.

The combined-PDF behavior may remain useful as one artifact requirement, but it
is not a coherent controlling E2E journey under current truth. Approval cannot
be inferred from the old readiness review or requested as the old six-hash
batch.

## Current public-route reality

Current route admission still recognizes legacy registration and opening paths,
and also recognizes artifact type `signed_original`. The coordinator contains
legacy/manual registration behavior while the current prepare view exposes an
original-upload form. This is an incomplete transition, not authority to retain
manual registration in the target E2E contract. Public original HTTP,
composition application and opening replacement remain separately gated as the
handoff records.

## Precise approval package still needed

Do not broadly re-ask policies already approved. After prerequisite contracts
land or become exact enough to compose, prepare one coherent protected E2E
amendment that:

1. replaces manual registration steps/routes/statuses with optional template or
   direct signed-original upload and no manual number;
2. preserves separate opening with mandatory actual date and proves upload does
   not open work or apply composition by itself;
3. cites exact public seams from the approved original HTTP, composition and
   opening slices rather than inventing route/result behavior;
4. carries forward the already approved actor-18/actor-19 local-list RBAC
   boundary without reopening it;
5. reconciles combined-PDF download only where it remains consistent with the
   accepted original workflow and exact current artifact routes;
6. supersedes every conflicting legacy section explicitly, updates the
   protected executable test only after fresh technical review plus explicit
   owner exact-hash approval, and then repeats RED/Gate 3.

Immediate prerequisites are: resolve the current zero-link fixture/projection
failure under the already approved RBAC Gate 4; complete safe-log/full original-
command Gate 5; obtain exact approved original HTTP contract/implementation;
complete composition selection/application and opening-from-original contracts;
and align TEST-USER/bootstrap data with the same journey. Until those exist,
exact E2E route/result assertions would be speculative.

The only eventual owner action is approval of that new coherent exact protected
E2E hash batch. The owner need not decide again that manual numbering is absent,
that signed-original upload does not open work, that opening is separate, or
that actor 18 uses exact `objects.read`.

## Exact current hashes

```text
c97bcb3df97362a19efc9dda6ab6ac2a8224fa0d0a3a42721d7eca8b511e3cfd  specs/PILOT-E2E-FLOW-001.md
147227bde8b9afe126ee374417a9c7f5a3bac84c5e13b10d7dc1b1d9a525ee1f  specs/PILOT-E2E-RBAC-FIXTURES-001.md
78fbdd1b453009ab1e9a85a59e2a382dd2c2b5bfc5c0c405c6d53c41ef404c96  RBAC proposal
848e238b73b9120cfca4884e54ee827e725f130326207d711662a527574777a1  RBAC design
df581ab9c2bfa986ca0b5293c7c50e9be01c66f4088bdaf7869a8104f5a1f967  RBAC tasks
fdb2db734e01ea292504ae76d18f9e503e83c34bee352c1503905dadaef3e4b6  RBAC delta spec
a28c7a8bfeabdf9f41bc05ac4f17faa22c3ef2956c62573323f83e9dc809ebd3  specs/PILOT-E2E-COMBINED-PDF-001.md
0355a370bdc95e99b178756edfe96c8badc04291e0daf5bed4fed0e910b5ce2a  combined proposal
33329327e0322a5f0875231c36ccbbcf980ff6c3d5d063af20bb4317bdf80102  combined design
14d4c62f923e856b77db8b1dfcbae874b70a382f8034e519aea020965e0cd2ef  combined tasks
aeabde502b78c63acea3c9fd771749a08951fde26643b485909307b1d0ffaef6  combined delta spec
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
2e8ca8e3db80d0e4b4b214eff9c93ef291fcf305f53b02c0904b6de733a91a7e  docs/operations/grill-009-rbac-exact-hash-approval-2026-09-02.md
bb5b79347afd9cbba5c57c1e61d60282100ebcc36d0bfbfd0343092200a56c57  reviews/tests/PILOT-E2E-RBAC-FIXTURES-001-v6.md
f5ab9e710c1b881d6f6369999c22d52199cc2064a41b8a85ef261f40c73ad623  /tmp/fmonitor2-verify-d1a5d09-prepared.log
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
f6491662738821743976e06086bcb988269c78a4b3d87b9899df4f65575b30b0  app/PilotHttp/PilotE2ECoordinator.php
```

## Final disposition

The actor-18 failure proceeds under already approved RBAC Gate 4 and needs no
new owner approval unless expected list membership must change. The old joint
v0.5/combined-PDF approval package is stale and conflicts with current
original-first truth; do not submit it. Build a fresh exact protected E2E package
only after its named original HTTP/composition/opening prerequisites are exact,
then request one narrow owner approval for those new bytes.
