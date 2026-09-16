## Why

Slice A задачи #123 устраняет доказанный T08 setup gap: canonical `run-in-profile` собирает locked Composer dependencies в image, но Yii entrypoints candidate checkout ищут их по repository-relative `vendor/` и останавливаются до проверяемого поведения. Actor — разработчик или существующий CI consumer; source oracle — решение владельца для slice A от 2026-09-16 и T08 gap-check на `main` `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf`; public seam — `tools/delivery/run-in-profile <profile> <command> [args...]`.

## What Changes

- Canonical profile materialize'ит существующий harness frozen candidate snapshot в container-owned read-only application source и предоставляет repository-relative Composer bootstrap из отдельного immutable locked dependency layer, не создавая host dependency paths.
- Project files происходят из exact executed candidate identity, включая additions, deletions и modes; host application root больше не является обязательной runtime composition.
- Запуск fail closed отклоняет несовпадающий lock input и отсутствующий либо повреждённый dependency layer без fallback на host dependencies.
- Добавляется public-route regression A–O для frozen source, post-freeze host mutation, candidate deletion, двух worktrees, allowed writable artifacts, stale host dependencies, lock/corruption failures, существующих profiles и cold/warm repeat.
- Не меняются classification `INTENDED_RED`, worktree identity guard, harness lifecycle, Composer/PHP/Yii versions, product/domain behavior, Docker performance или caching.

## Capabilities

### New Capabilities

- `delivery/container-composer-visibility`: наблюдаемый контракт Composer/Yii dependency visibility для canonical `run-in-profile`.

### Modified Capabilities

Нет. Существующий профиль остаётся public seam, но его capability ещё не архивирована в main OpenSpec store; новый узкий контракт описывается отдельной capability без переписывания завершённой истории change.

## Impact

Изменения ограничены существующим `tools/delivery/run-in-profile`, его focused-check image/layout, executable specification/tests, OpenSpec lifecycle и delivery evidence. `composer.lock`, dependency versions, production runtime, application/domain state и host dependency setup не изменяются. Release value: Yii bootstrap в fresh clean worktree достигает поведения через canonical profile без ручного Composer setup и без cross-worktree mutable state.
