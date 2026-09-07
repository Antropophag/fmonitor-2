# Unknown-route lazy configuration repair

Canonical frontier reconciliation позволила пройти migration fixture15 в
существующем `pilot_http_auth_001_test.php`. Следующая unchanged assertion
обнаружила чтение `FMONITOR_FRESH_ORDER_FLOW` для неизвестного URL.
PILOT-HTTP-AUTH-001 требует неизвестный404 до configuration/dependencies.

Independent Gate3:
`reviews/tests/PILOT-HTTP-AUTH-001-unknown-route-admission.md`.
Minimal production source `29397bd27e3af856e626908da722f474a272fe39` добавляет
распознавание owned legacy route union и немедленно делегирует остальные пути
до feature flag. Новые original/selection/template и прежние owned legacy ветки
не меняются. Test expectations не менялись; только canonical15 fixture другого
среза является предварительным условием этой проверки.

Evidence external `canonical-frontier-20260907`: auth current first RED
`pilot_http_auth_001_test-green-v1.log`, proper-environment GREEN
`pilot_http_auth_001_test-green-v2.log`, original/selection admission regressions
`*-after-lazy-fix.log`, architecture7PASS и unchanged global-call oraclePASS.
`auth-unknown-route-green.log` — ошибочный запуск без test DB password, это setup
failure и не используется как GREEN evidence.

Gate5 APPROVED:
`reviews/code/PILOT-HTTP-AUTH-001-unknown-route-admission.md`.
Это отдельное исправление production boundary; canonical-frontier change
сохраняет test-only scope. Full VERIFY/launch этим review не объявлены успешными.
