# Текущая цель — №185, безопасный повторный local `make up`

Поручение владельца 2026-09-18 заменяет прежнюю текущую цель №181: реализовать первый ограниченный срез [№185](https://github.com/Antropophag/fmonitor-2/issues/185) одним отдельным PR от актуального `main` после merge №188 и довести до PR-ready.

Срез отделяет clean initial-owner provisioning от explicit read-only local continuation. Повторный `make up` существующего стенда подтверждает ожидаемого active bootstrap-владельца по identity facts, provenance и необходимым полномочиям; дополнительные пользователи, invitations и login/history facts сами по себе запуск не блокируют. Password/profile/session/grants/history не сбрасываются и отозванные права не восстанавливаются. Чужой, неоднозначный, blocked или недостаточно полномочный owner отвергается без изменений. Direct production bootstrap остаётся строгим.

Не входят POSIX modes, file UID, VPN route, import filters, engineers, chunking, №182, planner/harness algorithms, skip rules и architecture exceptions. Production imports, external sends, merge и deploy запрещены. Локально только bounded focused checks; полный `make test`/`make verify` запрещён. Один exact-source CI обязателен.

Контракт: [INITIAL-OWNER-PROVISIONING-001](../../specs/INITIAL-OWNER-PROVISIONING-001.md). Lifecycle: [resume-provisioned-local-stand](../../openspec/changes/resume-provisioned-local-stand/). Root пишет scope/spec/tests; отдельный `gpt-5.6-sol/low` executor реализует; независимые `gpt-5.6-sol/low` reviewers решают Gates 3/5. PR использует `Refs #185`, не закрывает parent issue.
