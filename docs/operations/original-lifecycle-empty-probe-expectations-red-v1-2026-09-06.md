# Empty-probe expectations — unapplied candidate RED

COMMAND-LIFECYCLE-001 Gate1 explicitly removes the nonnormative empty-fingerprint probe. Only three assertions in two unprotected tests change: exact real fingerprint list, no fingerprint before invalid clock, and terminal0reads versus actual fingerprint2reads. Every result/authority/fact/cleanup assertion remains unchanged.

Patch `docs/operations/original-lifecycle-empty-probe-expectations-v1-2026-09-06.patch`, SHA256 `478c3ece041c89affa79f780be6e3920037e2865fcd31bce4a98f2b0baf7a3c8`, remains UNAPPLIED in worktree.
Candidate tests run in task-private copy with identical committed Original PHP source plus exact needed synthetic helpers/bootstrap. Source manifest and byte hashes are in archive `/Users/antropophag/.local/state/fmonitor2-verification/original-lifecycle-red-61vhs_tf`; no source instrumentation, substitutions, environment failure or private app-method calls. Both candidates fail with exit255 on fresh original source: dynamic tests see the extra empty fingerprint, domain test sees0reads where true fingerprint step requires2.

```json
{
  "baseHead": "3f5aa18cedc9bf71e43a16ed29a39da9ad7c1ca1",
  "patchSha256": "478c3ece041c89affa79f780be6e3920037e2865fcd31bce4a98f2b0baf7a3c8",
  "runs": [
    {
      "test": "tests/InstallationProcess/assignment_order_original_dynamic_ports_001_test.php",
      "beforeSha256": "4a83de1d2770ccd8f5c70604a3f678d9e9f24298317f4291e6dcfad8f757df86",
      "candidateSha256": "e3f7d4af232e579b24b71dc5074a9aaa00e71b3d8ee317cf3f41c9ab0cadace9",
      "exit": 255,
      "logSha256": "a6b9031df433bd11a14becadce6fd6e9f3501c843f0f75e8e97af6c06ea8a79f",
      "failureLabels": [
        "FAIL post-stream-fingerprint-unavailable: only the exact real post-stream fingerprint is queried",
        "FAIL clock-missing-Z: invalid clock precedes every fingerprint lookup",
        "FAIL clock-space: invalid clock precedes every fingerprint lookup",
        "FAIL clock-fraction: invalid clock precedes every fingerprint lookup",
        "FAIL clock-offset: invalid clock precedes every fingerprint lookup",
        "FAIL clock-calendar: invalid clock precedes every fingerprint lookup",
        "FAIL clock-hour: invalid clock precedes every fingerprint lookup",
        "FAIL clock-leap-second: invalid clock precedes every fingerprint lookup",
        "FAIL clock-throw: invalid clock precedes every fingerprint lookup"
      ]
    },
    {
      "test": "tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php",
      "beforeSha256": "f788e80143c25cda53fb02d79a4089248ce6079fcf1586b6aeb65b53d5ba6486",
      "candidateSha256": "66f334a9cf27897164c4c5bf42a58b6228c1336f01feeb8076fd8c8151bebe79",
      "exit": 255,
      "logSha256": "be4d221d01de8290c34c7d1a4eddbe07337ef6617ee8a97a182777df73e0cd32",
      "failureLabels": []
    }
  ]
}
```

Independent patch Gate3 required before application. This is not the protected E2E patch and changes none of its bytes.
