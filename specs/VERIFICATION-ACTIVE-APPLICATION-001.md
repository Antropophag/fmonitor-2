# VERIFICATION-ACTIVE-APPLICATION-001

## Решение владельца — 2026-09-14

При реализации №66 владелец явно подтвердил: обязательный CI проверяет только действующее приложение Yii2, без поддержки выведенного rapid-pilot. Это изменение поддерживаемого контура, а не разрешение скрывать ошибки действующего приложения. Первый CI №66 упал вследствие изменений №66; все18отказов сохраняются в delivery history.

## Контракт

Публичные `tools/verification/ci.py list` и `verify-roster`, а также существующий runner перечисляют актуальные проверки без повторов. В обязательном наборе нет direct entrypoints `rapid-pilot/verify-*` и перечисленных ниже retired launch/UI entrypoints. Их исходники сохраняются в Git history; отдельный optional CI, skiplist, allow-failure и изменение агрегатора не вводятся.

Общие application/domain/security/migration/history тесты сохраняются, включая исторический адаптер расчёта V1: прежние буквальные assertions переносятся из rapid-pilot verifier в `tests/Otiz/legacy_premium_calculation_001_test.php` с прямым импортом `app/Otiz/LegacyPremiumCalculation.php`. Старый router как техническая fixture у теста общего application seam сам по себе не повод удалить такой тест. Пять inspection characterization wrappers и их доказательства остаются в наборе. Отрицательные проверки запрета rapid-pilot в production package сохраняются.

Retired entrypoints:

- `tests/Verification/harness_otiz_canonical_compat_001_test.php`
- `tests/InstallationProcess/pilot_demo_bootstrap_001_test.php`
- `tests/InstallationProcess/docker_bootstrap_manual_pilot_test.php`
- `tests/InstallationProcess/pilot_e2e_flow_001_test.php`
- `tests/InstallationProcess/checklist_asset_current_source_manual_test.php`
- `tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php`
- `tests/InstallationProcess/pilot_http_auth_001_test.php`
- `tests/InstallationProcess/pilot_object_card_001_test.php`
- `tests/InstallationProcess/pilot_object_list_001_test.php`
- `tests/InstallationProcess/pilot_route_csp_login_001_test.php`
- `tests/InstallationProcess/pilot_session_storage_local_auth_canonical_001_test.php`
- `tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php`
- `tests/InstallationProcess/pilot_session_storage_protocol_001_test.php`
- `tests/InstallationProcess/pilot_shlz_assets_001_test.php`
- `tests/InstallationProcess/pilot_ui_shell_001_test.php`
- `tests/Runtime/rapid_router_otiz_dependency_001_test.php`

Все остальные catalog entries сохраняются, за исключением замены девяти direct rapid verifiers выше и добавления native V1 oracle. Существующие Yii2 auth/access/session/CSP, production runtime, checklist/completion, OTIZ publication/settlement и canonical migrations должны оставаться обязательными. Ошибка любого оставшегося теста по-прежнему делает CI красным. Полный exact-source CI обязателен; локально только focused checks.
