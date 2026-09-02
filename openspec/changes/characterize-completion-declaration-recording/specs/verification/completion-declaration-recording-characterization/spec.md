## Purpose

Зафиксировать воспроизводимый `PILOT_ONLY` oracle первой записи декларации через реальный rapid-pilot HTTP seam, сохранив неутверждённые terminal, evidence и authorization semantics вне target contract.

## ADDED Requirements

### Requirement: Первая декларация характеризуется через публичный HTTP seam
Characterization SHALL отправлять аутентифицированный form POST с действительным CSRF, `action=record_declaration`, валидной датой и непустыми реквизитами для уникального дела с pilot progress 85 и заранее существующим PTO fact. Она SHALL наблюдать `303`, redirect на тот же object completion anchor, `Cache-Control: no-store` и ровно один declaration fact с submitted date, обрезанными по краям details, actor id и bounded live Moscow timestamp. PTO и остальные факты SHALL остаться неизменными.

#### Scenario: Первая декларация сохраняется
- **WHEN** активный pilot-пользователь отправляет declaration date `<today>` и details `  ЕАЭС N RU Д-RU.РА01.А.12345/26  ` для дела с progress 85 и PTO
- **THEN** ответ имеет status `303`, а единственный declaration fact хранит `<today>`, exact trimmed details `ЕАЭС N RU Д-RU.РА01.А.12345/26`, actor и bounded timestamp без изменения PTO/case/order/checklist

### Requirement: PTO, duplicate и payload проверки следуют фактическому порядку
Characterization SHALL наблюдать threshold rejection до declaration prerequisite, missing-PTO rejection до payload validation и duplicate rejection до changed payload validation. Эти границы MUST оставаться `PILOT_ONLY` и MUST NOT утверждать target completion workflow.

#### Scenario: Progress скрывает отсутствие PTO
- **WHEN** declaration request для дела с progress 84 не имеет PTO и содержит invalid payload
- **THEN** ответ имеет status `409` с причиной достижения 85%, а completion fact не появляется

#### Scenario: PTO отсутствует
- **WHEN** declaration request для дела с progress 85 не имеет PTO и содержит invalid date/details
- **THEN** ответ имеет status `409` с причиной сначала зафиксировать PTO, а declaration не появляется

#### Scenario: Duplicate скрывает изменённый payload
- **WHEN** дело уже имеет declaration и replay содержит другую будущую дату и пустые details
- **THEN** ответ имеет status `409` с причиной уже существующей декларации, а исходный fact остаётся byte-equivalent

### Requirement: Date и Unicode details характеризуются точно
Characterization SHALL фиксировать exact date grammar/calendar/future rejection, outer trim, non-empty-after-trim и максимум 500 Unicode characters при явно запущенных и проверенных `mb_internal_encoding=UTF-8` server workers. Она SHALL также зафиксировать текущий риск отсутствия нижней границы и ordering against PTO/opening/order dates.

#### Scenario: Outer whitespace обрезается
- **WHEN** допустимые details окружены пробелами и переводами строк
- **THEN** declaration принимается, а persisted details сохраняют внутреннее содержимое и исключают только outer whitespace

#### Scenario: Ровно 500 Unicode characters принимаются
- **WHEN** details состоят ровно из 500 символов `Ж` и date допустима
- **THEN** declaration принимается и хранит ровно эти 500 Unicode characters

#### Scenario: 501 Unicode characters отклоняются
- **WHEN** details состоят из 501 символа `Ж`
- **THEN** ответ имеет status `422` с declaration validation reason и zero mutation

#### Scenario: Whitespace-only details отклоняются
- **WHEN** details содержат только пробелы, tab и newline
- **THEN** ответ имеет status `422` и declaration не появляется

#### Scenario: Невалидная или будущая дата отклоняется
- **WHEN** date равна `2026-02-30` либо calendar `<tomorrow>`
- **THEN** каждый изолированный запрос имеет status `422` и zero mutation

