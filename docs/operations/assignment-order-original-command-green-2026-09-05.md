# Assignment-order original command GREEN evidence — 2026-09-05

Gate 4 follows approved command-matrix Gate 3
`2907b4e7436b260c92c0c9aa4ef5e415728494cf`, schema-v2 Gate 5
`a54446f294e29362103e23d38c34823b06b5b1be`, canonical lease correction
Gate 3 `c6938640dbac7f927e26123a1361274ea5d8ef74` plus hash correction
`75037bd4210561aac78b509c0c3e90b488c39dbd`, and worker cleanup Gate 3
`455372a42fa6489aa2809e8b651618ed2cb4c14a`.

The implementation owns state changes in one command seam, keeps private leases
through post-finalize rereads, reconciles commit conflicts and unknown outcomes,
releases each lease through an explicit phase, and classifies referenced
maintenance retention as completed. SQL lives in explicit `MariaDb*` owners and
filesystem operations in `AssignmentOrderOriginalFileStorage`.

The complete approved nine-suite command matrix passed after the final boundary
split with these literal terminal lines:

```text
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
ASSIGNMENT_ORDER_ORIGINAL_EVIDENCE_READER_OK
ASSIGNMENT_ORDER_ORIGINAL_MAINTENANCE_OK
ASSIGNMENT_ORDER_ORIGINAL_WORKER_TRANSPORT_OK
ASSIGNMENT_ORDER_ORIGINAL_WORKER_PROTOCOL_OK
ASSIGNMENT_ORDER_ORIGINAL_LEASE_RACE_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK
```

The worker suite emitted no cleanup warning and left no new random temp root.
Additional checks:

```text
ARCHITECTURE CHECK PASSED (7 rules)
Ran 22 tests in 5.258s — OK
ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_V2_001_OK
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK
make lint: exit 0
git diff --check: exit 0
```

Key exact SHA-256 values before the implementation commit:

```text
5d1ea6c691916b0d84427f7202caf078542ec4dce9a0468dd22e868af6b9f656  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
898381a7aff748fd8238b24efd8e7dd327a14fba2202c62cb7dd6db718d86f6c  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
9025003a0fa62ba189a8b2ec07ac9bc6a9980e32fc97410515622f625abe56de  app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php
08906719e8a5c708df1d330559fba8c94ea0af70ab63a9bdf746d344c830112c  app/AssignmentOrderOriginal/MariaDbMaintenanceService.php
32aef5d7b051a8335a235cb6de10c137276c37055b4be9db60d1cc3348765366  app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php
9592b2be288815d7040bfb65cdf33ae0d0b2f49cc38a0cb420d1ec05e4c6f82e  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
83b584d3f4dd4048059d22a893e634cae0b49008b1e47c9f254e271a69212dcc  tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
64c1bfbed11df12ab6078e64ea5db71a970b5696403840375db945e528de86b8  tools/architecture/check.py
```

Fresh independent Gate 5 remains required on the exact implementation commit.
