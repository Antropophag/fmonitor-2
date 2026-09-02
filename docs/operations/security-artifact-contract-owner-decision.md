# Owner decision — local RBAC, route-scoped CSP and combined PDF

Date: 2026-09-02  
Decision: **APPROVED_ALL**  
GRILL: `GRILL-002`

## Local RBAC

Для TEST-USER contour authoritative является local RBAC: active user, active
activation, active role и exact permission. Legacy directory/roles могут быть
import evidence, но не являются fallback authorization и не дают implicit
access по имени или только по факту authentication.

## Route-scoped CSP

`script-src 'self'` разрешён только на явно allowlisted successful 2xx HTML
routes, которым нужен same-origin external JavaScript. Inline scripts, `eval`,
third-party scripts запрещены. Errors, redirects, assets и script-free HTML
сохраняют строгую CSP без `script-src`. Существующий inline fragment должен быть
вынесен во внешний asset либо удалён через отдельный reviewed slice.

## Combined assignment-order PDF

Публичный artifact contract — один versioned combined PDF распоряжения вместе с
приложением. Отдельные `order` + `appendix` HTML artifacts не поддерживаются.
Product/pilot/E2E документация и verifiers должны быть синхронизированы с уже
утверждённым `ARTIFACT-STORE-001` без добавления параллельного legacy contract.

## Unblocked slices

- `LOCAL-RBAC-AUTH-CONTRACT-001`;
- `PILOT-OBJECT-READ-RBAC-FIXTURES-001`;
- `PILOT-PREPARE-RBAC-FIXTURES-001`;
- `PILOT-E2E-RBAC-FIXTURES-001`;
- `PILOT-ROUTE-CSP-001`;
- `PILOT-E2E-COMBINED-PDF-001`.

Каждый slice всё ещё обязан отдельно пройти executable spec, RED, independent
test review, minimal GREEN и independent code review.
