# Lifecycle RED v2 — outcome getters and LOCKED closure

Independent Gate3v1 identified missing outcome status()/lease() Throwables and LOCKED outcomes. Added four scenarios: status getter throws before any lease returned (status1/lease0/release0); lease getter throws before returning a lease (status1/lease1/release0); LOCKED+returnedlease requires cleanup then release1; LOCKED+null requires no adoption/release. All full storage-failure Results and exact no-DONE/commit/audit/delivery traces fixed independently.

No production or spec change. Previous v1 evidence/reviews preserved; separate empty-probe patch remains unchanged/unapplied and has its own APPROVED Gate3.

Private archive original-lifecycle-red-61vhs_tf, lifecycle-red-v2.log/evidence-v2.json. 95cases:94failures/1control.

```json
{
  "exit": 255,
  "passes": 1,
  "failures": 94,
  "hashes": {
    "tests/InstallationProcess/assignment_order_original_command_lifecycle_001_test.php": "beec61b817c3234debf85a80f806054e85e9ab4e31b6b03f9a40b03454d313c9",
    "tests/Support/AssignmentOrderOriginalLifecycleFixture.php": "1153ae35c0bf052ffffe498561c9636575111026a3e91e57fea1edc4ed73646e"
  },
  "logSha256": "ecf451034d11807c270431a11dbbb541274ba21c8e6ad183cc60fcf033f5c769"
}
```

Independent Gate3v2 required before GREEN.
