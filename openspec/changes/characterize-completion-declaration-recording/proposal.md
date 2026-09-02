## Why

Rapid-pilot превращает запись декларации в терминальную 100% проекцию, но существующий verifier только подставляет готовую строку и не защищает реальный state-changing HTTP path. Перед переносом ownership нужен отдельный воспроизводимый oracle, не смешанный с записью ПТО и не утверждающий неразрешённые правила завершения.

## What Changes

- Добавляется строго `PILOT_ONLY` slice `CHARACTERIZE-COMPLETION-DECLARATION-RECORDING-001` для активного pilot-пользователя, фиксирующего дату и свободные реквизиты декларации после PTO.
- Characterization охватывает реальный local-auth/session/form-CSRF HTTP seam, prerequisite ordering, Unicode trimming/limit, сохранённый declaration fact, replay, multi-worker concurrency, broad admission, live Moscow clock и request-triggered DDL.
- PTO присутствует только как неизменяемый prerequisite; его запись и PTO/declaration race не входят в этот slice.
- Future owner candidate — application module `InstallationCompletion`, seam-кандидат `recordDeclaration`; ни module boundary, ни target command этим change не утверждаются и не реализуются.
- `NEEDS_GRILL`: target `COMPLETION-DECLARATION-001`, durable `canonicalize-installation-completion-schema` и declaration-driven OTIZ остаются заблокированы GRILL-001 вопросами 2–3.

Явные non-goals: утверждение 85/15; декларация как обязательные последние 15%/terminal fact; запись ПТО; cross-command PTO/declaration race; target authorization/separation of duties; structured registry evidence/file/hash; date ordering against PTO/opening/order; correction/supersession; canonical migration; production implementation.

## Capabilities

### New Capabilities

- `verification/completion-declaration-recording-characterization`: воспроизводимая проверка текущей первой записи `declaration` через публичный rapid-pilot HTTP seam.

### Modified Capabilities

Нет.

## Impact

- Source oracle: `rapid-pilot/router.php`, `rapid-pilot/LocalAuth.php`, `rapid-pilot/CompletionFlow.php`.
- Existing verifier gap: `rapid-pilot/verify-completion-flow.php`.
- Planning evidence: `docs/operations/completion-declaration-recording-behavior-evidence.md`.
- Test harness должен владеть уникальным DB prefix/port/cookie и только точными созданными им session files в общем LocalAuth session directory, запускать workers с отключённым session GC и подтверждённым UTF-8, сохранять unrelated session files и не обращаться к production/legacy данным.
- Current runtime DDL, broad authorization и projection-only completion фиксируются как migration risks, а не разрешённая target architecture.
