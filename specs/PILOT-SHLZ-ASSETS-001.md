# PILOT-SHLZ-ASSETS-001 — полный публичный CSS export `shlz-ui`

- Статус: `APPROVED`
- Версия: `0.2`
- Дата: `2026-08-29`
- Актор: browser пилотного FMonitor 2.0; asset routes публичны и не требуют identity
- Публичный seam: HTTP `GET|HEAD` корневого `/pilot/assets/shlz.css` и его разрешённых relative `@import` dependencies
- Predecessor contracts: `PILOT-HTTP-AUTH-001 v0.12`, `PILOT-UI-SHELL-001 v0.4`, `PILOT-E2E-FLOW-001 v0.4`, `PILOT-DEMO-BOOTSTRAP-001`

## 1. Цель и подтверждённая проблема

Configured pilot обязан загружать весь публичный CSS export `@shlz/styles`, а не только байты entrypoint `shlz.css`. Real Chromium acceptance на exact pilot HEAD `6313e1a` показал, что HTML journey работает, но browser не может считать дизайн-систему применённой, если public entrypoint содержит relative `@import`, а HTTP boundary обслуживает только фиксированный `/pilot/assets/shlz.css`.

Срез расширяет только read-only asset boundary:

```text
configured public shlz.css
→ фиксированный транзитивный manifest его локальных CSS @imports
→ same-origin GET/HEAD каждого manifest member
→ browser применяет shlz-ui ко всему рабочему journey
```

Существующий `pilot.css` сохраняется после `shlz.css` и остаётся владельцем только `.fm2-*` layout/composition. Срез не копирует tokens, component CSS, fonts, icons или Showcase assets в FMonitor, не меняет `../shlz-ui`, не вводит bundler, JavaScript, CDN, runtime proxy либо общий static-file server.

## 2. Configuration и корень public export

Единственный inherited key `FMONITOR_SHLZ_CSS_PATH` сохраняется. Он указывает на exact public entrypoint с basename `shlz.css`. Его canonical parent directory является **configured public dist root**. Новый alias/root key не вводится.

До выдачи любого manifest member приложение fail closed проверяет:

1. configured value — nonempty absolute path без NUL;
2. entrypoint basename exact `shlz.css`;
3. каждый существующий path component от filesystem root до dist root и entrypoint проверен через `lstat`, не является symlink, а dist root является readable/searchable directory;
4. canonical entrypoint остаётся непосредственным child canonical dist root, является readable non-symlink regular file;
5. каждый manifest dependency является descendant dist root, а не самим root; все его directory components и file — non-symlink; canonical dependency остаётся внутри exact canonical dist root;
6. opened descriptor каждого member проходит inherited `lstat → open once → fstat identity → lstat revalidation → complete read → final fstat/size → close exactly once` contract.

Deployment/bootstrap обязан установить dist root и все directories/files внутри него с одним trusted filesystem owner. Owner считается trusted только если его numeric UID равен effective UID application process либо `0`. Root и members не могут быть group/other-writable (`mode & 0022 == 0`); regular file не может быть executable; directory обязана иметь owner read/search permission. Owner-write на root допустим только потому, что этот owner уже trusted и выполняет официальную atomic replacement. Owner/mode mismatch либо смена owner/mode во время request дают `503`. HTTP-приложение само permissions не исправляет и export не публикует.

Missing, relative, unreadable, wrong-type, wrong-basename, symlinked component, escape, identity/size swap, malformed graph, read/stat/open/close failure или изменение любого manifest member во время конкретного response дают inherited redacted `503 Service unavailable.\n` с `Retry-After: 60`; partial CSS bytes не отправляются. Никакого fallback на source tree, copied CSS, private Showcase, package manager или bundled local snapshot нет.

## 3. Stateless per-request atomic graph capture

Manifest строится заново внутри каждого exact `GET|HEAD` request из exact bytes configured public export, начиная с relative path `shlz.css`, рекурсивно и без чтения directory listing. Между requests не сохраняется identity, graph или bytes. Учитываются только top-level CSS `@import` rules, разрешённые CSS grammar до первого не-`@charset`/не-`@layer` statement и до обычного style rule. Comments и whitespace допустимы. Import media/supports/layer suffix сохраняется browser-у, но на filesystem target не влияет.

