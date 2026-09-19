# Delivery — fix-role-stable-sidebar

## Authorization and authorship

- Owner request: 2026-09-19, исправить по FAST-треку зависимость видимости sidebar от текущего раздела.
- Requested lane: FAST; authoritative lane remains planner-selected.
- Root author: scope, OpenSpec artifacts, verification input and RED regression.
- Executor: отдельный gpt-5.6-sol/low, executable implementation only.
- Final reviewer: отдельный независимый gpt-5.6-sol/low.
- Merge/deploy/settings: not authorized.

## Evidence

- Base: `origin/main@62d027d54af7a01a1da300eda2901ba68b2bab20`.
- Root cause before implementation: `ViewSupport` static fallback and `Views/installers.php` standalone menu bypass canonical `MainNavigation` membership.
- Planner: `CRITICAL`, required reviews `gate3` and `final`; FAST request escalated because the supported classifier does not accept this test/spec delta.
- Focused RED: `php tests/Yii2/yii2_main_navigation_001_test.php` reaches real HTTP and fails on `/pilot/installers`: expected permitted `ОТиЗ`, actual item absent. Earlier missing-vendor, MariaDB and mixed-autoload attempts were setup failures and are not RED evidence.
- Gate 3 review 1: `RETURNED`; исправлены duplicate feedback expectations в трёх restricted matrices и пропущенный normative mapping `installers.read`.
- Gate 3 rereview 1: `APPROVED` для exact source `469c404cb010120f6a52d35882d76b83396fea69135352757f973c6393267016`; reviewer record `reviews/tests/ROLE-STABLE-SIDEBAR-001-gate3.md`.
- Executor author: `/root/sidebar_executor`, `gpt-5.6-sol/low`; changed only `MainNavigation.php`, `ViewSupport.php`, `Views/installers.php`.
- Focused GREEN on implementation: navigation HTTP matrix, verification planner self-test, runtime storage contract and architecture guard. Initial parallel evidence for two PHP commands was conservatively `UNKNOWN` because another focused command mutated its temporary source view concurrently; both were rerun sequentially as GREEN.
- Gate 5 final review: `APPROVED`, no findings, exact candidate `bf83f26bff1053fddb5b6185a48176a80149b9e7a5387974af3eb2b69378d8e8`; reviewer record `reviews/code/ROLE-STABLE-SIDEBAR-001.md`.
- PR and exact-source CI: pending.
