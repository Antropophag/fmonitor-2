## Context

Это первый из трёх UI slices. Владелец явно подтвердил декомпозицию после Gate 3 findings о слишком широком cross-product. `../shlz-ui` остаётся read-only oracle.

## Goals / Non-Goals

**Goals:** shared tokens/compositions и полная object-card journey.

**Non-Goals:** ОТиЗ, queue, construction control, checklist, completion, installers и access admin; они обязательны в двух follow-up changes.

## Decisions

1. `shlz.css` владеет primitives; `pilot.css` — bounded compositions.
2. Mobile-first DOM order; desktop 8/4 work/context grid; no visual reorder.
3. Corporate motion 120/220/360 ms только для feedback/continuity, с reduced-motion fallback.
4. Public HTTP/browser tests владеют acceptance; persistence owner и rapid-pilot не меняются.

## Risks / Trade-offs

- [Foundation ещё не применён ко всем routes] → follow-up changes применяют его без повторного redesign.
- [Shared CSS regression] → existing route browser tests остаются regression checks.

## Migration Plan

RED/review → executor implementation → bounded desktop/mobile screenshot pass → focused checks → final review → exact-source CI. Rollback — previous commit/image; DB rollback не нужен.