Разрешённый import target — quoted string либо `url(...)` с quoted/unquoted значением, которое после CSS string decoding является непустым relative POSIX path и:

- содержит только ASCII segments `[A-Za-z0-9][A-Za-z0-9._-]*`;
- не содержит empty, `.` или `..` segment, backslash, control, whitespace, percent escape, query, fragment, colon, scheme, protocol-relative/leading slash;
- заканчивается literal lowercase `.css`;
- разрешается относительно importing member directory;
- не равен root-level `pilot.css` и не разрешается в already-owned `/pilot/assets/pilot.css`.

Remote/data/package imports (`http:`, `https:`, `//`, `data:`, `@shlz/...`), non-CSS imports, CSS escape, invalid UTF-8, duplicate logical route with different file identity и dependency outside root делают весь graph invalid (`503`), а не пропускаются browser-у. Повторный import того же canonical member допустим и даёт одну manifest entry. Cycle допустим только как уже посещённая canonical identity и не расширяет обход. Максимум — 256 unique members, глубина — 32, суммарный размер — 8 MiB; превышение — `503`.

Один request выполняет atomic capture целого graph до отправки headers:

1. фиксирует для dist root и каждого посещённого directory `device + inode + type + owner + mode + mtime`, затем для каждого member до открытия — те же значения плюс size;
2. открывает member ровно один раз, сверяет descriptor `device + inode + type + owner + mode + size + mtime`, читает exact bytes и вычисляет SHA-256 captured bytes;
3. строит exact sorted mapping `public route → relative path → captured identity + SHA-256 + bytes` только из этих captured bytes;
4. после полного обхода повторяет `lstat` каждого directory/member и `fstat` ещё открытых descriptors, требуя byte-exact равенство всех captured identity fields; каждый descriptor перематывается и повторно читается до EOF, а повторный size/SHA-256 обязан совпасть с captured bytes; затем все descriptors закрываются attempt-all;
5. только после успешной общей revalidation выбирает exact request route из captured manifest и формирует response из captured bytes.

Любая symlink/path/owner/mode/identity/size/mtime/hash inconsistency или directory-entry change в границах одного request даёт `503`; ни старые, ни новые, ни смешанные partial bytes не выдаются. Request path никогда не выбирает filesystem path напрямую.

После завершения request никакая identity не обязана оставаться в process memory. Между двумя отдельными requests deployment может легитимно атомарно заменить официальный export; следующий request заново захватит и проверит целый новый graph. Поэтому изменение export не требует restart process и не считается ошибкой само по себе. Если replacement пересекается по времени с capture, текущий request обязан получить `503`, а следующий request после завершённой replacement может получить новый internally consistent export.

Запрещены SysV/shared-memory semaphore или segment, APCu/opcache user cache, filesystem manifest/cache/lock/sentinel, daemon/guardian, background watcher, subprocess и иная cross-request coordination. Реализация не оставляет cache, lock, temp, shared-memory или process-global residue. Весь request-owned capture освобождается/закрывается attempt-all и не является external dependency.

Этот manifest описывает public export, а не private структуру Showcase. FMonitor не сканирует `apps/showcase`, `packages/styles` source или `node_modules` и не утверждает, что конкретный набор component filenames вечен.

## 4. Public route mapping и HTTP semantics

Entry member сохраняет inherited URL:

```text
shlz.css → /pilot/assets/shlz.css
```

Каждый dependency доступен по browser-relative URL:

```text
foundation.css          → /pilot/assets/foundation.css
components/button.css   → /pilot/assets/components/button.css
```

Поэтому exact bytes `@import url("./components/button.css")` в root не переписываются. Nested imports также разрешаются browser-ом относительно URL importing member.

Для каждого exact manifest route:

