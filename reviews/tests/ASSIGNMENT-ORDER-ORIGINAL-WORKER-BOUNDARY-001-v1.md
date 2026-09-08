# Независимый Gate3: worker boundary

Дата2026-09-06. Reviewer `/root/maintenance_review`, отдельно назначенный gpt-5.6-sol low; не автор tests/source. Verdict **APPROVED**.
Spec caf6e181fac91967fb05b980a198a9928df8f0d734d29959a613c48b052881bb.
Test0de46ca0b24b2aed302a6744b549d085e173bfc7cc4b60278d82379f6e01a70a.
Helper6e3f32af2f073ed848ab302a52120399adfa7447449c2ab35c9f6372e6a8faf9.
Source ef2f1ead9b043ed55f9c0b1333200eafe3ffefed.
RED `/Users/antropophag/.local/state/fmonitor2-verification/original-worker-boundary-red-8kj7oe2r`.
Manifest3e88abd6102057207dda269e85b22cf5dd6ee25c78ec9ccff47bcee39f921efa.

1native controlPASS/12intendedFAIL. Encoder literals independently проверяют format,16384/16385 и invalidUTF8. Real socket elapsed4.5..12 отличает child5s от parent15s watchdog; READY исключает startup из barrier timing, допуск достаточен. Current worker доходит до parent watchdog и intendedFAIL. Six invalid configs с0command bytes/open peer выявляют premature command read. Exact70channels обязательны. Optional helper defaults неизменны; ownership/bounds/terminate/reap на всех путях. Setup/sensitivity/scope findings нет. Reviewer не менял artifacts; GREEN разрешён.
