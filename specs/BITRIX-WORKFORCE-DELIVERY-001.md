# BITRIX-WORKFORCE-DELIVERY-001

Версия0.3,2026-09-07. Manual-pilot live compatibility amendment.

## Простыми словами

Получаем все страницы кадрового ответа по настроенным подразделениям и возвращаем
их только после полной проверки структуры, порядка, количества и scope. При любой
ошибке частичный набор не выдаётся. Этот срез не записывает каталог, не назначает
монтажников и не решает, можно ли работать без известной даты приёма.

## 1. Public trusted readonly seam

Namespace FMonitor2\Workforce:

```php
BitrixWorkforceDeliveryFactory::create(BitrixWorkforceDeliveryConfig $config): BitrixWorkforceDeliveryClient;
interface BitrixWorkforceDeliveryClient { public function fetch(): BitrixWorkforceDeliveryResult; }
```

Config final readonly fields/constructor order:
origin:string, webhookUserId:int, tokenFile:string, departmentIds:array,
connectTimeoutSeconds:int=3, requestTimeoutSeconds:int=10, deadlineSeconds:int=120,
caFile:?string=null.

origin — HTTPS origin (scheme lowercase https) with optional explicit port1..65535, path absent
or '/', ASCII DNS/IPv4 host, no userinfo/query/fragment, max2048bytes. IPv6/IDN
Unicode origin not supported in this version. DNS host case is normalized to lowercase;
one optional trailing slash is removed. Endpoint always joins with one `/rest/`:
https://example.invalid and https://example.invalid/ produce the same target URL.
webhookUserId positive PHPint.
departmentIds nonempty list<=100 unique ascending positive PHPints. Timeouts:
connect1..10, request1..30, connect<=request, deadline1..300 seconds.
Token/optional CA paths absolute Unix paths<=4096bytes withoutNUL. Factory validates
scalar config before native activity; no network/FS/environment/DB access.
Invalid configuration throws BitrixWorkforceDeliveryConfigurationUnavailable,
fixed `Bitrix workforce delivery configuration unavailable.`, code0, previousnull.

fetch uses configured machine credential to read user.get. No FMonitor user grant,
HTTP endpoint or domain mutation is introduced. Runtime reads token at each fetch:
canonical non-symlink regular single-link file, current process UID, exact0600,
lstat/fstat coherence, <=1024bytes. One optional final LF or CRLF is removed;
remaining token ASCII[A-Za-z0-9_-]{1,256}. No global environment/credential fallback.
CA null uses system trust; configured CA is a canonical existing regular file,
readable by runtime, supplied as trusted CA bundle without disabling verification.
Missing runtime dependency/configuration/secret returns failed/configuration_unavailable,
zero attempts if discovered before native request. Native CA parse error is the same
reason after its actual attempt. No private config values in exceptions/results/logs.

## 2. Result and data boundary

Result public readonly fields: status (BitrixWorkforceDeliveryStatus complete/failed),
reason (nullable BitrixWorkforceDeliveryReason), pages:int, attempts:int,
batch:?BitrixWorkforceDeliveryBatch. Complete iff reasonnull/batchnonnull;
failed iff reasonnonnull/batchnull. pages counts fully validated pages; attempts
counts started native Curl attempts, including failed connections and retries.
Page counter advances only after every page check and the final page deadline check.
Both counters reset per fetch. No prior successful result reused after failure.

Reason values: configuration_unavailable, transport_failed, authorization_failed,
api_failed, schema_invalid, pagination_invalid, scope_invalid, limit_exceeded,
deadline_exceeded. No raw cURL/API exception/code text is copied into reason.

Batch public readonly total:int and records():array. Records are by-value arrays
of only selected fields in select order; nullable/raw scalar values remain as
received. This is not normalization or proof of one instantaneous remote snapshot.
Complete means consistent full pagination response, not permission to publish it.
A valid total0/result[]/no next is complete with total0/pages1. Publication policy
for an empty delivery belongs to the future owning sync application.

