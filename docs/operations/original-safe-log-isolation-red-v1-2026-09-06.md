# Original diagnostic isolation RED v1

Gate1 approved exact spec by independent review. Production unchanged.
Public verification application fails all13 cases: selected rejected/accepted/conflict results become failed/persistence_failure; useRequest Throwable escapes before lifecycle; failed audit sentinel shows duplicate diagnostic and abort, and missing required audit. No environment/OS/DB fixture required.

Private archive: `/Users/antropophag/.local/state/fmonitor2-verification/safe-log-isolation-red-yuh3tnwu`.

```json
{
  "baseHead": "f63a9f57d8cdde721fabaa8ceacaaabe8ec0eefa",
  "exit": 255,
  "sha256": {
    "specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-ISOLATION-001.md": "ef245ec06ad9ed0a7d9c365386077a22be5547eb625ba276ba4f603d7b310942",
    "tests/InstallationProcess/assignment_order_original_safe_log_isolation_001_test.php": "e39895f27c9041d14d85e6f9d51ac1f411ef4e7c7178c165756dfbb66a2a2056",
    "tests/Support/AssignmentOrderOriginalLogIsolationFixture.php": "20cdecb500b01c721c90b7fb4fb95878f4b612fe0e73f850850da452b4fb18df",
    "app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php": "569c792d7d48ba7842281881362693d0b3bf40e83cf66a045fa925705ba1279f"
  },
  "logSha256": "9a25785146dfb36ea24e2b35d316acb9bf79c8333a1356f1e44fe98632e6fc42"
}
```

Independent Gate3 required before implementation.
