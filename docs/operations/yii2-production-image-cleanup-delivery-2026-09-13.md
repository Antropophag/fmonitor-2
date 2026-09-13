# №76 — production image cleanup, delivery record 2026-09-13

## Scope и авторство

OpenSpec `yii2-production-image-cleanup`, контракт
`YII2-PRODUCTION-IMAGE-001`. Root подготовил scope/spec/tests; отдельный
`/root/executor_image_cleanup` (`gpt-5.6-sol/low`) изменил production recipe;
независимый `/root/gate3_image_cleanup` (`gpt-5.6-sol/low`) вынес Gates 3/5.

Срез удаляет `COPY rapid-pilot` и build-time legacy visual verifier только из
production runtime Dockerfile. Repository demo oracle, pilot image, stand,
schema/data и deployment не меняются.

## Gate 2/3

Первый RED record:
`1789282383313391000-6bf395a6244e4af7ad8a533d92ae22bf` — canonical
production recipe сохранял `rapid-pilot`. Первый Gate 3 вернул
`CHANGES_REQUESTED`: secret-like inventory был недостаточно чувствителен.
Correction RED `1789282572169597000-70eaf8e7fa4f48de9a646c3ead6c46b7`;
explicit inventory добавлен; Gate 3 `APPROVED`.

После implementation real-live fixture сначала дал честный
`REGRESSION_FAILURE` record
`1789282794988370000-0f0533f3da0945878e76f21f2eee9120`: test не передавал
обязательный Yii `cookieValidationKey`. Root заменил pseudo-request на реальный
HTTP smoke и передал fixture-only key; correction Gate 3 `APPROVED`. Production
secret не встраивается в image.

## Gate 4/5 evidence

Exact reviewed candidate `7da4f52a33de8b825878e5038ec9cb851e103e2b9a9469a5d3ca355106082aa0`,
executable source `154c3fc09ac4a9462ec63f7287ca7a11ecd84fa5024f12c0926ae28c027ad922`.
Focused records GREEN:

- image closure `1789283536421869000-bb4959c4c7fe4835afefb5c103c8ea56`;
- jobs compose `1789283601948665000-138aa961a976416a8baf9c44b80a7761`;
- change verification `1789283657642238000-e3b0a6945c1f4b63ac1dcfda4b5649b2`;
- architecture guard `1789283682937966000-c26201d6250c43548074ab3df6f4d82c`;
- generated Dockerfile `1789283708510481000-8c5bd33dbd384af59da04a3248b39386`;
- verification CI contract `1789283546425358000-c8ee870a058748d7acc337a25005d55c`.

Gate 5 `APPROVED`, findings `None`. Review package:
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T071713Z-b0701a69da/package.json`.
Полные логи остаются вне checkout.

## Открытые gates

PR/CI на момент записи `UNKNOWN`; локальный full suite не запускался по решению
владельца. Один exact-source full Quality Graph обязателен до merge. Deployment,
demo retirement, общий upgrade/rollback rehearsal и полное закрытие №76 остаются
отдельными работами.
