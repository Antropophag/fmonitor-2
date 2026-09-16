## Why

Blocked T07a не может безопасно потреблять live admission facts: существующий harness durable сохраняет exact verification plan и active task binding, но не materialize'ит authoritative structured Gate verdict и поэтому fresh invocation строит observation с пустым `reviews`. Нужен bounded prerequisite, который замыкает уже существующий harness data flow без определения admission semantics.

## What Changes

- Добавить один canonical machine-readable live admission context для текущей task/candidate: exact plan, selected obligations, policy/source binding и structured состояния только действительно требуемых reviews.
- Минимально расширить существующий external harness state/package contract для durable записи независимого Gate 3/5 result; Markdown reviews не являются authoritative input.
- Проецировать один и тот же context в `state`, `wait` и `prepare-merge`, сохраняя missing/stale как missing/stale и invalidating review при изменении exact source или applicable plan/policy.
- Представлять FAST route только его фактическим final-review contract, а STANDARD/CRITICAL — planner-selected `required_reviews`.
- Не коммитить candidate-specific verdict в candidate и не создавать новый evidence store, planner, review framework или admission policy.
- Не менять blocked T07a, `admission.evaluate()` semantics, Quality Graph publisher, T07b, #153B/C и product code.

## Capabilities

### New Capabilities

- `delivery/live-admission-context`: durable exact-source-bound plan/review context, восстанавливаемый fresh harness process и одинаково доступный live admission consumers.

### Modified Capabilities

Нет.

## Impact

Изменения ограничены существующими `tools/delivery/harness.py` / `harness_context.py`, external evidence/package files, focused Delivery tests, executable spec и process documentation при необходимости. Формат observation, передаваемый существующему admission consumer, расширяется данными из canonical context; логика решения admission не меняется.
