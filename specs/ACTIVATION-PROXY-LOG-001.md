# ACTIVATION-PROXY-LOG-001 — приватность ссылки активации на прокси

## Простыми словами

Одноразовая ссылка не должна попадать в журналы nginx, даже когда PHP недоступен.
Оператор сохраняет статус, путь и время запроса для диагностики. Оба текущих
nginx-контура получают одинаковую защиту до переключения пользователей на Yii.

## Public seam and preconditions

Real `nginx -t` и HTTP через конфигурации `deploy/yii2/nginx.conf` и
`deploy/runtime/nginx.conf`. Один и тот же существующий runtime nginx binary,
явно заданный FPM upstream. Никакой БД, user/session/domain операции. В isolated
проверке php:9000 разрешается в loopback без FPM и гарантированно даёт502.

| ID | Действие | Наблюдаемый результат |
| --- | --- | --- |
| syntax | nginx -t на каждом конфиге | exit0; конфигурация исполнима текущим runtime nginx. |
| access | GET и POST `/pilot/activate?token=A` с token B в Referer, token C в User-Agent и token D в POST body | HTTP502 при недоступном upstream; stdout содержит одну JSON запись на запрос с method, uri `/pilot/activate`, status502, request_id (32 hex), request_time, upstream_status и upstream_response_time. Ни A/B/C/D, ни query, ни request headers/body в записи нет. |
| error | тот же connect-refused на точном activation path | request-associated stderr не содержит token/query/activation request. Менять уровень error_log вместо исключения чувствительного request context недостаточно. |
| diagnostics | обычный `/health/live` без secret query при том же недоступном upstream | HTTP502, safe JSON access запись и обычная nginx upstream diagnostic в stderr сохраняются. Ошибка не превращается в успех. Startup/config errors также остаются диагностируемы. |
| parity | оба контура | те же method/body/query/Host передаются в прежний FPM entrypoint; URI, размер body, timeout и routing contracts не меняются. Exact activation location использует тот же include/параметры, что обычный handler. |
| setup | тест без cached image override | сам строит только named `runtime-base` target Yii Dockerfile, использующий прежние nginx/PHP packages; конечный app image наследует ту же base, app/Composer/shlz копирование сохранено. Один base build обслуживает оба конфига. Явный cached image override проверяется и не инициирует скрытый pull. |
| isolation | запуск/завершение теста и failures | уникальные container IDs и loopback ports, readonly candidate config; finally удаляет только созданные тестом контейнеры. Running stand/images/data не меняются. Failed setup не считается RED; HTTP502 и наличие старого query в логах — intended RED. |

Гарантия относится к генерируемым activation requests и их query/header/body
секретам. Политика Referrer-Policy no-referrer в приложении сохраняется. Она не
обещает удалить произвольную строку, которую злоумышленник сам сделал путём другого
маршрута. Existing domain/auth guarantees остаются в YII2-USER-ACCESS-001.

## Implementation constraints / independent examples

Named escaped JSON access format исключает `$request`, `$request_uri`, `$args`,
`$query_string`, Referer, User-Agent, cookies и Authorization. `$uri` — нормализованный
путь без query. Точный activation location подавляет request error log, сохраняя
обычные diagnostics других маршрутов. Изменение access format само по себе
не закрывает stderr. Response502, literal path/method и отсутствие заранее заданных
synthetic tokens определяются тестом независимо от format implementation.

Предварительный real-nginx probe на main8c446873 показал502 и token в обоих stream
обоих конфигов. Primary evidence outside repo:
`/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fmonitor-nginx-probe-gjf79wee`.

## Sources

- [nginx access/log_format](https://nginx.org/en/docs/http/ngx_http_log_module.html)
- [nginx variables](https://nginx.org/en/docs/http/ngx_http_core_module.html#variables)
- [nginx error_log](https://nginx.org/en/docs/ngx_core_module.html#error_log)
