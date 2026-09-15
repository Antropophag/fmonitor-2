# Текущая цель — №116, advisory file-size architecture signal

Поручение владельца 2026-09-15: реализовать [№116](https://github.com/Antropophag/fmonitor-2/issues/116) от актуального `main` `3c4dd015` и довести один bounded candidate до PR-ready. Scope: перевести физический размер production-файла из blocking hotspot ratchet в видимый human- и machine-readable advisory, сохранив все содержательные architecture ownership rules fail-closed. При необходимости сузить size-baseline update так, чтобы он не принимал unrelated exceptions.

Не входят production refactor/decomposition, массовое форматирование, новый analyzer/framework/dashboard/registry/Gate, rebaseline unrelated debt и issues №153/№132/№136/№141/№145. `rapid-pilot/` не читать и не изменять, кроме непосредственно применимой forbidden-dependency fixture; cleanup не выполнять. Verification inventory менять только штатной регистрацией нового canonical test. Merge/deploy/settings не выполнять.

Контракт: `specs/ARCHITECTURE-FILE-SIZE-ADVISORY-001.md`. Lifecycle: [advisory-architecture-file-size](../../openspec/changes/advisory-architecture-file-size/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают требуемые planner-ом gates. Локально только planner-selected focused checks; full `make test`/`make verify` запрещён.

Предыдущий merged указатель №150 сохранён Git history. Фактические source/PR/CI/lane получать через harness state и активный package.
