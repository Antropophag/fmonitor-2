## Purpose

Определяет reconstructible и fail-safe упаковку применимого repository context для delivery roles без копирования или ослабления canonical правил.

## ADDED Requirements

### Requirement: Manifest является deterministic индексом canonical sources
Existing harness prepare SHALL создавать machine-readable task-context manifest, связанный с issue/change identity, exact base/source, policy/instruction digests и ролью package. Каждый required item SHALL содержать canonical source, applicability reason, digest, load mode и либо exact bounded range/content reference, либо ссылку на существующее compact machine-readable rule. Manifest MUST NOT содержать generative summaries или становиться источником normative semantics.

#### Scenario: Повтор неизменного lifecycle input
- **WHEN** prepare повторяется для той же роли, exact source/base, verification input, canonical source digests и section index
- **THEN** semantic manifest content идентичен и immutable package не требует повторного expansion

#### Scenario: Fresh session
- **WHEN** новый root, executor или reviewer package готовится из текущего repository state
- **THEN** required context заново материализуется из актуальных canonical digests без межсессионного признака «уже прочитано»

### Requirement: Applicability выбирается детерминированно и fail-safe
Harness SHALL использовать только planned/changed boundaries, planner lane/risk, issue/OpenSpec binding, existing verification policy и repository-owned document roles. Безопасно индексируемая canonical section SHALL иметь stable identifier и проверяемые границы; unknown boundary, неизвестная applicability или неделимый документ MUST делать соответствующий canonical source required целиком.

#### Scenario: Bounded presentation change
- **WHEN** planned boundaries ограничены поддержанным presentation/UI классом без sensitive соседних boundaries
- **THEN** manifest включает полные применимые general/UI/delivery rules, не делает persistence/security history inline и оставляет несвязанные материалы load-on-demand

#### Scenario: Persistence/current-state change
- **WHEN** planned или changed boundaries касаются persistence, current state, schema или domain state transition
- **THEN** manifest обязательно включает применимые persistence/domain/security instructions и не использует UI-only context set

#### Scenario: Auth/security change
- **WHEN** planned или changed boundaries касаются auth, authorization, identity, session, CSRF, secrets или security policy
- **THEN** manifest обязательно включает security/auth instructions

#### Scenario: Harness/verification policy change
- **WHEN** boundaries принадлежат delivery harness или verification governance
- **THEN** manifest включает harness/verification governance rules, а historical product documents не становятся автоматически inline

#### Scenario: Unknown boundary
- **WHEN** хотя бы одна boundary не классифицирована repository-owned deterministic mapping
- **THEN** manifest выбирает conservative required context и не скрывает потенциально применимые rules

### Requirement: Digest и section freshness предотвращают тихое устаревание
Каждый source и range SHALL быть связан с content digest, а section index SHALL иметь собственную version/digest binding. Prepare MUST пересобрать context из current sources либо отвергнуть stale/invalid reference; изменение canonical текста, heading layout или index boundaries MUST NOT приводить к silently missing instruction.

#### Scenario: Canonical source изменился
- **WHEN** digest canonical source отличается от manifest binding
- **THEN** старый manifest не используется и prepare выдаёт новый current binding либо fail-closed error

#### Scenario: Section indexing изменился или range недействителен
- **WHEN** section index/version или ожидаемая section identity не соответствует canonical source
- **THEN** bounded extraction отвергается или source становится required целиком без пропуска instruction

### Requirement: Public role packages реально используют manifest
Root/executor/reviewer package SHALL содержать manifest reference и materialized required context через существующий `prepare` route. Reviewer package SHALL дополнительно содержать exact candidate/spec/evidence/review references и load-on-demand canonical links, достаточные для реконструкции exact canonical content. Наличие manifest само по себе MUST NOT давать approval, evidence или GREEN.

#### Scenario: Reviewer package
- **WHEN** harness готовит reviewer role package
- **THEN** package содержит exact applicable references/digests, candidate/spec/evidence bindings и возможность реконструировать полный canonical source

#### Scenario: Consumer без approval/evidence
- **WHEN** package содержит valid manifest, но required verification evidence или review отсутствует
- **THEN** существующие admission checks остаются fail-closed и не выводят GREEN из manifest

### Requirement: Current и historical context разделены без удаления истории
Current actionable goal и explicit current evidence SHALL быть required, когда применимы. Historical delivery goals, старые reviews и evidence SHALL по умолчанию быть load-on-demand и сохраняться append-only.

#### Scenario: Historical delivery documents
- **WHEN** historical goal/review/evidence не указан как evidence текущего exact task
- **THEN** manifest сохраняет reconstructible load-on-demand reference и не материализует документ как mandatory inline context

### Requirement: Context reduction измеряется воспроизводимо
Repository SHALL содержать deterministic replay measurement для завершённых bounded UI, persistence/current-state и harness/verification changes. Для каждого replay output SHALL показывать before/after mandatory bytes, characters, full canonical document count и load-on-demand reference count; unsupported token telemetry MUST отображаться как `UNKNOWN`.

#### Scenario: Три representative replay
- **WHEN** measurement запускается на зафиксированных inputs трёх representative changes
- **THEN** bounded UI показывает заметно меньший mandatory context, sensitive replay сохраняет applicable mandatory rules, а результаты воспроизводимы без LLM

