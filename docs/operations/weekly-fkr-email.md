# Еженедельное письмо руководителям ФКР

Scheduler создаёт одно durable-задание каждый понедельник в 09:00
`Europe/Moscow`. Слот хранится в `fm2_scheduler_slots`, поэтому рестарт и несколько
реплик не создают повторный отчёт. Worker строит глобальную проекцию только для
активных пользователей роли `fkr_manager` с точным правом `objects.read`, сохраняет
по одному email-intent на получателя в outbox и не меняет факты объектов.

SMTP настраивается только внешним private environment. Обязательны STARTTLS,
проверка peer/name и совпадение authenticated username с From. Значение
`FMONITOR_SMTP_TEST_RECIPIENT` допустимо только при `FMONITOR_RUNTIME_ENV=test`;
production fail-closed отвергает override. Пароль, содержимое писем и адрес
логического получателя не выводятся в operator logs.

Перед включением проверить `jobs.env` с именами из `.env.example`, затем выполнить
обычные migrations/readiness и запустить `jobs-worker jobs-scheduler`. Проверка:

```sh
docker compose --file deploy/runtime/compose.yaml --profile jobs exec -T jobs-worker \
  php bin/fmonitor2-jobs.php health
```

Live test-send не является частью startup и требует отдельного решения оператора.
После выбора активного тестового руководителя указать только его local numeric ID:

```sh
IFS= read -r FMONITOR_WEEKLY_TEST_USER_ID
export FMONITOR_WEEKLY_TEST_USER_ID
docker compose --file deploy/runtime/compose.yaml --profile jobs exec -T jobs-worker \
  php bin/fmonitor2-weekly-email-test.php --send
unset FMONITOR_WEEKLY_TEST_USER_ID
```

Exit `0` и safe JSON `{"status":"delivered"}` подтверждают SMTP ACK. Любой иной
результат оставляет доставку неподтверждённой. При `unknown` не повторять отправку
вручную без проверки ящика: сервер мог принять DATA до потери ACK.

Rollback: остановить scheduler первым, затем worker; сохранить queue/outbox rows.
После возврата reviewed image не удалять slot, intent или attempt history.
