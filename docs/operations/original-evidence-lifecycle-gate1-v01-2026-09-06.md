# EVIDENCE-LIFECYCLE v0.1 — независимый Gate1

Reviewer: отдельно назначенный `/root/maintenance_review`, gpt-5.6-sol low.
Дата2026-09-06. Verdict: **APPROVED**.
Spec SHA256: 2e37ebfce21f785a8a6a1ea4aa5aa44aabe04173a38c3954d9a307abc175adae.

Contract согласован с approved ORIGINAL-UPLOADv74 section16. Scope ограничен
public declarations, config validation, fixed errors, read-only lifecycle,
resource ownership и close; canonical projections/worker/fixture/command отдельно.
Public beginStage/abort создаёт empty metadata, held digest lock обязан оставаться
LOCKED для второго storage после reader close. Named declarations, representative
invalid config/password и все10 reads after close дают независимый RED.
Metadata→password→connection ordering и native close failure требуют source Gate5;
они не выдаются за доказанные через запрещённую interception. Неясностей нет.
Reviewer не изменял artifacts. Product authority не расширяется.
