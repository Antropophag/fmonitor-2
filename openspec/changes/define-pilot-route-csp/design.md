## Context

См. `proposal.md`. Сейчас CSP формируется как минимум четырьмя способами: общий `PilotHttpCoordinator`, E2E coordinator, direct rapid-pilot responders и local-auth HTML. Общие responders добавляют `script-src 'self'` почти всегда; direct handlers частично задают собственные headers. `PilotView` всегда добавляет внешний navigation script, специализированные renderers добавляют свои assets, а `CompletionFlow` добавляет inline script, который разрешённой owner policy не выполняется.

Три известных regression verifier фиксируют drift старой базовой policy. Slice security-cross-cutting, но не владеет product state или persistence.

## Goals / Non-Goals

**Goals:**

- Один детерминированный классификатор route/result → exact CSP для coordinator responses.
- Эквивалентная policy в неизбежных direct responders до их последующей strangler-миграции.
- Fail-closed default для unknown route, errors, redirects и non-HTML.
- Удаление единственного найденного inline CompletionFlow script без изменения progress semantics.
- Test matrix, чувствительная к случайному глобальному возврату `script-src`.

**Non-Goals:**

- Объединение всех HTTP handlers в этом slice.
- Изменение authorization, sessions, status/body semantics или business persistence.
- Новый CSP framework, reporting collector, nonce/hash generation.
- Разрешение новых scripts только потому, что asset существует в repository.

## Decisions

### 1. Security policy принадлежит HTTP boundary

Owning module — `app/PilotHttp` security/response boundary. Он зависит только от нормализованных request/result attributes и статической route policy; application/domain modules от него не зависят. Persistence owner отсутствует: CSP selection является pure function и не читает/не пишет DB.

Альтернатива — решать CSP внутри каждого renderer — отвергнута: renderer не знает итоговый status/media type, а drift direct headers уже наблюдается.

### 2. Allowlist основана на exact route patterns, не на body sniffing

Классификатор принимает method, normalized path, final status и media type.
Сначала выбирается `BASE_CSP`; exact `GET/HEAD` + `2xx` + HTML + allowlist
поднимает policy до scripted/checklist variant. Единственное method exception —
exact `POST /pilot/login` с final `200 HTML`; оно сохраняет текущую scripted
login representation. Body проверяется тестом как invariant, но не является
authorization input. PRG conversion вне scope.

Альтернатива `str_contains($body, '<script')` отвергнута: пользовательский текст может повлиять на security header, а error decoration может случайно расширить policy.

### 3. Route inventory фиксируется целыми families

Allowlist из executable spec соответствует реально наблюдаемым read routes и assets. `{positive-id}` match anchor-ится целиком. OTIZ export и все POST commands исключены. Новый route fail-closed до reviewed изменения policy и verifier.

### 4. Result classification выполняется после получения итогового response

Status и media type берутся из окончательного response после renderer/decorator. Это гарантирует, что `404/409/503` на script-capable path не наследуют success policy. `HEAD` использует классификацию соответствующей GET representation, сохраняя body-empty contract.

### 5. Checklist имеет отдельный узкий variant

Только два checklist route patterns получают worker/connect/blob additions. Service Worker script asset сохраняет собственную `default-src 'self'; connect-src 'self'`; он не классифицируется как HTML и не получает `script-src`.

### 6. Inline CompletionFlow logic переносится в существующий checklist asset

Предпочтительный bounded remediation — добавить обработку `[data-progress-cap]` в `app/PilotHttp/checklist.js`, который уже загружается обеими checklist страницами, и удалить inline injection из `CompletionFlow::enhanceChecklist`. Серверный renderer остаётся допустимой альтернативой только если Gate 2 докажет полностью эквивалентный динамический результат. Новый asset/route не создаётся.

Rapid-pilot остаётся adapter/oracle: разрешена только removal/wiring этой inline presentation logic; новая domain logic туда не добавляется. Изменение `CompletionFlow.php` требует architecture justification как hotspot edit и `make architecture-check`.

### 7. Existing security/cache headers сохраняются

Slice заменяет только значение CSP и inline fragment. Status, body (кроме fragment), content type/length, redirects, cache, `nosniff`, referrer/frame/permissions/COOP и asset bytes остаются regression assertions. CSP error classification не пишет audit: security incident logging не вводится скрыто.

## Risks / Trade-offs

- **[Разные response paths снова расходятся]** → один pure classifier для coordinators; direct handlers используют те же constants/matrix либо покрываются одинаковым black-box matrix до GREEN.
- **[Allowlist пропустит реально scripted page]** → inventory test извлекает external scripts из всех successful HTML fixtures и требует двустороннее соответствие allowlist ↔ необходимый script.
- **[Allowlist станет шире routing table]** → anchored route patterns и negative near-match examples.
- **[Inline behavior переносится с timing regression]** → focused DOM/browser-compatible verifier для cap `85/100`, затем checklist characterization/E2E.
- **[Hotspot edit увеличит rapid-pilot debt]** → минимальное удаление fragment и перенос в existing asset; architecture baseline не расширяется без явной записи.
- **[CSP на non-HTML имеет ограниченную browser effect]** → сохраняется как uniform defense/observable contract, но asset bytes/cache не меняются.

## Migration Plan

1. Утвердить `specs/PILOT-ROUTE-CSP-001.md` как Gate 1.
2. Gate 2 author создаёт минимальный black-box RED matrix и отдельный CompletionFlow inline/cap RED; сохраняет intended failures.
3. Independent test reviewer утверждает чувствительность и route inventory.
4. Реализовать pure route/result classifier, подключить coordinator/direct responders и bounded externalization CompletionFlow.
5. Запустить focused verifiers, известные три regression entrypoints, checklist characterization, `make architecture-check`, затем `make verify`.
6. Independent code review проверяет отсутствие расширения allowlist, inline/eval/third-party allowances и business changes.

Rollback: вернуть implementation commit целиком; data migration отсутствует. Частичный rollback, который возвращает глобальный `script-src` или inline fragment, запрещён, поскольку нарушает approved security contract.
