# Актуальный прогресс на вкладке ОТиЗ — 2026-09-07

## Замечание и воспроизведение

Владелец сообщил: после полного завершения работ по объекту прогресс на вкладке
ОТиЗ не отображается. Read-only headless Chromium на установленном стенде открыл
`/pilot/otiz` и нашёл единственную строку объекта №966 со значением:

```text
0%
Прогресс не подтверждён
state=planned
```

HTTP status 200, browser/page/request errors отсутствовали. Ни один form, расчёт,
payment или другое изменяющее действие не выполнялось. Private evidence:
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/otiz-progress-readonly-20260907.{cjs,json,png}`.

Причина: экран показывал только `current_progress_bp` последнего сохранённого
ОТиЗ snapshot. Native checklist operations и completion facts, появившиеся после
snapshot либо до первого расчёта, в display projection не участвовали.

## Исправление

Новый read-only owner `app/PilotHttp/MariaDbOtizCurrentProgress.php` использует
тот же `MariaDbChecklistProgress`, что карточка и очередь, и читает наличие native
`pto_act`/`declaration`. Display progress равен подтверждённому checklist весу до
85%; ровно 100% показывается только при наличии обоих документальных фактов.
Дата отображения берётся из последнего server-received checklist либо completion
fact.

`rapid-pilot/Otiz.php` применяет эту проекцию только к колонке «Прогресс» и её
дате. Сохранённые snapshot progress, accrued/pool/fund, calculation state,
eligibility, выплаты и сводные суммы не изменяются и не вычисляются заново.
RBAC и route admission остаются прежними.

Техническое состояние `planned` также сохранено без изменений, но его прежняя
подпись «Работы не начаты» была неверна для завершённого объекта без расчётного
snapshot. В строке и фильтре она заменена на «Расчёт не подготовлен». Эта подпись
не означает готовность к расчёту или выплате и не меняет финансовый state.

## Focused evidence

Новый disposable DB test проверяет точные значения:

```text
два пункта весом 2% + 2%                         → 400 bp
полный checklist + ПТО, но без декларации       → 8500 bp
полный checklist + ПТО + декларация             → 10000 bp
```

Результаты:

```text
otiz_current_progress_001_test: PASS
PHP lint: PASS
git diff --check: PASS
PILOT-HTTP-AUTH global-call qualification: PASS
ARCHITECTURE CHECK PASSED (7 rules)
```

`rapid-pilot/verify-otiz-workflow.php` из host environment не запустился, потому
что его fixture ожидает Compose DNS hostname `mariadb`; зафиксирован exact setup
failure `php_network_getaddresses: getaddrinfo for mariadb failed`. Проверка не
ослаблялась и не выдаётся за GREEN.

Все source/test files имеют mode 0644. Stand, объект №966, production data,
Bitrix, payments, CI и remote state не изменялись. Это focused implementation
evidence до независимого review и deployment.
