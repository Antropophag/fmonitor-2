# Yii2 OTIZ workflow delivery — 2026-09-12

Issue: #76. Change: `yii2-otiz-workflow`. Spec: `YII2-OTIZ-WORKFLOW-001`.

Owner/root authored scope, normative spec and tests. Independent sol/low Gate 3
approved the complete test-only source, including the final fixture correction.
A separate sol/low executor implemented the Yii2 adapters and moved reusable
evidence owners to `app/Otiz`; root did not author production implementation.

Candidate behavior: calculate → inspect → accept → XLSX → payment/reverse plus
reconciliation, quarantine, active baselines and historical replay use Yii2 HTTP.
State changes delegate to existing application owners; direct rapid-pilot remains
only a behavioral oracle. Production `public/runtime.php` selects Yii and runtime
include evidence excludes `RapidPilotOtiz` for every OTIZ route.

Focused evidence: mapped application/HTTP/browser tests GREEN; architecture-check
7 rules GREEN; visual contract GREEN; deployment Compose/restart focused check
GREEN. Full local `make test`/`make verify` intentionally not run under owner
decision 2026-09-11. Final independent Gate 5 is `APPROVED` on reviewed source
`3354d333fc0f95a040424b4d3f415cfc439610df37d9b173eb928befb8212721`.
PR and exact-source CI remain pending before merge-ready status.

Deployment: `UNKNOWN` and not authorized.
