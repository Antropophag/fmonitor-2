## Context

См. `proposal.md` — Why и delta spec `delivery/task-context-manifest`. Сегодня hook injects общий prose context, а `prepare` строит verification plan, snapshot и role package, но не имеет одного digest-bound списка того, какие canonical instructions реально должны быть прочитаны ролью. Canonical документы неоднородны: часть имеет устойчивые headings, часть безопаснее передавать целиком. Решение должно использовать existing verification input и planner output и не дублировать их решения.

## Goals / Non-Goals

**Goals:**

- Один deterministic manifest artifact внутри существующего package directory и reference из `package.json`/active state.
- Exact reconstruction каждой bounded section из repository snapshot/current source с digest verification.
- Closed deterministic boundary profiles для UI, persistence/state, auth/security и harness/verification плюс conservative fallback.
- Reproducible before/after proxy measurement на зафиксированных existing change inputs.

**Non-Goals:**

- Новый planner/policy engine, изменение verification lane/coverage/Gates или admission semantics.
- NLP/LLM classification, summaries, RAG/vector/search service или массовая реструктуризация canonical docs.
- Product/runtime edits, session rotation, supervisor, CI performance или broader delivery lifecycle redesign.

## Decisions

### 1. Manifest расширяет package schema, а не создаёт соседний service

Owning module остаётся `tools/delivery/`; `harness.py prepare` вызывает компактный deterministic builder после planner output и до атомарной записи `package.json`. Package содержит path/digest/schema version manifest и materialized required-context artifact. Альтернатива — отдельный CLI/daemon — отклонена как второй lifecycle и consumer, который может расходиться с реальным prepare route.

### 2. Applicability строится из closed boundary profiles с conservative fallback

Builder нормализует `planned_paths` и actual changed paths, затем применяет repository-owned path/profile table. Profiles добавляют document roles/section identifiers; неизвестный path выбирает full safe set. Lane остаётся planner-owned input, но builder не меняет lane и не решает verification coverage. Альтернатива — распознавание prose/issue текста — отклонена как недетерминированная и запрещённая.

### 3. Section index хранит locator и expected heading, но не normative copy

Для крупных Markdown sources индекс задаёт stable rule id и exact heading boundaries. Builder читает canonical bytes, проверяет heading uniqueness/order, вычисляет source digest и digest extracted bytes. Любая неоднозначность переводит весь source в required full-document mode. Compact existing JSON policy может быть referenced как whole machine-readable rule. Альтернатива — копировать сокращённые правила в индекс — отклонена как второй нормативный источник.

### 4. Materialized content отделено от semantic manifest identity

Manifest использует repository-relative refs, digests и deterministic ordering; timestamps/package UUID не входят в semantic content. Required-context artifact содержит exact canonical excerpts/full files один раз на package. Fresh prepare всегда читает current bytes; повтор внутри immutable package использует тот же artifact. Это даёт case G/H без межсессионного cache claim.

### 5. Historical artifacts являются typed references

Current goal и explicit evidence из prepare arguments помечаются required/reference; прочие known history roots перечисляются load-on-demand без recursive materialization. История не удаляется и не меняется. Reviewer получает candidate snapshot, plan, specs/evidence и manifest; manifest не участвует в verdict/admission calculation.

### 6. Measurement использует сохранённые verification inputs

Replay fixtures: `unify-yii-main-navigation` (bounded presentation), `standalone-control-engineer-assignment` (persistence/current-state) и `canonical-verification-inventory` (harness/verification). Baseline моделирует документированный pre-manifest route на current main; after использует builder с теми же inputs. Считаются bytes и Unicode chars exact materialized content, full-doc count и load-on-demand refs. Token usage фиксируется `UNKNOWN`.

Dependencies ограничены Python standard library и существующими harness/planner seams. Persistence owner отсутствует: manifest — immutable external package artifact, а не domain state. `rapid-pilot` не читается и не меняется. Architecture checks должны подтвердить отсутствие product imports и сохранение public CLI.

## Risks / Trade-offs

- [Неполный deterministic profile скроет правило] → unknown paths и invalid sections fail-safe расширяют required set; sensitive fixtures проверяют обязательные profiles.
- [Heading edit обесценит range] → validate unique expected heading against current source and bind index/source/excerpt digests; fallback full source.
- [Снижение bytes окажется только синтетическим] → измерять три реальные завершённые verification inputs и честно отделять bytes proxy от UNKNOWN tokens.
- [Package schema сломает старых consumers] → additive versioned fields; existing admission remains authoritative and focused compatibility tests cover route.

## Migration Plan

Добавить additive manifest schema/builder и focused tests, затем подключить его к `prepare` для всех roles. Старые external packages остаются историческими и не переписываются; новый prepare всегда создаёт current manifest. Rollback — удалить additive builder/reference без изменения canonical docs или product state.
