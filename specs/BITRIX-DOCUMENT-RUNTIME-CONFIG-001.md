# BITRIX-DOCUMENT-RUNTIME-CONFIG-001

Версия 0.1, 2026-09-24. Issue #252.

## Простыми словами

Оператор хранит два независимых Bitrix webhook URL в приватном `.env`: кадровый
и document webhook. Перед запуском jobs worker FMonitor безопасно раскладывает
их в один атомарный internal private config с отдельными `baseUrl` и
`documentBaseUrl`. Workforce bootstrap сохраняет свой временный token-файл, а
document delivery выбирает только `documentBaseUrl`. Если document-links
интеграция включена, но secret нельзя подготовить, запуск прекращается до worker,
а не продолжается с `/dev/null`. Этот срез не меняет workforce sync, обход Bitrix,
расписание, таблицу ссылок или карточку объекта.

## 1. Актор, вход и публичный seam

Актор — оператор локального или production deployment. Канонический вход —
приватная конфигурация, созданная из существующих `.env` ключей:

```dotenv
FMONITOR_BITRIX_WEBHOOK_URL='https://tenant.example.invalid/rest/7/marker-token/'
FMONITOR_BITRIX_ORDER_DOCUMENT_WEBHOOK_URL='https://tenant.example.invalid/rest/8/document-token/'
FMONITOR_BITRIX_DEPARTMENT_IDS_JSON='[71]'
FMONITOR_BITRIX_ORDER_DOCUMENT_ROOT_ID=1809812
```

Публичный seam — выполняемый `make up` one-shot service
`stage-runtime-secrets` и последующий запуск `jobs-worker`. Для deterministic
focused проверки stager SHALL принимать переопределяемые environment paths
`FMONITOR_BITRIX_CONFIG_INPUT` и `FMONITOR_RUNTIME_SECRETS_DIR`; production
defaults SHALL оставаться `/run/fmonitor-input/bitrix-config.json` и
`/run/fmonitor-secrets`.

Stager SHALL принимать один private regular non-symlink Bitrix config, полностью
проверить workforce `baseUrl` и optional `documentBaseUrl` до публикации и
атомарно публиковать `bitrix-config.json`. Совпадающие credentials SHALL отклоняться. Worker bootstrap SHALL из `baseUrl`
вида `https://HOST/rest/USER_ID/TOKEN` получить exact origin, положительный
decimal webhook user ID и непустой token. Root ID остаётся несекретным
deployment input.

## 2. Успешная подготовка и runtime contract

Успех SHALL до запуска worker одной атомарной заменой опубликовать совместимый
`bitrix-config.json` для workforce sync и document delivery. Отдельные staged
`bitrix-token`, `bitrix-runtime.env` и token mounts SHALL отсутствовать.

Workforce bootstrap SHALL прочитать workforce config, внутри worker process установить
exact `FMONITOR_BITRIX_ORIGIN` и `FMONITOR_BITRIX_WEBHOOK_USER_ID`, создать
короткоживущий `0600` token file и передать его путь через
`FMONITOR_BITRIX_TOKEN_FILE`. Token SHALL не попадать в Compose environment,
argv, stdout/stderr, rendered Compose или tracked files; workforce temporary token SHALL
быть удалён после завершения execution seam. Отдельный
`FMONITOR_BITRIX_TOKEN_HOST_FILE`, token volume mount и fallback `/dev/null`
запрещены.

Published config SHALL быть regular non-symlink file с режимом не шире `0600` и
владельцем runtime identity. Повтор с тем же входом SHALL давать эквивалентный
contract без изменения product state. Rotation SHALL полностью проверить новый
input до единственной atomic rename; worker SHALL видеть один согласованный набор.

### Example A — legacy-compatible upgrade

Для `https://tenant.example.invalid/rest/7/marker-token/` результат SHALL дать
один staged config; реальный worker bootstrap SHALL получить origin
`https://tenant.example.invalid`, user ID `7` и временные token bytes
`marker-token`. Workforce JSON SHALL оставаться совместимым с существующим
consumer и сохранять departments `[71]`.

### Example B — quoting

Plain, целиком single-quoted и целиком double-quoted webhook URL в `.env` SHALL
после existing host staging приводить к одному эквивалентному runtime contract.

## 3. Fail-closed matrix

Следующие случаи SHALL завершать staging ненулевым кодом до запуска worker:

- input отсутствует, является symlink или не является regular file;
- config пуст, нечитаем либо не проходит existing validation;
- `baseUrl` не имеет exact HTTPS webhook shape, user ID неположителен или token
  пуст;
- input имеет group/world permissions шире `0600`;
- runtime directory отсутствует и не может быть безопасно создан, является
  symlink/non-directory либо publication не завершена полностью.

Диагностика SHALL быть ровно безопасным стабильным классом
`RUNTIME_SECRET_STAGING_FAILED` без URL, token, document URL, filesystem path,
payload или stack trace. При отказе ранее опубликованные файлы SHALL остаться
побайтно неизменными; temporary files SHALL быть удалены.

Если root ID отсутствует, document-links integration SHALL оставаться
выключенной без фиктивного token file/mount. Тот же staged config и worker
bootstrap SHALL продолжить обслуживать workforce-only sync.

## 4. Delivery и совместимость

После успешного staging existing `BitrixOrderDocumentDelivery` SHALL прочитать
coherent origin, user ID и token только из `documentBaseUrl` private config, а root ID —
из deployment input. Synthetic read-only transport
SHALL подтвердить, что fetch обращается к configured root и возвращает complete
validated result без credential output. Ошибка fetch SHALL сохранять прежнюю
projection согласно `BITRIX-ORDER-DOCUMENT-LINKS-001`.

Срез SHALL не менять:

- формат и семантику workforce sync;
- scheduler slot, retry/dead policy и job identity;
- Bitrix traversal/mapping limits;
- DB schema, publication owner и object-card rendering;
- production данные или реальные credentials.

Успешные deterministic tests не доказывают production доступ. До отдельной
безопасной read-only/manual проверки production API, job status, опубликованные
rows и отображение карточки SHALL сообщаться как `UNKNOWN`, не `GREEN`.

## 5. Проверяемые acceptance cases

- **A1:** canonical webhook config даёт exact origin/user/token и сохраняет
  workforce departments.
- **A2:** plain/single/double quoted `.env` upgrade эквивалентен.
- **A3:** replay и successful rotation одной atomic rename публикуют private
  coherent configs; workforce bootstrap создаёт и удаляет свой temporary token без
  secret disclosure.
- **A4:** missing/empty/unreadable/symlink/non-regular/invalid input fail closed,
  сохраняют прежние bytes и очищают temporary files.
- **A5:** Compose contract не содержит token environment/mount,
  `FMONITOR_BITRIX_TOKEN_HOST_FILE`, `/dev/null` token fallback или неиспользуемый
  metadata contract; staging предшествует worker startup.
- **A6:** disabled document integration не создаёт фиктивный token contract и
  не ломает workforce-only path.
- **A7:** synthetic configured-root delivery достигает existing read-only API
  seam; existing failure/persistence semantics не меняются.
- **A8:** operator docs перечисляют обязательные inputs, безопасную проверку и
  rotation без команд, печатающих webhook/token.
