## Context

Quality Graph в репозитории сейчас описывает и публикует CI reporting. Его нельзя
представлять как upstream planner. `ci.py plan` выбирает docs-only/full CI, но не
вычисляет Gate 2 obligations и не видит staged/unstaged/untracked работу.

## Decisions

CLI остаётся stdlib-only. Policy — versioned JSON с ordered, mutually exclusive
boundary globs, required categories/tests и runtime-by-test-suffix. Change input
явно отображает spec IDs и public seams на test paths; тест может планироваться
до создания файла. Existing tests сверяются с category inventory.

Canonical plan включает только данные и argv. `check` строит ожидаемый документ
заново и сравнивает его целиком, поэтому omission/tampering и любой bound drift
дают один fail-closed путь. Git snapshot объединяет base triple-dot committed
diff, index, worktree и untracked. Rename не скрывает старую или новую boundary.

Focused execution использует `subprocess.run(argv, shell=False)`, прекращается на
первом RED и возвращает child status. Full command остаётся отдельной integration
phase и не исполняется focused режимом. CLI не управляет DB lifecycle.

## Risks / Trade-offs

Explicit acceptance input со stable acceptance ID/spec path требует дисциплины автора, зато остаётся reviewable до
появления тестового файла. Unknown boundary блокирует Gate 2 до изменения policy.
Это намеренно conservative и ограничено repository-owned extension.
