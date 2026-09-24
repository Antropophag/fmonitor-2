## Context

`MariaDbEffectiveObjectDetails::sqlValue()` is the existing public SQL mechanism used by `MariaDbYiiObjectQueue::read()` for address, entrance and registration number. `readCalendar()` has two bounded queries but currently selects the legacy columns directly.

## Decisions

1. Reuse `MariaDbEffectiveObjectDetails::sqlValue()` in both existing calendar queries and join the current edit row once per query.
2. Keep the two-query shape, limits, overflow checks, event construction and final sort unchanged. No per-object reads or N+1 loop are introduced.
3. Preserve the established JSON semantics: absent key falls back to legacy; an explicit JSON null resolves to null; an explicit empty string remains empty. Display mapping continues trimming the resolved scalar.
4. Prove behavior through the real Yii HTTP route. Existing registry/card reads are checked in the same fixture, and browser verification reloads the rendered calendar.

## Non-goals

Dates, deadline certificates/#233, #45, other #14 work, OTIZ, writers, history, schema, import, general UI, deployment and CI policy are excluded.
