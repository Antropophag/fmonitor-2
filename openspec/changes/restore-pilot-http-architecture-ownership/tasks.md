## 1. Зафиксировать неизменяемое поведение и границы

- [ ] 1.1 Снять pre-change evidence: exact output `PATH=/opt/homebrew/bin:$PATH tools/architecture/check`, текущие line counts и три ObjectQueue SQL fingerprint; verification: запись показывает только известные findings и не изменяет `tools/architecture/baseline.json`.
- [ ] 1.2 Выполнить существующие public regressions очереди, owner-session user admin и file-type assets до переноса; verification: сохранить команды, hashes тестов и фактические HTTP/DB результаты, не меняя ожидания и не используя stand/production data.
- [ ] 1.3 Проверить рабочее дерево и закрепить file ownership с параллельными потоками; verification: чужие изменения, особенно auth/inspection tests, перечислены и не перезаписаны.

## 2. Вернуть владельца read model очереди

- [ ] 2.1 Добавить `app/PilotHttp/MariaDbObjectQueue.php` с полным прежним query/projection behavior и публичным read seam; verification: SQL отсутствует в `rapid-pilot/ObjectQueue.php`, а новый файл удовлетворяет sql-ownership rule.
- [ ] 2.2 Перевести `RapidPilotObjectQueue` на новый reader, сохранив HTTP parsing, exact `objects.read`, completion decoration, scheduling и rendering; verification: `local_rbac_objects_route_admission_001_test.php`, `pilot_object_list_001_test.php` и `rapid-pilot/verify-object-queue-filters.php` проходят с неизменёнными public assertions.
- [ ] 2.3 Обновить только internal source-boundary assertion `rapid-pilot/verify-auth-hot-path.php` для нового владельца; verification: verifier запрещает SQL/read implementation в adapter и требует вызов назначенного MariaDB reader, не подменяя HTTP oracle.

## 3. Извлечь owner-session user admin

- [ ] 3.1 Добавить связный `app/PilotHttp` handler для user-admin route matching, owner-session identity/CSRF, invite PRG/flash и atomic session publish; verification: новый owner не принимает actor/capability из request fields и не создаёт второй state-changing application seam.
- [ ] 3.2 Делегировать совпавшие user-admin routes из `PilotE2ECoordinator` и удалить извлечённое request-local state coupling; verification: coordinator меньше baseline 268 строк либо не превышает его, а route/status/header/CSP/HEAD behavior сохраняется.
- [ ] 3.3 Выполнить `pilot_session_storage_user_access_fault_001_test.php`, `pilot_session_storage_accepted_payload_http_001_test.php`, `pilot_local_trusted_scheme_001_test.php` и релевантный `pilot_http_auth_001_test.php`; verification: success/repeat/role/invite cases GREEN, forced publish fault возвращает 503 и не выдаёт успешное тело/redirect.

## 4. Извлечь file-type asset dispatch

- [ ] 4.1 Добавить малый presentation owner для `/pilot/assets/file-types/{name}.svg` и делегацию из `rapid-pilot/router.php`; verification: router не превышает baseline 286 строк, handler допускает только `[a-z0-9-]+` и читает только публичный `shlz-ui` dist path с прежним generic fallback.
- [ ] 4.2 Выполнить `pilot_shlz_assets_001_test.php`, `pilot_route_csp_inventory_001_test.php` и `pilot_route_csp_001_test.php`; verification: exact/fallback/missing asset status, body/HEAD и security/cache headers совпадают с pre-change evidence.

## 5. Focused verification и независимый review

- [ ] 5.1 Запустить PHP lint всех изменённых PHP-файлов, `git diff --check` и весь focused набор из разделов 2–4; verification: все команды GREEN и не требуют global DB reset, stand, production data или external action.
- [ ] 5.2 Запустить `PATH=/opt/homebrew/bin:$PATH tools/architecture/check`; verification: три новых ObjectQueue SQL fingerprint отсутствуют, `PilotE2ECoordinator.php` и `rapid-pilot/router.php` не превышают baseline, новых findings нет, baseline byte-identical.
- [ ] 5.3 Передать exact production/test diff и verification evidence независимому reviewer, который не авторил implementation; verification: `reviews/code/RESTORE-PILOT-HTTP-ARCHITECTURE-OWNERSHIP.md` содержит reviewed commit/hashes и verdict `APPROVED` либо задачи остаются незавершёнными с findings.

## 6. Done и отдельно отложенные gates

- [ ] 6.1 Отметить change Done только после GREEN focused regressions, architecture check и независимого code review; verification: публичное поведение не изменено, schema/data/stand/remote actions отсутствовали, все предыдущие задачи имеют фактическое evidence.
- [ ] 6.2 Для последующей production integration отдельно выполнить полный `make verify` и применимые final gates; verification: их статус записан фактически и отсутствие `VERIFY_OK` не называется APPROVED, production-ready или завершённым manual-pilot доказательством.