#### Scenario: Дата раньше PTO принимается текущим pilot
- **WHEN** PTO fact датирован `<today>`, а declaration содержит валидную дату `<today minus 30 days>`
- **THEN** pilot отвечает `303` и сохраняет более раннюю declaration date

### Requirement: Фактическая authentication и broad admission видимы
Characterization SHALL доказать CSRF rejection и local-auth redirect для deactivated session. Она SHALL также доказать, что активный пользователь без object scope и coherent unopened/non-working case всё равно допускаются при progress 85 и PTO. Accepted gaps MUST быть security findings, не target authorization requirements.

#### Scenario: Невалидный CSRF
- **WHEN** active session отправляет declaration с неверным form token
- **THEN** ответ имеет status `403`, completion DDL/fact state не изменяется

#### Scenario: Deactivated session
- **WHEN** ранее аутентифицированный пользователь деактивирован до POST
- **THEN** router отвечает `303` с `Location: /pilot/login` до completion mutation

#### Scenario: Active out-of-scope user
- **WHEN** активный пользователь без declaration capability/assignment отправляет допустимую declaration
- **THEN** pilot отвечает `303` и сохраняет его actor id

#### Scenario: Non-working case
- **WHEN** допустимая declaration отправлена в coherent unopened case с progress 85 и PTO
- **THEN** pilot отвечает `303` и сохраняет declaration независимо от process state/opening fields

### Requirement: Replay и multi-worker concurrency сохраняют один fact
Exact и changed sequential replay SHALL возвращать duplicate `409`, не idempotent success. Два одновременных valid declaration requests одного дела через подтверждённые разные server workers и sessions SHALL завершаться unordered `{303,409}` и сохранять ровно один declaration fact без изменения PTO.

#### Scenario: Exact sequential replay
- **WHEN** accepted declaration повторяется с теми же date/details/actor
- **THEN** replay имеет status `409`, а original declaration и PTO byte-equivalent

#### Scenario: Changed sequential replay
- **WHEN** accepted declaration повторяется с другими допустимыми date/details
- **THEN** replay имеет status `409`, а original declaration byte-equivalent

#### Scenario: Concurrent declarations
- **WHEN** два valid clients на разных подтверждённых workers одновременно отправляют declaration одного дела
- **THEN** unordered statuses равны `{303,409}`, а БД содержит один declaration и исходный PTO

### Requirement: Request-triggered DDL характеризуется как risk
При исходно отсутствующей completion table characterization SHALL показать, что active-session/valid-CSRF request для missing object создаёт table до `404`, оставляя её пустой. Это MUST регистрироваться как нарушение target DDL ownership, а не allowlist precedent.

#### Scenario: Missing object создаёт completion table
- **WHEN** active authenticated request направлен на object id без installation case при отсутствующей completion table
- **THEN** ответ имеет status `404`, table существует и содержит zero rows

### Requirement: Harness изолирован и устойчив к live clock
Characterization MUST использовать collision-checked private DB prefix, уникальный loopback port/cookie name и реальный router. LocalAuth shared session directory MUST NOT удаляться или считаться private: harness tracks и удаляет только exact random session files, созданные его cookies, и доказывает byte-equivalence unrelated session files. Все server workers MUST наследовать явные PHP launch options `mbstring.internal_encoding=UTF-8` и `session.gc_probability=0`; preflight MUST подтвердить оба effective values для того же executable/options до HTTP assertions, чтобы `session_start()` не удалил unrelated files. Concurrency MUST выполняться штатным server с минимум двумя подтверждёнными worker PIDs, общей prefixed DB и разными sessions. Date fixtures MUST выводиться из live `Europe/Moscow`; whole-second `recorded_at` MUST попадать в округлённые наружу bounds, а concrete time/date MUST нормализоваться. Midnight crossing MUST перезапустить private namespace с теми же literal fixture ids. Setup, assertion и regression failures MUST различаться.

#### Scenario: Повторяемый изолированный запуск
- **WHEN** verifier запускается дважды последовательно и параллельно с другой invocation
- **THEN** normalized transcript совпадает, owned DB rows/tables/exact session files/processes отсутствуют после runs, а unrelated DB/session decoys byte-equivalent
