## Context

См. proposal и `docs/architecture/yii2-auth-migration-design-2026-09-09.md`.
Canonical auth/RBAC schema уже существует; меняется runtime owner. Владелец явно
разрешил повторный вход, поэтому legacy cookie/payload continuity не требуется.

## Goals / Non-Goals

**Goals:** глубокий IdentityAccess read module за Yii User/RBAC interfaces,
стандартная Yii session, двухшаговый login без изменения пользовательского потока,
первый реальный protected HTML route и публичный black-box seam.

**Non-Goals:** invitation/activation/reissue, status/role writes, остальные pilot
routes, shared multi-node session database и переключение рабочего стенда.

## Decisions

- `yii\web\User` владеет identity lifecycle; read-only identity реализует
  `IdentityInterface` поверх Yii DB/DAO и допускает только active canonical user.
- `yii\web\Session` использует новый `fm2yii[_port]`, persistent save path и штатную
  PHP serialization. Compatibility handler и старый codec запрещены.
- Yii Request владеет CSRF. Перед success response session явно закрывается; warning
  или failure преобразуется ErrorHandler в safe 503 до отправки redirect/body.
- Existing Argon2id hashes проверяются native `password_verify`: текущий Yii Security
  password validator предварительно допускает bcrypt-shaped hash. Hash не мигрирует
  при login.
- `CheckAccessInterface` вызывает существующий `AuthorizeLocalActor`; `User::can`
  вызывается с отключённым cache. Canonical FMonitor tables остаются persistence
  owner; Yii RBAC tables не создаются.
- `AccessControl` задаёт `access.administer` для roles action. Read renderer получает
  catalog через существующий directory read owner; controller не читает SQL сам.
- Rapid-pilot остаётся только oracle. GREEN удаляет reachability LocalAuth/custom
  session из перенесённых routes; global files удаляются после последнего consumer.
- Architecture check должен запрещать новый router, direct controller SQL/auth facts,
  custom session handler и production include старого auth для этих routes.

## Risks / Trade-offs

- Session close происходит поздно → response success при незафиксированном state.
  Мера: close до response send и fault-oriented public test.
- `User::can` скрывает unavailable за boolean → adapter выбрасывает отдельный safe
  infrastructure exception, который централизованно становится 503; denial остаётся 403.
- Block между identity renewal и command → state-changing application owner повторяет
  authorization внутри собственной транзакционной политики.
- Two-step state допускает подмену email между POST → password step carries normalized
  server-validated email and repeats canonical lookup before verification.

## Migration Plan

В isolated contour создать spec/RED и independent Gate3; реализовать login/session/
roles slice; focused GREEN и Gate5. Cutover позже публикует новый cookie namespace,
поэтому rollback возвращает старый entrypoint и пользователи снова входят. Ни один
вариант не преобразует session payload или удаляет canonical access/history data.
