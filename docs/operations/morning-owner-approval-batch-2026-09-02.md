# Morning owner approval batch — 2026-09-02

Каждый пункт независимо прошёл technical review
`READY_FOR_OWNER_APPROVAL`. Ответ можно дать одной строкой:

```text
Согласовано всё из morning-owner-approval-batch-2026-09-02
```

Либо перечислить номера, например `Согласовано 1, 2, 4; пункт 6 обсудить`.
Approval относится только к exact hashes ниже, разрешает Gate 2 и не одобряет
tests/code/Done.

## 1. Classification provenance v11

Простыми словами: canonical migration заранее создаёт точную таблицу источника/
классификации, import больше не делает DDL и при missing/drift останавливается до
чтения source/output. Existing output-without-provenance window только
характеризуется, не принимается как target behavior.

```text
a044645fac8c347e98ae876f1dfdb98c12944a1c4fde85a098f99b6a84be71ed  specs/CLASSIFICATION-PROVENANCE-SCHEMA-001.md
```

Review: `classification-provenance-schema-gate1-rereview-v2.md`
(`6a731ecb...`). Recommendation: **согласовать**.

## 2. Object-list local RBAC fixture

Простыми словами: positive `GET /pilot/objects` test получает canonical active
user/role/exact `objects.read`; legacy-only/missing/inactive/near-match/revoke
cases fail closed до list read. Card/UI/prepare не входят.

```text
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
```

Review `7f5fb2ce...`. Recommendation: **согласовать**.

## 3. Prepare GET/HEAD local RBAC migration

Простыми словами: форму можно читать только при двух разных основаниях — local
role permission и existing process capability `assignment_order.prepare`.
POST/CSRF command остаётся прежним и вне slice.

```text
565804719e95171fa82523f6f883b8abebc9d8f0e36ca9746612fb8f7daab01e  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
```

Review `ec12c7e1...`. Recommendation: **согласовать**.

## 4. E2E object-list RBAC fixtures

Простыми словами: actor18 проходит список по local `objects.read`; actor19 имеет
только legacy identity и получает 403. Revoke проверяется в отдельной branch и
не ломает main golden journey. Command routes остаются predecessor contracts.

```text
83dee68e5df98c3a51d895e4d8c0d2f712cfc4e3bd3ce0f2af3d6217510f0217  specs/PILOT-E2E-RBAC-FIXTURES-001.md
```

Review `d7aea03f...`. Recommendation: **согласовать**.

## 5. Combined PDF / E2E v0.5 — joint approval

Простыми словами: golden journey имеет одну ссылку и один immutable three-page
PDF с распоряжением и приложением. Старый appendix HTML route/metadata не
поддерживается. Exact GET/HEAD/403/404/503, decoder, fault/reload/concurrent
reads and cleanup fixed.

```text
c792b7bd3c707b0b9bd4fe2e934c677d44235ce2da41839688383391d47f3ec5  specs/PILOT-E2E-FLOW-001.md (v0.5)
a28c7a8bfeabdf9f41bc05ac4f17faa22c3ef2956c62573323f83e9dc809ebd3  specs/PILOT-E2E-COMBINED-PDF-001.md
```

Review `9410b6f8...`. Recommendation: **согласовать совместно оба hash**.

## 6. Pilot session storage

Простыми словами: LocalAuth и UserAccessView используют один configured owned
session root вместо hardcoded `/home/fmonitor`. Exact modes/locks, no-clobber
files, fail-safe regeneration tombstone, explicit commit-before-response,
GET/HEAD/POST 503, cookie/GC/Compose restart and cleanup fixed. Crash может
безопасно разлогинить, но не оставить два valid session ID.

```text
2afa029374583b18ed06d6eb37f8c9e3857b3366ac5e516f1eb3b07de8ba8ad0  specs/PILOT-SESSION-STORAGE-001.md
```

Review `78d4bca4...`. Recommendation: **согласовать**.

## Consequence of no response

Все шесть Gate 2 остаются закрыты. READY work смещается к другим planning/
discovery tasks; никакие tests или production changes по этим contracts не
начинаются.
