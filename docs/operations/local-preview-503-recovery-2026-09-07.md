# Preview503 — диагностика и сохранение состояния,2026-09-07

Source HEAD e1d3a64824395c96ceb47827b21e114dc4c60076, clean при старте.
Persistent goal отсутствовал и восстановлен exact из restart handoff без budget.

## Подтверждённая причина

Owned project fmonitor2-local-preview, image fmonitor2-local-preview:1eba93cf966d.
MariaDB healthy; pilot unhealthy; GET /pilot/login без cookie503. Диск2% занят.
Native public PilotSessionStorageFactory→start(null) вернул unavailable/gc_failed.
Session instance pilot содержит5003 lock +4998 session =10001files. Approved
FilesystemPilotSessionStorage initialize отклоняет list>10000. Compose каждые5s
вызывает objects/installers через file_get_contents без сохранения Set-Cookie:
redirect/login создают анонимные sessions, накопление подтверждено классификацией.

Payload categories (значения секретов не выводились):2495 auth_csrf-only;
1248 auth_return_to=/pilot/objects-only;1247 auth_return_to=/pilot/installers-only;
3 auth_return_to+auth_csrf;5 auth_csrf+auth_user_id+auth_email+auth_signed_in_at.

## Восстановление

Ownership подтверждён external ownership.json, Docker labels/mounts и uid10001.
Только2495 сессий с единственным exact key auth_return_to и одним из двух health
paths перенесены на том же volume в incident-20260906-2137-anonymous, mode0700.
Под existing per-session LOCK_EX|LOCK_NB повторно проверены owner/mode/payload;
bytes сохранены rename, SHA256 проверен после переноса. Existing locks оставлены
на месте. Закрытый manifest хранит hashes имён/bytes и размеры. Сессии с CSRF,
все5 авторизованных sessions, process state/DB и старый image не менялись.
Никаких reset, удаления данных, volume recreation или production import.

Сразу после операции GET /pilot/login200. Это восстановление доступности входа,
не исправление причины повторного роста и не full verify/launch readiness.

Primary diagnostic script находится только во внешнем каталоге:
/Users/antropophag/.local/state/fmonitor2-local-preview/diagnostics-20260906-2137.
Следующий bounded slice — fix-preview-healthcheck-session-growth, Gates1–5.

Authenticated проверка после восстановления: POST login→objects200; objects200,
calendar200, roles200, users503, installers403. Credentials использованы только
из external0600 preview.env и не выводились. Users503 остаётся отдельным blocker.

Healthcheck slice: Gate1v0.2/Gate3v2 APPROVED; intended RED absentCLI127 при
healthy fixture и demonstrated predecessor growth. GREEN native HTTP20 repeats,
both503,204,redirect trap/3/4,storage/lock/timeout/stale cookie PASS. Architecture7
rules PASS, deployment contract/local-auth return-to regression/lints/diff PASS.
External health-red-v2.log, health-green.log, health-architecture.log в diagnostics
каталоге выше. Gate5 и operational binding следуют отдельным record.

## Финальный Gate5 и operational binding

Source12efd022ad4b1879cb541a0fa4095705e15e803e получил Gate5 APPROVED:
reviews/code/PILOT-HEALTHCHECK-SESSION-001-v2.md. Первый Gate5 request сохранён;
исправлен silent curl cookie write failure через stdout capture + checked native
write/flush/fsync/rename. Native child-only RLIMIT_FSIZE200 test показал RED0
вместо1, fresh Gate3 APPROVED, затем GREEN. Permission probes/interception нет.

Pilot пересоздан на ТОМ ЖЕ fmonitor2-local-preview:1eba93cf966d image, с прежними
volumes; только approved probe12efd02 добавлен отдельным readonly mount из external
healthcheck-12efd02 в /opt/fmonitor-healthcheck. External compose.override.yaml
фиксирует этот mount/health command. Старый image и backup override сохранены.
До операции external0600 backups: state-before-healthcheck.tar и
single-transaction database-before-healthcheck.sql. Restore не выполнялся.
Штатный bootstrap старого image выполнен при restart; новая feature source/schema
не развёрнута. Manifest/sentinel deployment metadata обновляется этим predecessor.

Pilot и MariaDB healthy, login200. В running Linux container20 sequential public
probe invocations PASS: exact session/lock inventory9107entries и bytes неизменны.
Шесть авторизованных session files из before backup совпали byte-for-byte после
пересоздания. После этого authenticated login→objects200; objects/calendar/roles200,
users503 и installers403 сохраняют прежний отдельный disposition.
Primary logs: linux-health20.log, http-after-healthcheck.log, health-cookie-red.log,
health-cookie-green.log, health-architecture-v2.log в том же external diagnostics.

Диагностика+восстановление и corrective gates/reviews/commits уложились в объявленные
20min +40min пакеты (фактически около25min суммарно). Полного VERIFY_OK нет.
Следующая работа: fresh HTTP/UI selection/template/original, применение состава,
отдельное открытие; users503 остаётся launch blocker. Health означает anonymous
HTTP liveness, не launch readiness/доказательство DB/process grants.