Ordinary json_encode(result) exposes only status/reason/counters and batch.total,
not records, credential, URL or local paths. records() intentionally exposes the
validated selected employee data only to trusted normalization consumers. No stdout,
stderr, audit/log side effects; response body/API error descriptions are never echoed.

## 3. Exact HTTP request and native transport

POST to origin + `/rest/{webhookUserId}/{token}/user.get` with JSON:

```json
{"sort":"ID","order":"ASC","FILTER":{"UF_DEPARTMENT":[71]},"start":0,"select":["ID","ACTIVE","LAST_NAME","NAME","SECOND_NAME","WORK_POSITION","EMAIL","UF_XING","UF_DEPARTMENT","UF_EMPLOYMENT_DATE"]}
```

Only department IDs and start vary. No ACTIVE filter, ADMIN_MODE or extra fields.
Headers Content-Type/Accept application/json; HTTP1.1. TLS peer verification enabled,
hostname verification2; redirects disabled. No cookies, netrc, ambient proxy or verbose
trace. Only the fixed configured origin is contacted; next is an integer offset, never URL.
Native Curl is required with CURL_VERSION_ASYNCHDNS, millisecond request/connect
timeout support, CURLINFO_PRETRANSFER_TIME_T and POSIX effective UID support.
fetch checks these before credential/network activity; unsupported runtime returns
configuration_unavailable/pages0/attempts0. No insecure stream fallback; NOSIGNAL=true. Decompressed body cap1MiB per
attempt, cumulative received decoded body cap16MiB per fetch (including retry bodies).
A size abort gives limit_exceeded immediately and never a partial batch.

Each page allows up to3 attempts, only for connect-phase timeout or HTTP429/502/503/504.
A connect-phase timeout means Curl timeout before pretransfer/TLS readiness; timeout
after pretransfer is a non-retried request failure. Retry delays before attempts2/3
are1000/2000ms plus random0..250ms. TLS certificate/hostname failure, other network
failure, malformed JSON/schema, API error and HTTP401/403 do not retry.
HTTP401/403 → authorization_failed; exhausted retry/other non200/network failure
→ transport_failed; HTTP200 API error envelope → api_failed.

Monotonic deadline starts after credential/CA preflight and covers all attempts,
waits and response validation. Remaining nanoseconds are rounded down to milliseconds;
if less than1ms remains no attempt starts. Request/connect timeout milliseconds
are capped by that remaining budget. Check deadline after every native attempt and
after page validation, including final success. If a required retry delay cannot fit,
return deadline_exceeded without another attempt. At overlapping failures precedence
is limit_exceeded, then deadline_exceeded, then ordinary failure mapping. No sleep/network loop is unbounded. Curl handles/sockets are released on
every outcome before returning; fetch retains no token or response-resource handle.

## 4. Envelope, field and completeness validation

JSON must be valid UTF8, depth<=32, root object, with no duplicate object keys
(including equivalent escaped keys). Success envelope keys exactly result,total,
optional next,time. time, if present, is object and is ignored. Error envelope may
contain error:string plus optional error_description:string and time:object, no result;
it yields api_failed without exposing payload. Other shapes → schema_invalid.

result must be a list of objects with all required selected fields. Bitrix может
не вернуть optional fallback `UF_XING`, когда значение не задано; только это
отсутствующее поле нормализуется в выбранной записи как explicit `null`. List length
is checked by pagination only: >50 or any overfull/short page is pagination_invalid
(unless the body/person limits already selected limit_exceeded). ID is positive
PHPint or canonical positive decimal string fitting PHPint; ACTIVE is boolean.
UF_DEPARTMENT is a nonempty list<=100 of positive PHPints or canonical decimal
strings fitting PHPint. Other selected values are null or UTF8 strings<=4096bytes.
No invented defaults for other missing selected fields; missing/unknown/wrong types fail.
Each person must belong to at least one configured department, otherwise scope_invalid.
Employee-number/name/date semantic normalization remains a separate port; no rows
are skipped or converted to dismissed because a value is unknown.

