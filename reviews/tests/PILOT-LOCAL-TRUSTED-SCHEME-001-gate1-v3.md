# Independent Gate 1 exact-hash confirmation — PILOT-LOCAL-TRUSTED-SCHEME-001

- Verdict: **APPROVED**
- Reviewer: `/root/original_gate1_v3`, separately tasked agent; not an author of the specification or implementation.
- Date: 2026-09-07
- Reviewed HEAD: `a5419effa8a2da44edd216da0f5fc99d64bd978c`.
- Specification: version 0.2, final clarified SHA-256 `91eec5258b9d55b31fc4fc3688338b3c26c4dffa886b0569d9be43cc62ee3bc6`.
- Prior records: `PILOT-LOCAL-TRUSTED-SCHEME-001-gate1.md` and `PILOT-LOCAL-TRUSTED-SCHEME-001-gate1-v2.md`.

The additional operational clarification is **APPROVED** without blocking findings. The allowed nonce change is confined to exactly one existing sentinel row; all other active.json fields remain unchanged. The post-recreation preservation snapshot is explicitly taken before smoke login. Smoke login uses a new task-owned session and retains its ordinary login audit, while previously existing session bytes remain exact. This cleanly distinguishes startup preservation evidence from the separately authorized authenticated smoke actions.

The functional configuration/HTTP contract and all prior gate limitations remain unchanged. The hash above was independently confirmed after reading the final operations section. Only this review record was added; no tested artifact or runtime resource was changed or executed. Gate 3, GREEN and Gate 5 remain prerequisites for recovery.
