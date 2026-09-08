## Context

Существующий hourly adapter пишет DB напрямую; schema ownership уже канонизирован,
но native delivery отсутствует. Public user.get docs проверены2026-09-07; вызов
реального портала не разрешён. Exact contract — в executable specification.

## Goals / Non-Goals

**Goals:** полный ограниченный native HTTPS fetch для доверенного sync consumer.
**Non-Goals:** normalization/publication/eligibility, cron wiring, DB/grants/migrations.

## Decisions

Readonly transport принадлежит новому namespace Workforce. Небольшие helpers
разделяют config/secret, Curl attempt, retry/deadline, JSON duplicate-key/shape и
pagination за одним public fetch seam. Нет зависимости от PilotHttp, legacy/verifier.
Каждый запрос освобождает Curl resources; cookies и credential cache не создаются.

JSON syntax validation предшествует duplicate-key scan; scan обязан корректно
обрабатывать escaped keys и большие строки без неограниченного regex/backtracking.
Batch сохраняет только selected scalar/list data by value; JSON summary — counts.

Task-owned Python HTTPS server и synthetic CA/key проверяют реальный Curl. TLS и
redirect failures доказываются native network behavior, без permission/native
interception. Workers/ports очищаются с deadlines, raw captures остаются private.
ASYNCHDNS и millisecond timeout prerequisites проверяются до запросов: нельзя
обещать deadline для синхронного resolver, который native timeout не прерывает.

## Risks / Trade-offs

Remote pagination не даёт atomic point-in-time snapshot → утверждается только
проверенный полный delivery; observations и normalization принадлежат publisher.
Missing selected field может означать scope/schema mismatch → fail без выдуманных
значений. Empty complete delivery — данные, а не одобрение публикации. Secrets
остаются вне repo, safe results не содержат diagnostic response/credential strings.
Architecture-check проверяет новые boundaries без baseline ratchet.
