## Context

I2 реконструирован один раз на fresh main поверх exact I1 predecessor. Scope
закрыт issue #110 и нормативным I2 contract.

## Goals / Non-Goals

Цель — reproducible governance/integration/browser focused checks, observed
readiness и isolated owned resources. Не цели — I3 snapshot/review orchestration,
I4 resume/closeout, продуктовые изменения, merge, deployment и settings.

## Decisions

Root владеет spec/tests; отдельный sol/low executor реализует после независимого
Gate 3; независимый reviewer решает Gate 5. Максимум одна test-review correction,
две source invalidations и один полный core/routes run на frozen source. После
лимита — NEEDS_OWNER. Findings вне I2 оформляются follow-up issues.

## Verification

Локальный mapped пакет ограничен boundary и verification-ci. Canonical stage
проверяется отдельным container witness и полным exact-source CI, а не host route.
Тяжёлые core/routes self-tests остаются зарегистрированы для exact-source CI при
изменении delivery infrastructure и не выбираются обычным product PR. Применимый
bounded #90/#99 inventory выполняется targeted witnesses. Полный suite локально
запрещён; один exact-source CI возможен только после Gate 5.
