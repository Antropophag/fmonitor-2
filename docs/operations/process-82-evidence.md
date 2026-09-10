# #82 — исправление процесса и фактическая проверка

## Scope / authorship

Владелец 2026-09-10 включил автономное выполнение текущего поручения: #82, затем
#76. Root выполняет анализ, spec/tests и проверку полноты; отдельный sol/low
исполнитель реализует CLI, независимый sol/low reviewer выполняет Gates 3/5.
Это сохраняет проверяемое обычное распределение авторства даже в автономном режиме.

## Instruction audit

Проверены AGENTS.md, docs/development-process.md, review templates,
tools/delivery/{handoff-template,change-verification}.md, openspec/config.yaml,
локальные OpenSpec skills и writing-for-agents. Независимый read-only audit:
`/root/process_audit` (sol/low), исходный main `b58852a9`.

Источники неоднозначности и исправления:

- «One executor owns a vertical slice» не задавало автора spec/tests: active
  AGENTS + process теперь определяют root, исполнителя и явное разрешение режима.
- «Reviewed commit» вместе с «exact source» не объясняло промежуточные правки:
  templates принимают commit или восстановимый base/patch/digest, процесс задаёт
  содержательные checkpoint без квоты коммитов.
- «Smallest test / after each change» читалось как отдельный gate на assertion:
  Gates 2/4 теперь различают малые RED циклы и цельный review candidate.
- Неполнота матрицы/зависимостей оставалась reviewer: root проверяет полный
  контракт, schema consumers (включая rapid verifiers), fixtures, runtime,
  deployment/readiness/restore/inventory до Gate 2, с кратким N/A где уместно.
- Повторные review и CI: один полный список findings, delta rereview с причиной
  расширения, пересборка матрицы при втором completeness return; полный failure
  inventory до исправлений, обоснование same-source retry, фактический статус.
- OpenSpec apply отмечает задачи по одной, но не требует commit/review на задачу.
  Инструкции skill не менялись: проблема была в интерпретации. Repo config теперь
  отсылает к правилу авторства/checkpoint и актуальной очереди вместо старого milestone.

## Bounded proof

[REVIEW-SOURCE-001](../../specs/REVIEW-SOURCE-001.md) — реальный CLI для сохранения
и восстановления локального review candidate. Матрица, зависимости и полный
набор тестов подготовлены root до первого независимого review.
Первый содержательный checkpoint `934b58f5` (2026-09-10 02:25:13 +03:00).
RED: шесть тестов падают с `INTENDED_RED: REVIEW-SOURCE-001 capture/restore public
seam is absent`, fixture Git setup проходит. Команда:
`python3 tools/delivery/change-verification.py run --plan .local/verification/82-plan.json --phase focused`.
Первый вызов генерации plan без созданного output parent завершился setup failure;
каталог создан и plan успешно получен до написания тестов. Это не RED evidence.

## Comparison status

Исторический checkpoint #82: 28 review verdicts (14 returns), 74 commits,
почти два часа по оценке владельца; это не 28 CI runs. Новый инструмент существенно
меньше финансовой миграции, поэтому прямой коэффициент производительности и
денежную экономию из этих чисел выводить нельзя. Токены/стоимость не измерены.

Gate3: первый verdict CHANGES_REQUESTED — тесты отказов не доказывали сохранность
source/snapshot/чужих данных. Root добавил полное сравнение before/after и повторил
RED. Delta rereview APPROVED; оба вердикта сохранены в одной
[записи](../../reviews/tests/REVIEW-SOURCE-001.md). Исправление передано как base
`934b58f5` + `/tmp/82-gate3-fix.patch`, SHA-256
`ea2ab6e1e1edeb66d276eee86bd3c084ea526ddf7a01595f6d19b06e23587764`, без отдельного
коммита. Это один реальный возврат по чувствительности теста, а не нулевая доработка.

