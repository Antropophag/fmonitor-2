## Context

См. `proposal.md` — Why. Текущий public handler одновременно выполняет authorization, direct read-model SQL и HTML rendering. Отдельный runtime-DDL test проходит этот route, но проверяет только readiness status. Новый slice является test-only characterization и не создаёт новый production seam, persistence или domain logic.

## Goals / Non-Goals

**Goals:**

- получить deterministic normalized transcript реального HTTP oracle;
- доказать read-only поведение полным fingerprint owned DB/file/session state;
- отделить server projection от browser-only filters/offline effects;
- обеспечить безопасные concurrent-agent namespaces и cleanup.

**Non-Goals:**

- изменение `PilotHttp`, `InstallationProcess`, rapid-pilot assets или schema;
- утверждение target assignment/visibility, activity, completion, ordering или pagination semantics;
- browser automation и characterization IndexedDB/service-worker synchronization;
- production data, primary evidence и secrets.

## Decisions

### 1. Реальный HTTP composition, не прямой renderer unit call

Harness поднимает отдельный loopback worker через production entrypoint factory и выполняет socket HTTP exchanges. Так наблюдаются identity, permission, SQL projection, renderer, headers и HEAD behavior одним public seam. Прямой вызов renderer отклонён: он мог бы self-attest rows и пропустить authorization/SQL defects.

### 2. Полностью synthetic prefixed persistence

Persistence owner остаётся существующим canonical schema/InstallationProcess namespace; characterization ничего не создаёт в production runtime. Test support подготавливает уникальный короткий prefix, буквальные fictional users/cases/events/operations и отдельный artifact/session root, предварительно доказывает collision-free ownership и удаляет только owned resources. Любой DDL и DML fixture setup выполняет только privileged test connection; каждый HTTP worker principal получает лишь exact `SELECT` grants на owned fixture tables, без DML/DDL privileges.

### 3. Triple observation: response, state fingerprint и independent write guard

Expected projection задаётся literal fixtures и normalized transcript, а не вычисляется production queries/renderer. До и после каждого сценария harness независимо снимает schema+rows для всех доступных таблиц, file tree и owned session state. Отдельный privileged setup connection создаёт fixtures и ровно четыре runtime accounts, производные от token: `serial`, `concurrent-a`, `concurrent-b`, `sensitivity`; каждый получает одинаковые exact `SELECT` grants. Все последовательные request groups используют serial account строго по одному request/connection за раз и завершают его two-barrier audit до следующего. Concurrent worker A/B получают разные accounts и держат overlap, а sensitivity double использует только свой account.

Test-owned router ставит barrier после установления production DB connection и после получения response, но до teardown. Observer по exact slot username требует ровно один active connection, однозначно фиксирует его `CONNECTION_ID`/`THREAD_ID`, начальный `EVENT_ID`, затем читает заранее включённую `performance_schema.events_statements_history_long` и отклоняет любую DML/DDL attempt, включая denied или rolled-back statement. Для concurrent A/B observer фиксирует оба разных thread до общего release, ждёт оба response barriers и проверяет обе histories до закрытия любой connection. Missing/extra connection в любом slot — `SETUP_FAILURE`. Observer не включает consumers/instruments и не меняет global server state: отсутствие instrumentation, неоднозначный thread или возможное вытеснение history являются `SETUP_FAILURE`.

Альтернативы отклонены: один username для двух concurrent connections неоднозначен; timing/production-owned connection hint допускает self-attestation; before/after-only fingerprint не видит mutate-then-restore; global general log вмешивается в общий server. Per-slot SELECT-only grants предотвращают успешную transient mutation, а независимая statement history делает саму attempt наблюдаемой. Fixture setup остаётся возможным только через отдельное privileged соединение.

### 4. Контролируемое время без production clock seam

Срез не добавляет clock injection в production renderer. Fixtures используют activity branches без относительной даты там, где возможно; clock-relative label проверяется только в bounded before/after window с normalization и retry при смене календарной границы. Exact human phrase не повышается до target semantics.

### 5. Browser JS остаётся source evidence

`control-queue.js` читается как доказательство того, что `mine/all`, search, completed filter, sessionStorage и offline sync происходят после server pagination. Verifier не исполняет JS: это сохранило бы слишком широкую stateful browser surface и пересеклось бы с session/offline slices. Эти эффекты перечисляются как non-goals/hazards.

### 6. Architecture boundary

Owning production module не меняется. Allowed dependencies будущего verifier: test support, loopback PHP process, test MariaDB и public HTTP composition. Production dependency и rapid-pilot mutation counts MUST не расти; `make architecture-check` SHALL оставаться green. Shared hotspots `pilot_e2e_flow_001_test.php`, `pilot_object_list_001_test.php`, `pilot_prepare_form_001_test.php`, `PilotView.php` и `ProductionPilotHttpEntrypointFactory.php` не редактируются.

## Risks / Trade-offs

- [Live relative-time label может дать flaky boundary] → ограничить branch, снять before/after bounds и повторить весь private scenario при пересечении дня.
- [Harness может сам сформировать expected HTML из production output] → literal fixtures, independent DOM/token assertions, raw hashes и reviewer check expected-value independence.
- [Полный DB snapshot может быть нестабилен при чужих агентах] → уникальные prefixes, explicit owned-table inventory и отсутствие глобального snapshot.
- [`performance_schema` history выключена или переполнена] → read-only preflight, per-slot principals, request barriers, per-thread event cursor и `SETUP_FAILURE`; global instrumentation не включать.
- [Наблюдаемая broad visibility будет ошибочно принята за policy] → `PILOT_ONLY` в spec/transcript/evidence и отдельный `NEEDS_GRILL` для target semantics.
- [Параллельный read test может зависнуть] → bounded barrier, deadlines, process reaping и failure cleanup.

## Migration Plan

1. Получить fresh planning review, затем подготовить exact executable spec и owner-approved hash Gate 1.
2. После отдельной apply-команды продемонстрировать RED отсутствующего verifier и получить independent Gate 3 approval.
3. Реализовать минимальный test-only harness, зарегистрировать его ровно один раз в characterization stage и выполнить focused/regression/architecture checks.
4. Получить fresh independent code review; только затем обновить inventory/status и закрыть change.

Rollback test-only: удалить единственную canonical registration и новые verifier/support files; production/schema rollback отсутствует, потому что они не меняются.