- `GET` возвращает `200`, exact member bytes, `Content-Type: text/css; charset=UTF-8` и exact byte `Content-Length`;
- `HEAD` выполняет те же validation/integrity reads, возвращает те же application headers/length, но empty body;
- каждый response внутренне согласован с одним per-request capture; repeated/concurrent requests на неизменённом fixture дают exact одинаковые bytes, но не полагаются на общую identity/cache;
- inherited security headers, `Cache-Control: no-store`, Host boundary, redaction и no-cookie/no-session rules применяются без исключений;
- asset не требует `REMOTE_USER`, DB connection, user lookup, process capability или body read.

Route grammar после one-time path decoding принимает только exact `/pilot/assets/` + manifest-shaped relative CSS path из section 3. Duplicate slash, trailing slash, dot segment, encoded segment/separator/dot/percent, backslash, NUL, extra suffix и non-CSS path дают exact inherited `404 Not found.\n` до identity, DB или file bytes. Любой non-`GET|HEAD` method на syntactically valid asset candidate даёт exact inherited `405 Method not allowed.\n` с `Allow: GET, HEAD` до identity, DB, manifest/file reads и request-body read.

Syntactically valid CSS route, отсутствующий в successfully built manifest, возвращает exact `404` и не открывает одноимённый filesystem file. Если manifest нельзя безопасно построить/проверить, root route и любой syntactically valid dependency candidate fail closed как exact `503`, чтобы broken configuration не изображалась отсутствующим component asset. Malformed route/method по-прежнему имеет более ранний `404/405` priority.

`/pilot/assets/pilot.css` остаётся отдельным application asset `PILOT-UI-SHELL-001`; shlz manifest не перехватывает, не замещает и не затеняет его.

## 5. Independently fixed executable example

Gate 2 создаёт task-owned public dist fixture; expected graph задан здесь, а не извлекается из production renderer или private Showcase:

```text
dist/shlz.css
  @import "./foundation.css";
  @import url("./components/button.css") layer(components);
  @import url(components/card.css) screen;

dist/foundation.css
  @import "./tokens/colors.css";

dist/components/card.css
  @import "../tokens/colors.css";
```

Fixed manifest и routes:

```text
/pilot/assets/shlz.css                  → shlz.css
/pilot/assets/foundation.css            → foundation.css
/pilot/assets/components/button.css     → components/button.css
/pilot/assets/components/card.css       → components/card.css
/pilot/assets/tokens/colors.css          → tokens/colors.css
```

Fixture также содержит unreferenced readable `dist/private.css`; `/pilot/assets/private.css` обязан вернуть `404` и bytes не читаются. Для всех пяти manifest members Gate 2 доказывает exact `GET` bytes, exact `text/css; charset=UTF-8`, length и HEAD parity. Root HTML сохраняет stylesheet order `/pilot/assets/shlz.css`, затем `/pilot/assets/pilot.css`.

Отдельные fixtures независимо дают:

- traversal (`../secret.css`, encoded/double-encoded variants), absolute/remote/package import, query/fragment, uppercase/non-CSS suffix и malformed import → redacted `503` graph, ни target, ни secret bytes не выдаются;
- dependency symlink, symlink directory component, regular-file swap/removal либо graph replacement во время per-request capture → `503`, ни target, ни replacement/mixed bytes не выдаются;
- legitimate atomic official export replacement, завершённая между двумя requests, позволяет первому response вернуть целый старый graph, а следующему — целый новый graph; equality требуется только пока fixture не менялся;
- unknown well-formed CSS route → `404`; malformed route → `404`; `POST|PUT|PATCH|DELETE|OPTIONS` manifest candidate → `405` + exact Allow;
- graph duplicate through the same normalized relative target remains one route; graph collision with `pilot.css`, different-identity alias, limit overflow or invalid UTF-8 → `503`.

Текущий one-file standalone `dist/shlz.css` является valid graph из одного member и продолжает работать без special case. Таким образом тест не hardcode-ит нынешнюю форму export, но чувствителен к исходному дефекту split export.

## 6. Real-browser acceptance

