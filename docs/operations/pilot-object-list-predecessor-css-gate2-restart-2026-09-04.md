# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — возврат predecessor CSS fixture в Gate 2

- Date: `2026-09-04`
- Gate: `2`, test-only correction
- Production changes: none
- Baseline: `75a642476224abe9ec99905777164b4279e743a7`
- Prior approved integration RED: `513d00debbf547e74bb35be9656627405fc913e0`
- Prior Gate 3 v11: `8f531a86726d4ddfb60382c5e829069104c21a90`, historical after this test change

## Repository/checkpoint reconciliation

Переданный checkpoint называл локальную ветку `codex/session-route-admission` с
head `3ae214f75b898d171c68bb127dec10f17e03117a`. Фактически checkout начинался с
чистого `main`/`origin/main` на `2bff0a0`. Object-list test/review commits не были
его предками и находились в `origin/codex/remove-pilot-work-navigation-v2` с head
`75a642476224abe9ec99905777164b4279e743a7`. Работа продолжена на локальной
tracking branch с этим именем; PR #10 не изменялся и не merge-ился.

## Причина Gate 2 restart

`tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php`
передавал `rapid-pilot/pilot.css` как `FMONITOR_SHLZ_CSS_PATH`. Public
`ShlzCssAsset` принимает только absolute regular asset с exact basename
`shlz.css`; следовательно этот positive real-handler fixture был stale и честная
последующая CSS validation превращала ожидаемый `200` в setup-like `503`.

Test-only correction теперь:

- читает public export `../shlz-ui/packages/styles/shlz.css`;
- копирует все его bytes в verified task-owned root под exact basename
  `shlz.css` и проверяет canonical identity/hash;
- использует этот путь только в positive/read-capable composition;
- оставляет auth-only composition без SELECT к business tables и с отсутствующим
  downstream path `unavailable/shlz.css`, поэтому denial обязан завершиться до
  CSS и object reads;
- регистрирует cleanup сразу после создания owned root, включая setup failure.

## Доступная проверка и RED status

```text
$ git diff --check
# exit 0, empty output

$ sha256sum tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
5e29a959970faa7b7e8220dc4e114560f3f665e3faca0ab308dec6eac83b4914

$ php -l tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
zsh: command not found: php

$ docker info
zsh: command not found: docker
```

На этом executor нельзя честно повторить predecessor GREEN и unchanged current
integration RED (`healthy local RBAC + missing CSS`: expected `503` +
`Retry-After: 60`, actual `200`; затем query-equivalence RED). Поэтому эта запись
не объявляет `QUALIFYING RED`, Gate 3 approval или Gate 4 authorization. Fresh
independent reviewer должен либо воспроизвести команды в пригодном PHP/MariaDB
runtime, либо вернуть `CHANGES_REQUESTED`; production остаётся неизменным.
