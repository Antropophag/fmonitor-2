# Rapid auth hot-path — constructor verifier correction

- Date: `2026-09-04`
- Gate: `2`, verifier-only integration correction
- Production changes: none

`RapidPilotLocalAuth` now receives the canonical session owner through an
optional typed constructor parameter. The hot-path verifier still searched for
the obsolete literal zero-argument signature and failed with
`LocalAuth constructor unavailable` before inspecting the constructor body.

The locator now matches the stable PHP declaration prefix
`public function __construct(`. Its security oracle is unchanged: the complete
source slice up to `handle()` must contain no DDL, bulk INSERT or
`ensureSchema`, and no private request-reachable schema bootstrap may exist.
Identity bootstrap/migration, password, worker, upload-limit and queue checks
remain byte-identical.

The corrected verifier must pass current production and remain sensitive to a
constructor-body `CREATE TABLE`, `INSERT INTO` or `ensureSchema` mutation. A
fresh independent Gate 3 review is required before treating the
characterization stage as repaired.

Fresh execution:

```text
$ php rapid-pilot/verify-auth-hot-path.php
PASS auth hot path is schema-mutation free

$ php -l rapid-pilot/verify-auth-hot-path.php
No syntax errors detected in rapid-pilot/verify-auth-hot-path.php

# temporary constructor-body comment containing CREATE TABLE
$ php rapid-pilot/verify-auth-hot-path.php
Fatal error: request-time auth constructor contains DDL
exit 255
```

The temporary production sensitivity probe was removed immediately; production
bytes are unchanged.
