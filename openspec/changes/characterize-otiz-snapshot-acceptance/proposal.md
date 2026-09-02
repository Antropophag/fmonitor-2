## Why

Перед переносом финансового перехода из rapid-pilot нужен узкий исполняемый
oracle фактического поведения при принятии premium snapshot. Текущий широкий
verifier смешивает принятие с расчётом и выплатами, заранее создаёт OTIZ-схему и
обходит настоящий LocalAuth/router, поэтому не защищает границу, которую затем
должен заменить application seam.

## What Changes

- Добавить строго `PILOT_ONLY` characterization для действия сотрудника ОТиЗ
  `POST /pilot/otiz/snapshots/{id}/accept` на литерально подготовленном draft.
- Зафиксировать публичные HTTP-результаты, изменение трёх полей snapshot,
  единственный audit event, отсутствие изменения дочернего содержимого,
  blocker/non-blocker, replay и конкурентную сериализацию.
- Доказать текущий порядок admission: LocalAuth → broad `otiz.manage` →
  constructor-time DDL двенадцати таблиц → CSRF → business checks.
- Запускать oracle воспроизводимо, с приватным SQL namespace, настоящими
  параллельными соединениями и безопасным владением только своими LocalAuth
  session-файлами.
- Подключить oracle к canonical characterization stage и обязательной
  архитектурной/регрессионной проверке.
- Не изменять product behavior, production application seam или rapid-pilot
  domain logic в этом change.

Target public seam будущего migration slice:
`PremiumDecisions::acceptPremiumSnapshot`. Он указан только как направление
владения; этот change его не утверждает и не реализует.

Release value: до переноса критического финансового перехода появляется
изолированный regression oracle, способный обнаружить drift авторизации,
блокеров, audit/history, replay/concurrency и скрытого runtime DDL.

NEEDS_GRILL: точная capability принятия, meaning of acceptance, достаточность
evidence и separation of duties остаются в GRILL-001. Это блокирует target
`PREMIUM-SNAPSHOT-ACCEPTANCE-001`, canonical premium schema и downstream
closure/payment slices, но не блокирует данный `PILOT_ONLY` characterization.

Явные non-goals:

- утверждение формул, расчёта snapshot или его `content_hash`;
- утверждение broad `otiz.manage`, blocker/warning semantics, self-acceptance
  или non-idempotent replay как target product rules;
- перенос поведения в application module;
- canonicalization/redesign OTIZ persistence;
- characterization payment closure, payment completion, reversal, history или
  export;
- доказательство глобальной DB-immutability дочерних таблиц другими writers.

## Capabilities

### New Capabilities

- `verification/otiz-snapshot-acceptance-characterization`: воспроизводимый
  `PILOT_ONLY` oracle текущего публичного перехода принятия OTIZ snapshot.

### Modified Capabilities

Нет.

## Impact

- Новый focused verifier в `rapid-pilot/`, изменяющий только characterization
  coverage и test-owned fixtures.
- Canonical characterization/verification stage и его детерминированный
  transcript.
- Тестовая MariaDB, loopback PHP workers и общий каталог LocalAuth sessions;
  verifier обязан изолировать порт/cookie/session ids и не удалять чужие файлы.
- Никаких production schema/API/dependency changes в этом change.