После Gate 4 автор реализации запускает actual prepared demo data и записывает Chromium evidence для final working queue и final working object card at `1440×900`, `768×1024`, `320×568`:

1. все same-origin stylesheet requests root `shlz.css`, его transitive imports и `pilot.css` имеют status `200` и exact CSS MIME; failed/blocked/mixed-content/CORS requests отсутствуют;
2. browser console содержит zero CSS parse/import/MIME/network errors и zero application errors;
3. computed styles доказывают применение минимум одного публичного `.shlz-*` component rule и одного `--shlz-*` custom property из served export, а также application `.fm2-*` rule; oracle фиксирует property/value и owning stylesheet URL, не цветовой screenshot guess;
4. `document.documentElement.scrollWidth <= clientWidth` и `document.body.scrollWidth <= clientWidth`; queue/card primary regions не создают horizontal overflow на всех трёх viewport;
5. visible links/native controls достигаются `Tab` в DOM order, имеют nonzero rect и visible focus outline; narrow responsive order, wrapping и доступный следующий шаг остаются `PILOT-UI-SHELL-001`/`PILOT-E2E-FLOW-001`;
6. screenshot и request/console/computed-style/overflow/focus log привязаны к exact Git HEAD, browser/version, route и viewport.

Browser evidence не добавляет новый harness и не заменяет deterministic Gate 2. Любая CSS request/console error, неприменённый shlz rule, overflow либо потеря responsive/focus возвращает срез в Gate 4. Existing `pilot.css` не удаляется и не поглощается design-system asset layer.

## 7. Zero mutation, authorization и audit

У asset read нет business actor, authorization capability или domain audit event. Для обычного неизменяемого fixture before/after fingerprints `fm2_*`, legacy data, artifact store, application CSS и configured shlz dist root byte-equivalent. Никакой request не пишет cache/build output, manifest/lock/temp/sentinel file, shared memory, event, log, session или cookie. Только request-local in-memory capture допустим; process-global/external cache и committed/generated manifest в FMonitor запрещены.

## 8. Out of scope

- изменение/сборка/публикация `shlz-ui` или private Showcase;
- копирование/инлайнинг/переписывание CSS, tokens, fonts, icons и component markup;
- generic static server, directory browsing, source maps и arbitrary media/font assets;
- cache validators, compression, CDN, immutable production caching и любая cross-request coordination;
- изменение business flow, `InstallationProcess`, HTML views или `pilot.css`;
- harness, CI, Bitrix-history и unrelated architectural refactoring.

## 9. Gate 2 boundary

Новый отдельно поставленный Gate 2 agent пишет минимальный RED через existing real raw HTTP public seam и task-owned split-export fixture section 5. Он не вызывает private parser/manifest methods, не читает production source tree как expected oracle, не меняет harness и не использует private Showcase. Existing focused HTTP/auth/UI/E2E tests остаются regression obligations; новый тест не ослабляет их exact outcomes или security priority.

Gate 3 reviewer независимо проверяет traceability, import grammar, stateless per-request manifest sensitivity, trusted owner/mode boundary, GET/HEAD parity, отсутствие external cache/guardian dependency, fail-closed filesystem boundary и captured RED. Gate 4 разрешён только после `APPROVED` Gate 3. Gate 5 требует отдельного свежего reviewer и real Chromium evidence section 6.

## 10. Gate 1 approval

- Product owner: project user
- Approved by: separately tasked specification agent `/root/shlz_assets_spec`
- Date: `2026-08-29`
- Decision: `APPROVED`
- Basis: пользователь поручил автономно довести пилот до visually ready состояния, использовать полный public `shlz-ui` export без копирования и сохранить обязательные SSD/TDD/security gates; real Chromium acceptance at `6313e1a` зафиксировал missing dependency-serving behavior.

Version `0.2` replaces `0.1`: она устраняет cross-request/process-lifetime identity memory и разрешает официальную atomic replacement между requests без ослабления atomicity внутри одного response.

Gate 2 разрешён только для exact version `0.2` и должен выполняться новым bounded-context agent.