Исполнитель `/root/implement82` поставил CLI и registration. Focused acceptance
6/6, planner 12/12, architecture guard 59/59, CI selection 15/15 прошли. Root
выполнил `make architecture-check`: HTTP qualification PASS, 7 rules PASS.
Интеграция регистрации выявила ожидаемое отклонение исторического unit baseline:
root добавил новый тест в явный список additions (исторический digest сохранён).
RED `/tmp/82-inventory-red.log`, GREEN `/tmp/82-inventory-green.log` (15/15).
В design этот конкретный consumer изначально не был назван — подготовку можно
улучшить; ошибка обнаружена focused до CI, не скрыта и не исправлена ослаблением.
Root также проверил nested repo/dangling-output handling и потребовал связывать
cleanup с успешным созданием worktree, а не с попыткой. Это подготовка к Gate5,
не дополнительные независимые review verdicts.

Текущий срез ещё не поставлен. Итоговые elapsed/review/return/check counts,
CI и merge будут записаны по фактическому результату, не по намерению.

## Gate 5 finding and correction cycle

Первый Gate5 verdict CHANGES_REQUESTED: `restore` разрешал вложенный destination
в source checkout и менял его status. Независимый reviewer воспроизвёл ошибку.
Root добавил один публичный regression для source, symlink alias и immutable
snapshot directory, уточнил запрет в isolation row, сохранил intended RED
(`/tmp/82-nested-red-v2.log`: три успешных вместо отказа результата). Это реальная
ошибка реализации/изоляции, а не причина игнорировать review ради скорости.
Вместе с этим sentinel создаётся в setup, чтобы сравнение не могло восстановить
ошибочно удалённый sentinel. Тестовая поправка идёт через независимый Gate3 до кода.

## Reviewed candidate before CI

Gate3 containment amendment APPROVED; Gate5 delta rereview APPROVED. Итог:
6 независимых verdicts в двух записях (4 approvals, 2 returns: чувствительность
отказов и реальная restore containment ошибка), 5 передач reviewer. Отдельный
read-only audit не является gate verdict. Возвраты не скрыты.

Реальный snapshot восстановлен: 3961 файла проверены по bytes/executable mode.
Последний source snapshot patch SHA-256
`fafddd5beba8aab18db1d9e495bd3bdff1f1fa22c005b674ae9153a5e3440026`, base `934b58f5`.
Финальный CLI SHA-256 `a45bc1170ba7712088e49961d2b84cb917b6b9bb72b04ec07290bb07118b99ef`.
Snapshots/RED logs сохранены также постоянно вне repo:
`/Users/antropophag/.local/state/fmonitor2/review-snapshots/82/` (те же имена/digests).
Последний acceptance GREEN 7/7, reviewer повторил 7/7; неизменённые широкие проверки
после containment guard не перезапускались. До CI — два содержательных checkpoint:
полный spec/RED/protocol и реализация со всеми исправлениями/review records.
Начало работы в новом checkout по reflog: 2026-09-10 02:21:11 +03:00;
финальное review получено около 02:47 +03:00. Полное elapsed до merge добавляется
по фактическому времени. CI/merge пока ожидаются.

После review добавлены/обновлены только записи verdict/evidence и checkbox 2.2;
reviewed code, tests, registrations и инструкции сверены с восстановленным
кандидатом. Фактическая поставка подтверждается ссылкой на PR/CI после завершения.

## Delivered

[PR84](https://github.com/Antropophag/fmonitor-2/pull/84) MERGED
2026-09-09T23:58:06Z, merge `8c4468738a96953055d35fe6c05bd0c5d26539e4`.
Exact candidate `11ecb84677b40f7c29ed5b72a49bf260af30956f`,
[Actions34418478143](https://github.com/Antropophag/fmonitor-2/actions/runs/34418478143)
SUCCESS, literal VERIFY_OK (verify job 102690554700). Один full CI, все категории
с первого запуска; повторов CI и скрытых failures нет. #82 CLOSED.
От создания worktree (02:21:11) до merge (02:58:06) прошло 36м55с.
До review около 26 минут; остальное включало CI/merge. Два содержательных коммита.
Сравнение ограничено разным масштабом задач; токены/денежная экономия не измерены.
Закрывающие metadata записаны после merge и группируются с последующей работой,
а не порождают отдельный микрокоммит/повтор полного CI неизменённого кода.
Следующий приоритет по текущему поручению — #76.
