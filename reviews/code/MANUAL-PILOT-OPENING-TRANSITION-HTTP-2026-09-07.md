# Независимый review opening transition и HTTP adapter

Reviewer `/root`, авторы проверяемого кода — `/root/auth_review` (transition) и
`/root/architecture_diagnosis` (HTTP). Дата2026-09-07, base c9bd432.
Verdict: **APPROVED** для двух перечисленных implementation файлов.

`MariaDbOriginalOpening::openPreparedWithinTransaction` требует активную
транзакцию и не вызывает begin/commit/rollback. Он повторно проверяет authority,
точную application и reference с тем же issuing reader, дату, открытие/закрывающие
факты, eligibility и template; только затем записывает opening. Прежний публичный
openInstallation сохраняет собственную transaction boundary и result semantics.

HTTP принимает actor/object только из доверенного контекста/маршрута. Новый
open_confirmed проверяет installation.open, размер/duplicate fields/CSRF и exact
идентификаторы и вызывает один public application seam. Старые apply/open сохраняют
свою selection admission и прикладные права; новый endpoint не ослабляет
standalone apply. Успех нового действия —303 в карточку, прежних —execution.

Root независимо выполнил manual_original_execution_smoke и новый
confirmed_original_opening_http_001_test: PASS. Последний подтверждает один POST
без отдельного apply, сохранённый original download, case/card/checklist projection
и отказ неуполномоченному actor. Дополнительно полный headless golden прошёл
upload+correction→card→open_confirmed от другого пользователя→41items/7photos→100%,
без ошибок и без дополнительных apply HTTP requests.

Exact SHA256:
- MariaDbOriginalOpening.php:
  `35c7185742ed3bd77a18c98094d82bd6daf0421b4ab232191bc7184b416628d4`.
- ExecutionHttpHandler.php:
  `5b4f4a5d126dfa6b8fd61eaed6cdff7d1a63d7fe1b94078bd199819816f620a4`.

Root-authored compound owner/application extraction и HTTP test отдельно
проверены другим reviewer в MANUAL-PILOT-CONFIRMED-ORIGINAL-OPENING-2026-09-07.md.
Это разделение сохраняет независимость review; общий VERIFY_OK не заявляется.
