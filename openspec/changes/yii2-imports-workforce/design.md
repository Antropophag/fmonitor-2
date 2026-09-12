## Context

См. `proposal.md`. Полный `PILOT-CASE-IMPORT-001` уже проверяет transport, DB facts, eligibility, repeat, concurrency и unknown commit. Текущий bin script вручную собирает owner; Yii console уже обслуживает jobs и migrations.

## Goals / Non-Goals

**Goals:**

- Один Yii route и одна shared case-import composition.
- Полная прогоняемость существующего oracle через новый seam.
- Behavioral parity retained alias и доказанная единственная composition boundary.
- Закрытый production package/load set.

**Non-Goals:**

- Не менять `PilotCaseImporter`, eligibility, SQL, schema или history.
- Не переносить snapshot import, workforce sync, web/startup или stand.

## Decisions

1. `InstallationProcess` остаётся owner; controller передаёт validated IDs shared adapter.
2. Shared adapter владеет environment mapping и одним mysqli lifecycle; Yii DB не используется.
3. Existing case-import suite раздельно запускается через Yii test entrypoint и retained alias; lexical witness запрещает вторую owner/connection composition.
4. Legacy script становится launcher к той же composition. Direct/alias сравниваются на success, rejection, unavailable DB и repeat.
5. Package test проверяет artifact inventory, terminal subprocess outcome, required/forbidden included files и отсутствие запуска из ordinary runtime.

## Risks / Trade-offs

- [Risk] Alias сохранит скрытую composition → Mitigation: lexical ownership и behavioral parity.
- [Risk] Yii bootstrap изменит output → Mitigation: закрытая subprocess matrix и redaction canaries.
- [Risk] Transport wrapper исказит oracle → Mitigation: wrapper добавляет только route/`--interactive=0`, durable facts проверяет прежний независимый suite.

## Migration Plan

1. Пересчитать normative spec/input/RED и получить новый Gate 3.
2. Executor реализует adapter/controller/alias и focused GREEN.
3. Independent Gate 5, exact-source PR и один full CI. Stand не переключается.
