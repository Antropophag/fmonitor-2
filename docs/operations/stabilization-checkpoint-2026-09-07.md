# Стабилизация до VERIFY_OK — рабочая точка продолжения

Владелец подтвердил стабилизацию до VERIFY_OK, затем разрешил продолжать автономно:
«Работй дальше автономно, перезапустить сейчас не успеваю, сделаем когда вернусь».
Перезапуск отложен; это сохранение состояния, а не завершение работы. Глобальная
цель ACTIVE. Новые функции, включая обогащение адреса, остаются в backlog.

## Установленный пилот

- Source: `6aa39aa79d0c2e7cbd1a140f2110fcf2199fb9d4`.
- Image: `sha256:a624164ee5cf7aafd7c077f3c843377bd3e8b8a73bf989a8e8071f8aacd9674a`.
- Container: `fmonitor2-manual-pilot-1`, healthy; URL `http://127.0.0.1:8092/pilot/objects`.
- Проверены хеши 732 runtime-файлов относительно архива exact commit.
- Volumes `fmonitor2-manual_pilot-state` и `fmonitor2-manual_mariadb-data`
  сохранены. Объект 966 содержит данные владельца: только read-only проверки.

Установлены исправления layout декларации, bulk checklist без исчезновения
pending отметок, настоящих default value дат ПТО/декларации, имён авторов,
фильтра завершённых объектов стройконтроля, текущего прогресса ОТиЗ и inline PDF.
Последний уточнённый маршрут: загрузить оригинал и подтвердить на той же странице
→ карточка → явное открытие работ с фактической датой. Отдельной кнопки применения
состава нет; внутренние применение и открытие атомарны. У загрузчика и открывающего
могут быть разные учётные записи. GET не меняет факты.

Focused проверки и независимые review сохранены в коммите. Синтетический
headless browser golden прошёл от оригинала/исправления до 100%, включая отдельного
открывающего пользователя, стабильность всех девяти pending галочек в 275 кадрах,
перезагрузку pending очереди, 41 работу, семь фото и нетронутые поля дат.
Private evidence: `~/.local/state/fmonitor2/manual-pilot-20260907/runtime/direct-opening-browser-20260907/`.
Это не доказательство полного VERIFY_OK.

## Текущий полный прогон

Запущен `PATH=/opt/homebrew/bin:$PATH make verify` на чистом detached worktree
`/Users/antropophag/code/fmonitor-2-verify-6aa39aa` указанного выше SHA.
Лог: `~/.local/state/fmonitor2/manual-pilot-20260907/runtime/verify-6aa39aa.log`.
При записи этой точки процесс ещё работал: exec session `30319`, PID `52546`.
Перед продолжением проверить PID и хвост лога; не перезапускать уже живой прогон
и не менять его зависимости/БД посреди исполнения.

Уже прошли test-db-reset, migrate, architecture-check, lint. Unit suite упала
на `PDF renderer dependency is unavailable`; связанные PDF/HTTP тесты в DB suite
также упали. Причина подтверждена: отсутствует gitignored `vendor/`. Перед следующим
прогоном подготовить TCPDF 6.11.4, pinned commit
`fbbaf14cfae8fe646f154f7c530d15ec25764040`, и autoload из
`rapid-pilot/tcpdf-autoload.php`, как в Dockerfile. Допустимо скопировать проверенный
main-workspace vendor и сверить версию/хеш autoload. Текущий checkout не менять.
Итогового
вердикта ещё нет; после завершения дописать результат и перечень оставшихся причин.

Параллельные агенты только читают: verify_triage проверяет зависимости PDF,
architecture_diagnosis сверяет старые UI assertions с актуальными контрактами,
auth_review сверяет фото revoke/reupload с owner retention decision. Они не меняют
тестовую БД и не вмешиваются в прогон. Все агенты gpt-5.6-sol / low;
автор не рецензирует собственную реализацию.

## После прогона

1. Сохранить полный результат; устранить подтверждённые причины focused проверками.
2. Не возвращать ручную регистрацию/применение и старый UI ради устаревших тестов.
   Protected E2E не менять молча; reconciliation требует записанного основания
   и независимого review. Не воспроизводить устаревшую SQL-ошибку фото в продукте.
3. Следующий полный прогон — на подготовленном exact SHA после focused GREEN.
4. После literal VERIFY_OK зафиксировать source/image и restart/golden evidence,
   затем продолжить Quality Graph / GitHub CI согласно разрешениям.

Реальные Bitrix calls/imports, PR10 merge и публикация CI остаются ограничены
current delivery goal. Вопрос владельца о CI не отменяет ранее записанного запрета
публиковать CI до первого VERIFY_OK. Предварительное read-only состояние remote:
`remote-readiness-observation-2026-09-07.md`; адресное исследование:
`../research/address-district-enrichment-2026-09-07.md`.

Браузерные проверки только headless; окна владельца не трогать. Секреты и первичные
артефакты вне репозитория. Старые volumes и append-only историю сохранять.
