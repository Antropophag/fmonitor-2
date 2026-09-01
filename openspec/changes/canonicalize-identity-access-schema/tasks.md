## 1. Gate 1 — executable schema contract

- [ ] 1.1 Снять независимое evidence exact production fingerprints всех девяти identity/access таблиц (columns/order/types/nullability/default/extra, enum, primary/unique/secondary indexes, foreign keys/delete rules, engine, charset/collation) из owners и реально populated schema; verification: evidence и расхождения перечислены без изменения production code.
- [ ] 1.2 Создать/утвердить нормативную executable schema specification со stable spec id, public canonical-runner seam, clean/repeat/populated/partial/single-table-conflict/family-conflict/prefix/no-runtime-DDL examples и deterministic expected results; verification: Gate 1 approval явно отмечает `GRILL-002` как blocker только для authority/authorization behavior и не утверждает ambiguous RBAC semantics.

## 2. Gates 2–3 — RED и independent test review

- [ ] 2.1 Написать smallest focused MariaDB tests через public migration seam для clean family, safe repeat и fully populated compatible family с сохранением всех строк; verification: captured RED падает из-за отсутствующего canonical identity/access migration, а не setup.
- [ ] 2.2 Добавить RED для representative exact-compatible partial states,
  dependency-ordered recovery, несовместимого fingerprint каждой релевантной
  category и family-level relationship conflict; verification: compatible
  existing members/rows byte-equivalent preserved, создаются только missing
  members, а conflict даёт zero mutation/version registration.
- [ ] 2.3 Добавить RED для prefixed target рядом с decoy namespace, invalid prefix, block/unblock на pre-migrated schema и missing-schema runtime path без `CREATE`/`ALTER`/`DROP`; verification: tests чувствительны к runtime DDL и не делают normative assertion о том, какая RBAC authority модель правильна.
- [ ] 2.4 Передать approved spec, tests и RED evidence отдельно назначенному independent reviewer; verification: `reviews/tests/<spec-id>.md` имеет `APPROVED`, а `CHANGES_REQUESTED` возвращает работу к Gate 1/2 до production edits.

## 3. Gate 4 — minimal GREEN

- [ ] 3.1 Реализовать один production migration owner с full-family preflight,
  Gate 1 literal fingerprints и restartable exact-compatible partial recovery;
  verification: clean/populated/repeat/partial/conflict green, existing rows,
  counters и append-only audit history неизменны.
- [ ] 3.2 Зарегистрировать identity/access migration как literal v6 после landed workforce v5 и сделать runner final version `6`; verification: clean/partial success включают `6`, repeat исключает `6`, conflict сообщает `schemaVersion=6`, canonical order проходит focused runner test.
- [ ] 3.3 Перевести explicit destructive bootstrap на canonical schema readiness, сохранив seed/rebuild только как отдельно вызываемую disposable operation; verification: clean bootstrap остаётся воспроизводимым, а canonical repeat никогда не seed-ит и не rebuild-ит данные.
- [ ] 3.4 Удалить identity/access `CREATE`/schema repair из request/runtime paths, включая lazy ownership `fm2_pilot_user_status_events`, и оставить consumers fail-closed; verification: auth/access characterization и block/unblock audit tests green без runtime DDL.
- [ ] 3.5 Остановить любые изменения queries, role catalogue, permission meanings, legacy fallback или authorization outcomes как blocked by `GRILL-002`; verification: production diff ограничен schema ownership/version/bootstrap wiring, либо behavior change вынесен в отдельную approved Gate 1 slice.

## 4. Regression, architecture и Gate 5

- [ ] 4.1 Выполнить focused identity/access DB suite, canonical clean/repeat migration stage, built-image/fresh-test lifecycle и relevant auth/access verifiers; verification: все новые сценарии green, а известные GRILL-002 failures не скрыты и классифицированы отдельно.
- [ ] 4.2 Выполнить `make architecture-check`, lint и полный `make verify`; verification: runtime production DDL debt уменьшается без обновления baseline для сокрытия долга, unrelated regression отсутствует.
- [ ] 4.3 Передать approved spec/tests, production diff и verification evidence отдельно назначенному independent code reviewer; verification: `reviews/code/<spec-id>.md` имеет `APPROVED`, а изменение tests после review перезапускает Gate 2/3.

## 5. Done definition

- [ ] 5.1 Подтвердить, что OpenSpec artifacts согласованы, strict validation green, все Gates 1–5 завершены, canonical runner владеет ровно nine-table family, populated data сохранены, exact-compatible partial recovery restartable, incompatible conflict fail-closed, prefix изолирован, runtime DDL отсутствует, destructive rebuild остаётся explicit и GRILL-002 behavior не заморожен; verification: финальный status/evidence пакет содержит команды, результаты и ссылки на оба independent reviews.
