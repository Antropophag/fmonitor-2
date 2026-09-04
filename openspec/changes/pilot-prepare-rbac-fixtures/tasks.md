## 1. Gate 1 — executable fixture contract

- [x] 1.1 Подготовить exact `PILOT-PREPARE-RBAC-FIXTURES-001` spec для GET/HEAD path с раздельными local permission/process capability gates, cross-route/method denial, precedence, snapshots и cleanup; verification: independent Gate 1 review READY_FOR_OWNER_APPROVAL.
- [x] 1.2 Получить explicit owner approval reviewed hash до tests; verification: отдельная approval record.
- [x] 1.3 Амендировать Gate 1 по GRILL-009 узким factory-owned renderer decorator: production identity, test spy wraps/delegates canonical real renderer, manual graph запрещён; verification: fresh independent Gate 1 review и new exact-hash owner approval.
- [x] 1.4 Зафиксировать exact public PHP decorator/factory signatures, identity/default semantics и factory-owned real-renderer wrapping; verification: strict-valid coherent artifacts получают fresh independent Gate 1 review и новый exact-hash owner approval, прежнее approval не переиспользуется.
- [x] 1.5 Уточнить PHP built-in transport boundary: fully delivered
  `PUT|PATCH|DELETE` сохраняют exact public 405/Allow, no payload leak,
  exactly-one composition `decorate()`, zero request-time wrapped-renderer
  render/mutation и admission-before-domain/form work, но app contract не
  заявляет transport no-read; verification: strict validate, fresh independent
  Gate 1 review и explicit owner approval новых exact hashes. Прежние
  approvals и Gate 2/3 reviews остаются historical.
- [x] 1.6 Согласовать artifacts с owner-approved upload-first GET
  `PILOT-PREPARE-FORM-001 v0.2`; verification: strict OpenSpec, fresh
  independent Gate 1 review и exact-hash owner approval до tests.

## 2. Gates 2–3 — RED и review

- [x] 2.1 После owner approval v3 переподтвердить public exact GET/HEAD
  RED и fully-delivered unsupported-method matrix по amended hash: exact 405/Allow,
  no payload leak, one composition-time `decorate()`, zero request-time wrapped
  renderer/mutation и application admission до domain/form work;
  verification: прежний RED v6 и review v6 только historical.
- [x] 2.2 Передать tests независимому reviewer; verification: APPROVED hashes, route mapping и no-fallback/no-ambient-env sensitivity.

## 3. Gate 4 — minimal fixture GREEN

- [x] 3.1 Подключить exact prepare GET/HEAD route к stable local authorization seam перед process-capability/form reads и seed-ить canonical actors/roles/local permission + отдельную process capability; verification: prepare verifier GREEN, negative cases не получают positive env.
- [x] 3.2 Сохранить POST command untouched, full process/artifact/audit snapshots и redaction; verification: rejected GET cases byte-equivalent кроме authority fixture.

## 4. Verification, Gate 5 и Done

- [ ] 4.1 Запустить prepare/local-auth DB suites, architecture, lint и full verify; verification: owned failures исчезли без assertion weakening.
- [ ] 4.2 Получить independent code review APPROVED; test changes возвращают Gate 2.
- [ ] 4.3 Обновить operations status и Done только после Gates 1–5 и strict OpenSpec.
