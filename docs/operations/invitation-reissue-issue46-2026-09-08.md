# Issue46: перевыпуск приглашений — локальный срез

Основание: #46 и указание владельца2026-09-08 одновременно отвязать CLI writer.
Baseline main38ffe30a; ветка codex/issue-46-invitation-reissue в отдельной рабочей копии.
Аудиты PR48 и исходные незакоммиченные файлы сохранены без изменений. Следующая
продуктовая очередь — #40 с правилами #39; архитектурная #32 → ограниченный #24 → #33.
ОТиЗ-риски из аудита не исправлялись этим срезом; повреждение реальных данных не установлено.

## Изменение

Администратор возвращается в каталог и видит «Ожидает активации», состояние ссылки
и «Перевыпустить приглашение». POST проверяет CSRF и вызывает
IdentityAccess\ReissueUserInvitation. MariaDb implementation проверяет локальное
active/access.administer, блокирует адресата и атомарно отзывает неиспользованные
приглашения, добавляя новое на24часа с автором. Отсутствующий/активированный/
заблокированный адресат отклоняется; прежние пользователь и роли сохраняются.
После выдачи ссылка доступна в подписанном readonly поле; после ухода можно
перевыпустить снова. Первичное создание, активация и управление ролями остаются
прежними owning predecessors, их полная миграция не входит в этот срез.

CLI теперь требует `php rapid-pilot/issue-invitation.php name@shlz.ru administrator-user-id`.
Указанный локальный администратор проходит ту же application-проверку; CLI не
содержит SQL и не выпускает безымянные приглашения. Read-only lookup email
находится внутри IdentityAccess. Доступ к CLI остаётся привилегированным операторским
доступом с конфигурацией БД; ID задаёт аудит, не заменяет аутентификацию веб-сессии.

## Проверки

Изолированная MariaDB23307, фиктивные данные. Стенд владельца и его volumes не менялись.

- RED: `FMONITOR_TEST_DB_PORT=23307 php tests/InstallationProcess/invitation_reissue_http_001_test.php`
  до UI-правки: `INTENTIONAL_RED: pending user exposes a CSRF-protected reissue action after return`.
  Создание, flash, уход на роли и возврат перед этим прошли. Использован стандартный test admin password из compose.test.yaml.
- Та же команда GREEN: `PASS invitation reissue HTTP lifecycle`. Создать → уйти →
  вернуться → перевыпустить → старая ссылка отклонена → новая активирует того же
  пользователя. Проверены роли, история, actor, TTL, отсутствующий адресат,
  активированный/заблокированный адресат, неадминистратор, неверный/отсутствующий
  CSRF, GET405 без записи, expired/repeat. Два параллельных процесса оставляют одну
  действующую ссылку; test-only INSERT failure откатывает отзыв.
- Canonical migrations1–19 на изолированной БД; соседние
  `pilot_session_storage_user_access_tokens_001_test.php` и
  `pilot_session_storage_user_access_fault_001_test.php` PASS с тем же test DB port.
- Реальный CLI с отдельными HOME manifest и prefix cli46_: две строки истории,
  одна revoked/одна live, actor9001; target9002 и его роль сохранены.
- Headless Chromium/Playwright на SelectionHttpFixture: реальные клики создания,
  ухода, перевыпуска и активации PASS; console/network чистые. Desktop1440 и
  mobile390 screenshots проверены; существующая широкая таблица на телефоне
  требует горизонтальной прокрутки. Сырые ссылки/скриншоты только во временном
  каталоге вне репозитория, тестовые токены уже использованы.
- `php rapid-pilot/verify-visual-contract.php`, `php rapid-pilot/verify-focus-contract.php`
  PASS; Impeccable detector по изменённым view/JS/CSS — `[]`.
- `make architecture-check`: `ARCHITECTURE CHECK PASSED (7 rules)`, baseline нарушений
  не расширялся. PHP lint изменённых файлов, `git diff --check`, OpenSpec strict validate PASS.
- Тест зарегистрирован в suites.tsv/db и categories.json/integration; list inventory проходит.

Первый запуск соседних тестов выявил отсутствие миграций тестовой БД; после
canonical migrate оба прошли. Первый browser запуск не имел FMONITOR_NOW в fixture,
после добавления той же конфигурации, что в HTTP-тесте, проходит. Это исправления
подготовки проверки, не изменения производственного контракта.

## Границы готовности

Независимые verdict и exact reviewed SHA фиксируются в reviews/tests/INVITATION-REISSUE-001.md
и reviews/code/INVITATION-REISSUE-001.md. Последовательность предварительных Gates1–3
отложена по manual-pilot exception; она не объявляется задним числом выполненной.
Полный make test/Actions, публикация PR, merge и установка на стенд в этом локальном
проходе не выполнялись; production readiness и закрытие issue не заявлены.

## Точный локальный результат

Код и тесты: `1a3555c82c559647f85016e25ff10e7cd6e8c115`. Оба независимых review
на этом SHA — APPROVED. Реестр тестов после добавления нового db member сначала
дал RED baseline drift; точное включение одного нового member в existing guard
сохранило старый digest и дало GREEN15/15. Тестовая MariaDB и browser fixture
остановлены; volumes сохранены. Последующий commit содержит только этот checkpoint,
review records и статус задач. Полная интеграционная задача4.1 остаётся открытой.
