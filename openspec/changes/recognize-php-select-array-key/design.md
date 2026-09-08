## Context
Измеренный architecture-check failure в readonly DeliveryCurlAttempt.

## Goals / Non-Goals
Устранить false positive. Не менять ownership policy, fingerprints или baseline.

## Decisions
При lexical обработке PHP quotes исключать только whole select token перед =>.
Остальные quoted values проверяются обычным scanner. SQL в той же строке остаётся.

## Risks / Trade-offs
Нельзя исключать whole line или длинный literal с SQL. Negative CLI fixtures и
существующие checker tests сохраняют чувствительность.
