## 1. Gate 1 — executable security contract

- [x] 1.1 Написать и одобрить исходный `PILOT-SESSION-STORAGE-001`; historical verification: pre-amendment exact hash был owner-approved, но больше не разрешает Gate 2/3/4.
- [x] 1.2 Получить owner decision GRILL-009 на public factory/result DTO, exact filesystem events/faults, pause/crash hooks, clock/entropy и read-only Compose inspector; verification: append-only decision exists.
- [x] 1.3 Исторически: получены fresh independent Gate 1 review и owner approval v2 package, включая non-secret `entryKeySha256`; это approval не покрывает новый exact public PHP API amendment.
- [x] 1.4 Получить fresh independent Gate 1 review v5 executable spec/OpenSpec package с syntactically valid exact config/factory examples, exact owner/filesystem primitive, explicit `public static function` primitive/entropy result and handle creation, handle/stat ownership, clock/entropy и observer/event/result PHP signatures, затем explicit owner approval нового exact hash; verification: READY_FOR_OWNER_APPROVAL review + append-only exact-hash decision. До этого tasks 2.2+ закрыты.
- [x] 1.5 Получить fresh independent Gate 1 review v7 и exact-hash owner approval для exhaustive public API: backed event/failure enums, explicit raw-HTTP injected-dependency composition, deterministic-clock-only contract, exact inspector class/CLI/JSON/exit protocol и constructible owner/event/inspection results с restricted call sites; verification: READY_FOR_OWNER_APPROVAL review + append-only decision. До этого tasks 2.2+ снова закрыты.
- [x] 1.6 Получить fresh independent Gate 1 review v8 и exact-hash owner approval узкого inspector CLI application seam: injected inspection/filesystem/argv/output ports, unconditional native production binding и deterministic `64|65|70` mapping; verification: READY_FOR_OWNER_APPROVAL review + append-only decision. До этого tasks 2.2+ снова закрыты.
- [ ] 1.7 Уточнить exact successful-start payload handoff (`ownerStarted(id,
  payload)` + nullable `sessionPayload()`), bounded whole-array codec и
  `PAYLOAD_INVALID`, запретить second-owner filesystem
  read в HTTP consumers и получить fresh independent Gate 1 review; прежние
  Gate 2–5 approvals не покрывают amendment.

## 2. Gates 2–3 — RED и independent review

- [x] 2.1 Зафиксировать pre-amendment RED и два CHANGES_REQUIRED review; historical evidence only, old Gate 3 applicability reset by GRILL-009.
- [x] 2.2 Заменить self-attesting dispatcher на tests, которые вызывают real owner public factory и независимо наблюдают exact backed-enum before/after primitive events, material inode/bytes state, closed-code injected faults, pause/kill crash boundaries, deterministic clock values, entropy failure и immutable result DTO; verification: every normative phase reaches intended RED that production alone can make GREEN.
- [ ] 2.3 Добавить real LocalAuth+UserAccessView raw HTTP через explicit injected-dependency factory method, включая exact owner-read payload handoff без второго filesystem owner, exact unknown-route `404` before session/config/auth, asset/Host/URI priority, cookie/CSRF/return-to/old-ID/GC и exact CLI/class read-only inspector, который не выдаёт literal basename/session ID, сортирует exact `entryKeySha256`, emits exact canonical envelope/exit codes и fail-closed на duplicate; CLI runner детерминированно доказывает `64|65|70`; плюс actual Compose stop/start preservation; verification: fresh independent Gate 3 APPROVED exact hashes and no test-owned success claims.

## 3. Gate 4 — minimal GREEN

- [x] 3.1 Реализовать one IdentityAccess storage/session handler with explicit atomic commit and typed results; filesystem matrix GREEN.
- [ ] 3.2 Подключить оба consumers, response buffering, Compose compatibility config and task-owned harness; protocol suites host+image GREEN.
- [x] 3.3 Добавить ratchet against alternate native session primitives/hardcoded paths/unsafe repair и against owner/event/inspection `@internal` factory calls outside exact owner/inspector classes; architecture targeted fixtures GREEN.

## 4. Verification, Gate 5 и Done

- [ ] 4.1 Запустить exact login/user-access/CSP/RBAC tests, host/image, Compose stop/start cookie, lint, architecture, fresh lifecycle and full verify; distinguish setup/regression and prove cleanup.
- [ ] 4.2 Получить independent code review APPROVED; test changes restart Gate 2.
- [ ] 4.3 Обновить runbook/status and Done only after Gates 1–5, strict OpenSpec, safe persistent restart and no foreign cleanup.
