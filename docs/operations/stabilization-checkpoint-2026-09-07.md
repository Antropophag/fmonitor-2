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
Прогон завершился с exit 2; exec session `30319`, PID `52546` больше не активны.
Итог: `FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test`.
Прошли setup, migrate, architecture, lint и diff-check. Не считать старую сессию живой.

Уже прошли test-db-reset, migrate, architecture-check, lint. Unit suite упала
на `PDF renderer dependency is unavailable`; связанные PDF/HTTP тесты в DB suite
также упали. Причина подтверждена: отсутствует gitignored `vendor/`. Перед следующим
прогоном подготовить TCPDF 6.11.4, pinned commit
`fbbaf14cfae8fe646f154f7c530d15ec25764040`, и autoload из
`rapid-pilot/tcpdf-autoload.php`, как в Dockerfile. Допустимо скопировать проверенный
main-workspace vendor и сверить версию/хеш autoload. После завершения полного
прогона vendor скопирован, source checkout по-прежнему чистый; focused PDF renderer
уже GREEN. Все десять связанных focused проверок прошли, лог
`runtime/pdf-dependency-focused.log`, terminal `FOCUSED_FAILURES []`.

Остальные причины полного FAIL: docker bootstrap конфликт DEMO/DB переменных,
старые card/list/shell expectations, protected E2E и его demo-bootstrap caller,
характеризация identical photo reupload после revoke. Bootstrap environment fix
прошёл focused, но выявил зависание cleanup дочерних PHP workers; переключён на
существующий process-group wrapper, после чего PASS с exit0 и без оставшихся
процессов на 18092. После reviewer finding cleanup дополнительно проверяет всю
process group даже после выхода leader; повторный focused PASS. Независимый review
APPROVED. Исправление и registration identity сохранены в source commit `b662e1e`.

Card module oracle прошёл независимый review, но focused card далее выявил
следующее расхождение visible literal/order `77-000123`; whole card пока FAIL.
Разбор выявил два отдельных вопроса: native card поменял порядок identity/status
относительно старого oracle, а RapidPilotObjectDetails действительно обрезал полный
регистрационный номер до последних цифр. Root исправил presentation prefix parsing;
новый `object_card_registration_identity_test.php` показал RED `77-000123`→`000123`,
затем GREEN для полного номера, ведущих нулей, букв/слеша и escaping. Visual/focus/
detector проверки прошли; независимый review APPROVED. На стенд пока не установлено.
Подготовлены OpenSpec changes `reconcile-pilot-queue-shell-verifiers`,
`reconcile-protected-pilot-e2e-current-flow`, `allow-identical-photo-reupload-after-revoke`.
Они не означают законченной реализации. Worktree содержит параллельную работу;
не собирать его целиком без review и фиксации exact commit.

Уточнение scope UI tests: `public/router.php` проверяет native adapter seam,
`rapid-pilot/router.php` дополнительно собирает manual-pilot фильтры/пагинацию,
root redirect и полную карточку. Нельзя переносить ожидания второго на первый и
объявлять их отсутствие новым product defect. Native list test сохраняет свой
unfiltered reader/cap500/501/ignored-query контракт, обновляя только текущие
table/copy/date/identity assertions; manual q/status/page/50-row поведение остаётся
в отдельных rapid checks/E2E. Аналогично native shell root compatibility body
отличается от actual manual root redirect. Review первоначального слишком широкого
test reconciliation отклонён; агент уточняет package. Новая архитектурная миграция
ради такого расхождения не нужна.

Protected E2E replacement пока не APPROVED. Review выявил и потребовал устранить
tautological PDF check, общие cookies пользователей, недостаточный child cleanup,
fixture role collision и потерю browser failure evidence. Переработанный test
выполняет обязательные retained contracts плюс current browser flow; diagnostic
итерации пока исправляют setup/selector ошибки. Отсутствие пропусков не заменяет
конкретного полного GREEN и финального независимого review.

Параллельные агенты: verify_triage реализует current protected E2E reconciliation,
architecture_diagnosis обновляет queue/shell assertions, auth_review готовит
schema/reupload тесты до production implementation и отдельно рецензирует bootstrap.
DB focused execution координируется root. Все агенты gpt-5.6-sol / low;
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