total integer0..20000; above20000 gives limit_exceeded, wrong type/negative schema_invalid.
First total binds the fetch. Any total drift, duplicate/decreasing numeric ID,
short/overfull page relative to min(50,total-start), unexpected next, nonsequential
or repeated offset → pagination_invalid. Before total reached next MUST equal
start+50 as integer; once total reached next MUST be absent. Pages start at0; every
validated page advances exactly50 until final. Final unique record count equals total.
If any later page fails, result batchnull; completed earlier page count is retained.

## 5. Independent native examples and checks

Synthetic HTTPS server/certificate under task-owned external directory, no real
webhook/portal/DB. Config department[71], webhookUserId7, fake token file0600, native
Curl with dedicated fixture CA. Literal persons use IDs1..51 (mixed canonical
string/int permitted), ACTIVE true/false, names «Работник теста», WORK_POSITION
«Монтажник», EMAIL tabN@example.invalid, UF_XING=decimal string of N, UF_DEPARTMENT[71],
UF_EMPLOYMENT_DATE null. Page0 contains1..50,total51,next50; page50 contains51,total51.
Expected complete/pages2/attempts2/total51, exact records, unchanged nullable dates.

Native tests cover full2pages, zero/full50 final pages, response property reordering,
copy isolation/safe JSON summary, exact request params/no unwanted filters;
malformed/duplicate escaped JSON keys, types/missing/unknown fields, scope mismatch,
ID overlap/order/total/next/length drift, second-page failure without partial batch;
per-page/cumulative/person bounds, retry allowed vs non-retried failures, deadline,
no redirect to trap, real trusted/untrusted/wrong-hostname TLS, invalid/missing/rotated
secret and repeated fetch. Fake secret/PII in error body must not appear in captured
output or error result. All owned HTTPS workers/ports/keys cleaned; no permission
or native-interception probes. Readable but invalid secret metadata tests are allowed.

## 6. Evidence, gates and exclusions

Request syntax/select/page-size and missing-field behavior follow official user.get:
https://apidocs.bitrix24.ru/api-reference/user/user-get.html
Needed built-in fields are listed under read-only user_basic:
https://apidocs.bitrix24.ru/api-reference/user/user-scope.html
Public docs read2026-09-07; they do not prove live credential or payload readiness.
BITRIX-WORKFORCE-HISTORY-001 is only product/epic evidence, not executable approval.

Gate1→native TLS RED→independent Gate3→minimal GREEN→regression/architecture/lint/diff
→independent Gate5. No DB/DDL/audit/publication or old-cron wiring in this slice.
Normalization/publication/unknown employment eligibility/freshness/scheduler each
retain their own gates. Actual Bitrix run/import/remote mutation forbidden this session.
Full goal/HTTP/application/opening/VERIFY/CI/deployment remain unclosed.

## 7. Manual-pilot live amendment evidence

Read-only user.get был отдельно разрешён владельцем2026-09-07. Первый live ответ
получил HTTP200, корректный envelope и50 строк; безопасная схема сохранена вне
репозитория в
`/Users/antropophag/.local/state/fmonitor2/manual-pilot-20260907/bitrix-schema-summary.json`.
В49 строках Bitrix не включил только `UF_XING`; остальные выбранные поля были
присутствующими и имели ожидаемые типы, включая строковый `UF_EMPLOYMENT_DATE`.
Поэтому v0.3 разрешает только omission `UF_XING` → `null`; обязательность остальных
полей, fail-closed pagination/scope и отсутствие частичного batch не меняются.
Формальные повторные Gate1/3/5 отложены текущим manual-pilot delivery mode; это
не является production-ready claim.
