> Историческая остановка после PR87 снята новым поручением владельца.
> Продолжение #76: [current-delivery-goal.md](current-delivery-goal.md).
> Ниже сохранён готовый closeout из локального1702cc9d; перенос этой записи
> в текущую ветку сам по себе не является merge или deployment.

# #76 — Yii2 путь до открытия: поставлено

[PR #87](https://github.com/Antropophag/fmonitor-2/pull/87) слит в `main`
2026-09-10 в 13:29:20 МСК. Срез `yii2-preopening-journey` завершён:
независимые Gate3/Gate5 пройдены, полный exact-source CI — SUCCESS / VERIFY_OK.
Рабочий стенд не переключался. Весь #76 остаётся открытым.

## Результат и точный источник

На Yii2 перенесён путь карточка → выбор состава → необязательный PDF-шаблон →
загрузка/исправление оригинала → история и точное скачивание → отдельное открытие.
Сохранены полномочия, native session/CSRF, неизменяемые документы и история,
повторы запросов, атомарность применения состава и открытия. Новые read boundaries
используют Yii DAO; прежние native owners подключаются целиком без внешней
смешанной транзакции. Общий read-only admission согласует ссылки UI с сервером;
raw POST не получил дополнительного требования `original.read`.

| Источник | Идентификатор |
|---|---|
| Проверенный source commit | `76e896b7d9c692a66fb722bd21de6cb091431bc4` |
| Merge commit | `5822cde3ab327c728db2cc7affb661d0946123cc` |
| CI | [34465238864](https://github.com/Antropophag/fmonitor-2/actions/runs/34465238864) |
| CI conclusion | `success`, итоговый `VERIFY_OK` |
| Полные CI-запуски кандидата | 1; повторного запуска не было |

CI проверял именно source `76e896b7…`, не будущую запись о его поставке.
Все категории и итоговый verify прошли. По `createdAt`/`updatedAt` GitHub run
занял 11 минут 27 секунд (10:17:03–10:28:30 UTC). Полный local suite не запускался.
Закрывающая документация оформляется отдельно после merge; она не меняет код
или тесты проверенного кандидата.

## Контракт, авторство и reviews

- [Нормативный контракт](../../specs/YII2-PREOPENING-JOURNEY-001.md).
- [OpenSpec tasks](../../openspec/changes/yii2-preopening-journey/tasks.md): 7/7 выполнено;
  change не архивирован в рамках этой записи.
- [Gate3 и сохранённые RED/delta reviews](../../reviews/tests/YII2-PREOPENING-JOURNEY-001.md).
- [Gate5: два возврата и финальный APPROVED](../../reviews/code/YII2-PREOPENING-JOURNEY-001.md).

Root писал spec/tests и принимал решения по границам. Production реализовали
отдельные `preopening_executor`, `card_executor`, `preopening_js` — sol/low.
Reviews выполняли отдельные неавторы. После исчерпания лимита collaboration
использовались свежие процессы `codex exec`, также gpt-5.6-sol / low;
саморевью не подменяло независимый gate.

Финальный Gate5 source snapshot:
`/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-preopening-gate5-final`,
base `401a2345535a1e3c991373c7afd85acf6a2997d3`, patch SHA-256
`2ea96645170c3d0f792588b72c682f98a286fd075034c87d809706508de3d5ab`.
Перед implementation commit проверено побайтовое совпадение и режимы 53 артефактов;
дополнительно изменялись только review/delivery/current-goal документы.

## Проверки и сохранённая история возвратов

GREEN: 12 срезовых HTTP/browser/image suites, 15 native neighbors,
5 Yii/readiness neighbors, 6 QualityGraph obligations и actual
`make architecture-check` (7 правил и PILOT-HTTP-AUTH qualification).
Браузер проверил полный путь, потерю ответа и повтор, исправление, оба скачивания,
CSP и загрузку assets; desktop/mobile inspection выполнена.

Gate5 вернул два MEDIUM: несоответствие document links фактическому допуску,
затем ошибочную обработку unavailable при его исправлении. После второго возврата
root пересобрал полный caller matrix; final review — APPROVED без findings.
Admission delta Gate3 один раз возвращался для добавления положительного raw POST
без read grant; исправленный набор одобрен. Остальные reviews и ранний Gate3
RETURN до перезапуска сохранены в соответствующих review records.

Первый native concurrency run имел один setup timeout. Диагностический и затем
канонический повторы прошли; точная причина timeout не установлена, исходный
failure сохранён. Повторные focused runs выполнялись после исправлений кода или
оснастки: ordering редакций, proxy EINTR, browser response lifetime, строгие формы,
admission и assets. Это не отдельные полные CI-прогоны. Измеренные 15 native-команд
первого inventory заняли 417,7 секунды; общее время сессии и стоимость не измерялись.

Постоянные логи, probes и snapshots находятся вне репозитория:
`/Users/antropophag/.local/state/fmonitor2/deliveries/76-preopening-20260910/`.
[Прежний рабочий журнал](yii2-preopening-work-log-2026-09-10.md) сохранён
байт-в-байт; его WIP/pending статусы исторические и заменены этой записью.

## Точка остановки

Код поставлен, незакрытых gates этого среза нет. Следующий срез #76 не начат.
Checklist/photos/offline, completion, оставшиеся каталоги/console и общий cutover
остаются дальнейшей работой #76. Актуальная точка продолжения —
[handoff](session-restart-2026-09-10.md). Текущее поручение ограничено закрывающей
записью и handoff; дальнейшая реализация не запускается.
