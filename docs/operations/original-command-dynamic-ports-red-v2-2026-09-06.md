# Dynamic ports RED v2 — lifecycle assertion correction

Root caught an overconstraint after Gate3 v1 but before production GREEN: post-stream unavailable forbids later fingerprint/finalize/committed events; it must not require absence of the earlier AFTER_REQUEST_MISS_BEFORE_STREAM required by parent section cleanup transcript. One assertion now filters only the three forbidden later events. Other tests/fixtures/production unchanged.

All33 cases retain24 intended failures and9controls; authoritative rerun in private original-dynamic-red-h47kdud9/red-v2.log. Earlier history unchanged. Independent Gate3 v2 required before GREEN.

```json
{
  "exit": 255,
  "testSha256": "4a83de1d2770ccd8f5c70604a3f678d9e9f24298317f4291e6dcfad8f757df86",
  "logSha256": "300d90d4a749257b16dc45b7667e75ad94a17550f0d5d2eda7b03b64c00fa25a"
}
```
