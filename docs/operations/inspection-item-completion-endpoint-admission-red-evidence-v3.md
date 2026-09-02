# INSPECTION-ITEM-COMPLETE-001 endpoint admission RED evidence v3

Date: 2026-09-01

The no-mutation raw-HTTP oracle now uses an otherwise valid item completion
whose sole malformed field is literal `deviceInstallationId=not-a-uuid`.
Before asserting, it collects the public checklist page, independently parsed
CSRF/cookie, actual item POST, same-user non-item POST, and sync-context GET.

The approved admitted item result is HTTP 422 with `status=rejected`, revision
zero, and projection revision zero. Current legacy admission returns HTTP 403:

```text
Expected: 422
Actual: 403
RED_ASSERTION: expected failing behavior observed
```

Exact `SHOW CREATE TABLE` plus all rows of the four v8 tables and the complete
owned artifact tree are identical before/after every request. The future GREEN
assertions also require same-user non-item HTTP 403 and sync-context HTTP 200
with revision zero.

Sockets have connect/read/write deadlines and response caps. Server pipes are
nonblocking; stop uses bounded TERM and KILL polling and closes/reaps only the
owned process. A shutdown-owned recursive cleanup removes partial artifact
members on every PHP exit. The isolated database and Compose volumes were also
removed after reproduction.

```text
40e2c36434d8a3131fa1e9c2b3f784d79ab5da0596700ec5b46a8324a01c32b7  tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
```

Production and approved artifacts were not edited.
