# Yii2 jobs console — delivery record 2026-09-11

Issue: #76. Contract: `YII2-JOBS-CONSOLE-001`. OpenSpec: `yii2-jobs-console`.

Root authored scope, specification and tests. A separate `gpt-5.6-sol/low`
executor authored production code. Independent `gpt-5.6-sol/low` review approved
Gate 3 on retained pre-implementation source `019c69abd126c56faa3c7e653f2ae5b5c731839515d7456163ca06357505b414`
and Gate 5 on candidate source `94901a00df86be6d92f0da6462894c8548559584a5ea30386930e373f8d9764b`.

Focused evidence covers Yii worker/scheduler/health, DB health and rejection
immutability, isolated Compose restart/delivery, lease loss, signals, retry,
outbox deduplication, architecture and verification graph. Gate 5 record:
`reviews/code/YII2-JOBS-CONSOLE-001.md`. Full CI, merge and deployment are not
implied until recorded below.

The pilot image now installs the locked Composer vendor and carries Yii config;
production worker/scheduler/health no longer enter through rapid-pilot. Imports,
migrations, web retirement, common cutover and deployment remain outside this slice.
