# PILOT-SESSION-STORAGE-001 v9 unknown-route Gate 2 RED v3

- Specification SHA-256: `7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30`
- Session test SHA-256: `315825c95c7ba4059b63e298bf3f710621ff9ffd3e33c57c4982fb43146204d3`
- Companion HTTP test SHA-256: `0c8074ed4548f34fc12e7c3f6a4a30458939f0726caaa78d7b21c3b1b4b1c118`
- Result: intended `404`, actual `503`, exit `255`.

The session test now checks the complete inherited application header set including exact CSP, forbids auth/cookie/location/server headers and rejects unspecified headers. Its replayed cookie proves route priority with an existing anonymous session. Authenticated coverage is independently provided by the companion HTTP test: its `successServer` carries valid `REMOTE_USER`, requests `/pilot/unknown`, requires exact 404, and its injected/environment probes prove zero dependency/environment/auth reads.
