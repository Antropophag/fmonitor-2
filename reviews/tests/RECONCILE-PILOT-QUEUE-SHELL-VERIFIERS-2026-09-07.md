# Current queue/shell verifier reconciliation — review draft

Дата: 2026-09-07
Статус: **READY_FOR_INDEPENDENT_REVIEW**

## Historical RED

Последний полный verify зафиксировал predecessor failures:

```text
pilot_object_list_001_test.php:280
normal object-list success has no pagination nav/control/class/data/copy/query state
Expected: []
Actual: [copy=Показано ]

pilot_ui_shell_001_test.php:44/74
shell identity
Expected: 1
Actual: 0
```

Первый test ожидал semantic list без controls/pagination; второй продолжал
запрашивать configured root «Моя работа» и старые disabled navigation pairs.
Owner-approved manual UI использует objects-first shell, rich queue table,
server-side q/status/page и полный permission-driven profile.

## Reviewed intent

Delta spec:
`openspec/changes/reconcile-pilot-queue-shell-verifiers/specs/verification/pilot-queue-shell-current/spec.md`.

Object-list test теперь требует native rich table/current count и сохраняет
полный unfiltered set, ignored query и safety ceiling 500/501. Exact object IDs,
registration/address/dates, local RBAC/revoke, GET/HEAD parity, CSP, no origin
leakage и DB no-mutation сохранены. Foreign sentinel исключает только atime,
который меняет собственное чтение test; bytes/hash/mode/uid/gid/mtime остаются.

Shell test сохраняет configured native root body «Моя работа», но требует отсутствие такого destination в navigation, проверяет current sidebar user
identity, exact permission-driven links, три muted
non-link направления, exact scripts/styles/CSP, skip link, escaping и отсутствие
inline execution. Manual rapid root redirect проверяется отдельным E2E.
Сохранены запреты preload/remote resources, безымянных links/buttons и dangerous
URLs; muted items обязаны быть не `a/button`, без href/role/tabindex. Queue scenario требует native table/count и exact fixture
facts вместо semantic list. Card script manifest сохраняет approved ES module.
Compatibility root отдельно проверяется и при configured, и при отсутствующем pilot CSS; ни один случай не восстанавливает My Work navigation destination.

Production и protected E2E не изменялись. DB tests до независимого review не
запускались первоначально; ошибочно расширенный diagnostic RED на rapid-only filters/root redirect был отклонён после подтверждения двух seams. Tests запускают native `public/router.php`; manual q/status/page/root redirect проверяются отдельно через `rapid-pilot/router.php`. Setup/DB cleanup завершились;
production не менялся. PHP lint и `git diff --check` PASS.

## Exact artifacts

```text
8b0eb6973e7caa442afd185ec072fc5191b56ffa83b8d30e1861a19f12ba2815  tests/InstallationProcess/pilot_object_list_001_test.php
fcb721039c2138f325581a20b91a7c4ecf3e48f53fc6bcc1e316102036d3055b  tests/InstallationProcess/pilot_ui_shell_001_test.php
953fcc945e41656cd849e7018448e27d475bfef07233469b98f61fb38335d6ba  openspec/changes/reconcile-pilot-queue-shell-verifiers/specs/verification/pilot-queue-shell-current/spec.md
```

Reviewer должен проверить, что literal field/row assertions остаются sensitive,
navigation visibility следует exact permission map и старые security/no-write
assertions не потеряны. Этот draft не является approval.
