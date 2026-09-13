## Purpose

Определяет точный read-only target manifest и canonical Yii2 Compose topology для безопасной подготовки последующего переключения стенда.

## ADDED Requirements

### Requirement: Exact target валидируется без effects
Validator SHALL принимать versioned manifest с exact project, services, database, volumes, immutable current/candidate image digests, external evidence root, authorization id и structurally nonempty unique observed IDs. Relative, broad, home/repository/symlink roots, mutable/missing digest, project `default`, duplicate/unknown target, unresolved value и missing/duplicate ID MUST вернуть safe `TARGET_INVALID` до Docker/DB/filesystem mutation. Freshness/comparison с Docker observations относится к следующему control-plane срезу.

#### Scenario: Валидный target
- **WHEN** все exact values однозначны и immutable
- **THEN** validator возвращает `TARGET_VALID` и deterministic canonical SHA-256 без external effects

#### Scenario: Опасный target
- **WHEN** любое exact условие нарушено
- **THEN** validator возвращает non-zero `TARGET_INVALID`, пустой stderr и не раскрывает rejected value

### Requirement: Production Compose использует единый Yii2 runtime
Parsed template и rendered Compose SHALL быть byte-identical и сохранять принятую topology `db`, `prepare`, `migrate`, `php`, `web`, `jobs-worker`, `jobs-scheduler`. Application services используют один image; migration и jobs имеют exact Yii2 commands, а php/jobs зависят от successful migration. Existing runtime test сохраняет принятые principals, secrets, volumes и executable lifecycle; production commands MUST NOT ссылаться на `rapid-pilot`.

#### Scenario: Migration блокирует consumers
- **WHEN** migration не завершилась успешно
- **THEN** Compose dependency graph не запускает php, jobs-worker и jobs-scheduler

#### Scenario: Isolated runtime
- **WHEN** Compose запускается в disposable project
- **THEN** existing runtime web/jobs health, DML-only principal и restart contracts остаются GREEN
