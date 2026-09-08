# Исправление фильтра завершённых объектов стройконтроля

Дата: 2026-09-07. Автор реализации: `/root/auth_review`. Базовый commit:
`c02f1a23121058fb0046e4b8fec859c5754ecb6d`.

## Причина и исправление

На установленном manual-pilot stand объект 966 после принятия акта ПТО и
декларации оставался с `process_state=working`. Очередь стройконтроля определяла
`completed` только по legacy `fm_maintable.ptoactdate`; это поле пусто для native
completion flow. Поэтому renderer выдавал `data-completed="false"`, и исправный
client filter показывал завершённый объект при выключенном переключателе.

`MariaDbConstructionControlQueue` теперь вычисляет завершение по тому же native
контракту, что completion flow: для installation case существуют оба неизменяемых
root fact type — `pto_act` и `declaration`. `process_state=working` остаётся
предикатом состава очереди и не используется как признак незавершённости.
Legacy `ptoactdate` больше не определяет client completion flag.

SQL остаётся у существующего MariaDB read owner. Renderer и `control-queue.js` не
изменены; server RBAC, пагинация, сортировка, local-sync и toggle behavior
сохранены. Read model не пишет facts или process state.

## RED / GREEN

Read-only headless stand repro:

```text
object 966 before toggle: count=1, hidden=null, data-completed=false
object 966 after toggle:  count=1, hidden=null, data-completed=false
browser errors=[]; HTTP failures=[]
```

Это точное замечание владельца: выключенный фильтр не скрывал завершённый объект.
Скрипт только вошёл, открыл очередь и переключил client control; form/command не
отправлялись.

Новый disposable MariaDB + real renderer + headless JavaScript test сначала
падал:

```text
INTENTIONAL_RED: native PTO plus declaration marks completed despite stale working state and empty legacy PTO
Expected: true
Actual: false
```

После исправления:

```text
PASS: construction-control native completion filtering
```

Тест одновременно доказывает: completed `working` case скрыт по умолчанию и
появляется после включения toggle; незавершённый 85-percent case виден по
умолчанию; completion facts byte/value-equivalent до и после read/render/browser
цикла; fixture database удаляется.

## Проверки

```text
tests/InstallationProcess/construction_control_completed_filter_001_test.php
PASS: construction-control native completion filtering

tests/AssignmentOrderComposition/manual_checklist_http_smoke_test.php
PASS manual HTTP applied composition -> checklist -> photo -> correction -> completion

rapid-pilot/verify-auth-hot-path.php
PASS auth hot path is schema-mutation free

tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)

PHP lint production/test, Node syntax browser helper, focused git diff --check
PASS
```

## Exact artifacts

```text
6099cac5eab80cdc94c3f363db94d71ede025ebf53c3853f714f8a027bbaf322  app/PilotHttp/MariaDbConstructionControlQueue.php
4c5a0c7311cb6e8b143d71f392e6bb3db7d995f6bd9b0fdef678257f1dc5beae  tests/InstallationProcess/construction_control_completed_filter_001_test.php
98a05b9ffb94f82383f3ed6ab0b55275279c8fdde176df567a71774bd1ab79b5  tests/Support/construction_control_completed_filter_browser.cjs
4e605315f49380f885d5effc0d29c287a967bf8c272f17d89fd630de87490da2  production git diff --binary
c99a9453a3a63c5b59979ef14ff0ff9af8401572710382c9fe37d3d1134af176  test new-file diff --binary
5b49731761d454a3938c9afab92898d637935ff58ec02f0936c26b7f5267baac  browser-helper new-file diff --binary
```

Private real-stand evidence:

```text
4882459ab869b1b4da4176dd88ad7495fca0c3b0bf40ebf01ad5b622e3886570  construction-completed-filter-before.json
d24fa33b59db9fb8023e61b79b7453d2246509d1f20353f527a40e4cef4ae9ac  construction-completed-filter-toggle-off.png
ef2607044d7be9f1f2eb4e377616bf56ee139311c2b5608c4eda290582f6ce8e  construction-completed-filter-toggle-on.png
```

Независимый code review, commit и deployment остаются следующими шагами. Global
reset, real DB mutation, stand restart, remote/CI и Bitrix actions не выполнялись.
