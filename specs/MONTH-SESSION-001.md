# MONTH-SESSION-001

## Простыми словами

Вход сохраняется после перезапуска браузера и перехода на FMonitor по ссылке из другого сайта. Срок серверной сессии и cookie —30 суток. Владелец запросил исправление и месяц13 ноября2026. Старые cookie получают новый срок/политику при следующем входе; принудительный logout всех пользователей не выполняется.

## Границы и acceptance

- A1: canonical Yii session timeout и cookie lifetime равны2,592,000секунд; browser cookie после настоящего login имеет этот срок, является persistent и переживает полный restart Chromium с тем же profile.
- A2: cookie SameSite=Lax. Top-level GET navigation с другого site после restart сохраняет authenticated identity и cookie; не создаёт гостевую замену. Это воспроизводимый дефект Strict, подтверждённый private diagnosis probe.
- A3: Secure остаётся включённым при trusted scheme=https, HttpOnly=true, path=/pilot, strict PHP session IDs. Имя cookie, savePath, validation/identity keys, enableAutoLogin=false, отсутствие дополнительных authTimeout/absoluteAuthTimeout не меняются. Месяц означает30суток, не календарный переход и не sliding remember-me.
- A4: POST без корректного CSRF по-прежнему отвергается. Same-origin invalid-CSRF logout не завершает действующую сессию; настоящий logout с корректным CSRF завершает её. Cross-site POST не получает Lax session cookie и не может выполнить защищённую операцию.
- A5: исправление не удаляет session files, не меняет пользователей/права/session_version, не создаёт новый identity cookie и не переносит auth в localStorage. Отсутствие cookies из-за browser privacy policy не обходится.

Публичные seams: actual Yii login/protected GET/logout и external browser navigation. Данные и cookie values не выводятся в logs. Схема БД не меняется. Реализация ограничена настройками session в `config/yii/web.php`; исходный prod config hash обязан совпасть перед scoped deploy.

## Delivery

Root автор spec/tests, отдельный sol/low executor — config change, независимый sol/low final review. По действующему решению владельца в беседе standard Gate3/fullCI sequence отложены; focused checks и final review сохраняются. Полный local make test/verify запрещён. Production-first поставка с private rollback file и контрольными live headers/health; предыдущие поставки и пользовательские сессии сохраняются.
