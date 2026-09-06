# Доставка аудита original attempts

Завершён ATTEMPT-AUDIT v0.4 на exact SHA `3d4e852866ace2ef3abf776c8ffa027b7eca9d91`.
Независимый Gate5 APPROVED: `reviews/code/ASSIGNMENT-ORDER-ORIGINAL-ATTEMPT-AUDIT-001-v1.md`, SHA256 `84206a905c7a81206673773a6dc4c6f397c7845b1fb22e70c4a2722b9aa5d8d9`.

Production factory и worker сохраняют каждую valid-shape denied invocation отдельной audit row. Accepted terminal/evidence не раскрываются и не переписываются; retryable file/storage failures используют отдельный audit-only port. Реальные конкурентные вызовы сохраняют один terminal и две audit rows. Схема v3 зарегистрирована canonical migration13; целостность, repeat, prefix25 и неизменность истории проверены. Quoted CHECK space/backtick drift больше не принимается за ready.

Final completed archive: `/Users/antropophag/.local/state/fmonitor2-verification/original-attempt-audit-final-green-id4dwftx`; evidence SHA256 `4be7c8ace5036d5f7ff0f019730becbefa026f3164327bd8a964effd33d736e7`. Before/after clean3d4e852, все52 commands PASS. Проверены новые command45/schema21/native41/literal4, прежние original suites, supporting DB checks, architecture/unit/lint, strict обоих OpenSpec changes и scoped diff. Это не full make verify/VERIFY_OK.

Gate chronology: owner every-denial approval → Gate1v02/v03/v04 → отдельные command/schema/native/naming-consumer/binding/literal Gate3 → минимальный GREEN → exact compatibility patches Gate3 → corrected final52 → independent Gate5. Предыдущие failures и все reviews сохраняются. Записи compatibility-v1/tail со статусом UNAPPLIED — исторические; patches применены в2f543df и3d4e852 после соответствующих approvals.

## Затраты и следующий критический путь

RED span около80 минут превысил предел60; причина — подтверждённые predecessor/naming/binding gaps. Пересмотр подхода ограничил дальнейшую работу конкретными найденными дефектами и необходимой compatibility regression. Schema/native GREEN+closure span около106 минут уложился в предел120. На11:36UTC observed persistent goal:1131642 tokens,10823 seconds с рестарта; это агрегат всей цели, не стоимость одного slice. Временных ограничений оказалось недостаточно; следующие пакеты ограничиваются также наблюдаемым token delta, без persistent token budget.

До полного launch остаются maintenance/resource/public-declaration полнота original command, выбор состава/registry/read/render/cutover, HTTP, применение оригинала и отдельное открытие, первая full exact-SHA VERIFY_OK, затем разрешённая CI/publication стадия, clean deploy/restart/login/persistence/golden path и requirements audit. Оба режима new_order/replace_pending сохраняются. Canonical frontier теперь13; selection/registry ещё не зарегистрированы. Deadline09-09 09:00МСК, риск высокий. Goal ACTIVE, не complete/blocked. PR10, remote branches и deployment не изменялись.
