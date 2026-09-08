# PR41 baseline CI: transfer and RED

{
  "baseCommit": "12c96f674b0960617727dae72993615b61674929",
  "sourceCommit": "9f530017ab769de4e6e1647cadb990281e34c0e9",
  "testAuthor": "agent:/root",
  "tests": [
    {
      "path": "tests/InstallationProcess/pilot_object_list_001_test.php",
      "status": "M",
      "sha256": "752aa4765f35ea2a39b60afa779fa8a72b37898ce0380e10ca9a9d8c33bf055e"
    },
    {
      "path": "tests/InstallationProcess/pilot_shlz_assets_001_test.php",
      "status": "M",
      "sha256": "571f958174a970007884ddec35098502a8455f52e3eb17d7ac268242df120f6b"
    },
    {
      "path": "tests/Support/PilotSafeAuthorizationLog.php",
      "status": "A",
      "sha256": "e6fcbed10b086064228a151fa9ff70b0fecc744bd71f770b43653073f3d90943"
    },
    {
      "path": "tests/Support/SelectedOriginalFixture.php",
      "status": "M",
      "sha256": "920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"
    },
    {
      "path": "tests/Support/ShlzManifestCaptureProbe.php",
      "status": "A",
      "sha256": "abe0ad400bc535a7632277800aa88c65cf3ceda458d8836aa892e2b637f8c501"
    },
    {
      "path": "tests/Support/construction_control_completed_filter_browser.cjs",
      "status": "M",
      "sha256": "bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"
    },
    {
      "path": "tests/Support/shlz_manifest_capture_witness.php",
      "status": "A",
      "sha256": "a87af3e38204c0f38dc1ed2f6ad2842c2a59456c62def8bd4ce7bc1abb09c357"
    },
    {
      "path": "tests/Verification/quality_graph_ci_setup_001_test.php",
      "status": "A",
      "sha256": "3f61386a0fe582f142d3ff6e366f1081a4d339d2eaae559bad31cc90f8e3b4ab"
    }
  ],
  "redCommand": "php tests/Verification/quality_graph_ci_setup_001_test.php",
  "redExit": 255,
  "logSha256": "308d6c70d78416a80f2affc66541c477f578e6b7128276f631e7f8f4c1b62f11"
}

Actual missing-rg RED reproduced before Make/setup implementation. Tests remain exact source9f53001; previous Gate3v8/Gate5v5 and Linux PASS are reused as historical proof, not claimed as PR41 CI. Independent transfer review pending.
