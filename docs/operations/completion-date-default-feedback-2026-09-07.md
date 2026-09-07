# Дата акта ПТО и декларации без взаимодействия — 2026-09-07

Владелец сообщил: поле визуально показывает текущий день, но при переходе браузер
требует заполнить дату. Read-only Playwright на объекте966 подтвердил у новой
декларации `value=""`, `defaultValue=""`, `FormData=""`, `validity.valueMissing=true`;
`max` содержал2026-09-07. Существующее correction-поле ПТО имело реальную дату.

В `CompletionFlow::currentAction` текущий день Europe/Moscow вычисляется один раз
и используется как `value` и `max` начальных полей ПТО/декларации. Поля остаются
обязательными и редактируемыми. Correction-факты, серверные проверки дат, роли и
сохранение истории не меняются. Нажатие кнопки остаётся явной отправкой документа.

Проверка `rapid-pilot/verify-completion-flow.php` получила DOM assertions реального
value/max для обоих этапов. Ожидаемый день вычисляется независимыми системными
часами Europe/Moscow; production clock не подменялся. RED: отсутствовал PTO value,
exit255. GREEN: весь прежний85→ПТО→декларация→100 flow плюс новые assertions, exit0.
Visual/focus gates PASS, PHP lint PASS, Impeccable detector `[]`.

Private headless runner:
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/completion-default-date-20260907/`.
`golden-browser-fixture.php` создаёт только синтетическую БД и очищает её.
Первая попытка не дошла до дат из-за отсутствующих fixture PNG; setup-failure.log
сохранён отдельно, синтетические PNG перенесены, затем выполнена полная проверка.
Итог `result.log`: `ptoDateUntouched=true`, `declarationDateUntouched=true`,
`finalProgress=100`, `result=PASS`, errors[]. Browser не вызывал fill/click/focus
для обоих date inputs; реквизиты декларации введены отдельно, переходы выполнены
обычными кнопками. Дополнительно весь pending batch сохранял9 из9 отметок через
252 sampled frames и reload. Реальный объект966 не изменялся.

## Установка

Source `78182e1ef1992f87d08c1bc4c415ac42255be550`, образ
`sha256:1a343904967ba7488876dc600e3a0059aebf4ddabf5ab197b366bb10e11f81ce`.
Образ собран из `git archive` exact commit, чтобы параллельный незавершённый
рефакторинг рабочего дерева не попал на стенд. Все721 runtime-файла сверены
SHA256 из-под пользователя контейнера с архивом; несовпадений0. Label совпадает.
Pilot healthy, обновлён только сервис pilot без DB/volume reset; предыдущий
образ сохранён `fmonitor2-manual:checkpoint-670ebc9`.

Read-only post-deploy№966:
`value=defaultValue=max=FormData=2026-09-07`, `validity.valueMissing=false`.
Private `completion-date-installed.json` — отдельное новое свидетельство;
исходный `completion-date-before.json` сохранён. Форму владельца не отправляли.
