# Текущая цель — №153 Slice B, consumer/ownership frontier

Поручение владельца 2026-09-16: реализовать только bounded Slice B [№153](https://github.com/Antropophag/fmonitor-2/issues/153) от актуального `origin/main` `b9dfcb4d9d1cdd934f16fa4a9f4910464f1ddfc6` и довести отдельный candidate до PR-ready без merge.

Scope: существующий change-verification planner детерминированно расширяет frontier от изменённого protected capability/invariant до всех зарегистрированных direct/transitive consumers, выдаёт machine-readable causal chains и fail closed для missing/stale ownership или unregistered verifier. Slice A conservative integration closure сохраняется; local presentation-only change не расширяется.

Контракт: [CONSUMER-OWNERSHIP-FRONTIER-153-B](../../specs/CONSUMER-OWNERSHIP-FRONTIER-153-B.md). Lifecycle: [expand-consumer-ownership-frontier](../../openspec/changes/expand-consumer-ownership-frontier/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; full `make test`/`make verify` запрещён. Exact-source GitHub CI выполняется один раз.

Не входят #153C/D, T07a+/#107, fixture-reachability safeguard, product changes, новый evidence store, новый planner/global dependency framework и issue-specific policy. Frozen #20 branch `codex/issue-20-control-engineer-import` at `0e34bb7e2f72e7ac766fc8d364abf6e420d84768` не изменяется. После Slice B остановиться; merge/deploy/settings не выполнять.
