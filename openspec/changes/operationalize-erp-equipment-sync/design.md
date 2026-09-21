## Context

См. `proposal.md` и `specs/erp-equipment-facts-operations/spec.md`. Baseline — `origin/main` `45fa7dee` после PR #216 и migration v31 на сохраняемом стенде 8093. В native коде уже существуют `EquipmentFactsApplication`, ERP source/delivery composition, migration/projection/history/read model, scheduler job и object-card block. Дефекты находятся на operational boundaries: claim allowlist, реальный SQL contract, source cardinality, secret/config transport, process startup/readiness и retry diagnostics.

Legacy `../fmonitor` используется только как read-only oracle для `Integration.php::shlz_prodorders`; его controller, credentials, staging/truncate flow и domain logic не копируются. Владелец persistence остаётся `InstallationProcess/EquipmentFactsApplication`; Jobs может только orchestration-ить public command и интерпретировать receipt. `rapid-pilot` не получает новых адаптеров или логики. Изменения затрагивают architecture/production-runtime/deployment boundaries и должны быть включены в verification plan.

## Goals / Non-Goals

**Goals:**

- Сделать один canonical путь `scheduler/manual command → durable job → worker claim/handler → bounded ERP fetch → EquipmentFactsApplication → projection/card` реально исполнимым.
- Ограничить ERP I/O локальным candidate set, сохранив atomic authoritative application и privacy invariants.
- Сделать runtime configuration, startup и readiness операционно честными и воспроизводимыми для пилота.
- Сохранить текущие данные стенда и additive migration/recovery contract.

**Non-Goals:**

- Новый scheduler, queue, integration framework, generic SQL abstraction или перенос legacy staging.
- Сканирование полного ERP-каталога, приблизительное matching, очистка отсутствующих ERP orders или хранение raw order numbers.
- Reset стенда, удаление volumes, изменение существующего процесса монтажного дела, merge или внешний deployment.

## Decisions

1. **Root сначала расширяет стабильный executable contract и пишет focused RED через public seams.** Минимум два обязательных RED: реальный `JobHandlerClaim → handler → worker` для `erp.equipment-facts.sync` и source contract, который проверяет fully-qualified identifiers/joins плюс bounded local-number chunking. Полная acceptance matrix также покрывает env/startup/readiness/retry/card/recovery. Альтернатива — unit-тест private branches — отклонена: она не ловит именно обнаруженный E2E разрыв.

2. **Candidate строится в отдельном worktree от свежего `origin/main`.** Активный №157 worktree грязный и содержит независимый WIP; он не изменяется. Текущая owner authorization, authorship root/executor/reviewers и новый current-delivery pointer фиксируются в delivery record до Gate 2. Альтернатива — очищать либо переиспользовать активный checkout — нарушает preservation rule.

3. **Local candidate set вычисляется до ERP connection.** Read-side получает уникальные trim-нормализованные ненулевые номера и отдельно знает ambiguous mappings. Source принимает только candidate values, режет их на проверяемый bounded chunk size и использует driver parameters, не string interpolation. `maxRows` становится per-bounded-response safety guard, а не глобальным snapshot cap; oversized/partial chunk является source failure. Альтернатива — поднять 10 000 — сохраняет неограниченный I/O и memory risk.

4. **Каждый chunk выполняет два согласованных bounded query по подтверждённой legacy-схеме.** Все ERP objects имеют `[1c-erp].` prefix; orders и shipment stages используют ровно утверждённые join fields/filters. Sentinel/NULL исключаются в stage predicate до `MIN`. Результаты объединяются по exact normalized order number; duplicate/conflicting output invalidates whole batch. По возможности transport использует read-only transaction/snapshot, но отсутствие snapshot capability не разрешает partial apply. Альтернатива — unqualified tables или database в DSN — несовместима с реальным login.

5. **Failure переводится в command для того же application owner.** Delivery ловит source/validation failures, редактирует их до allowlisted reason (`SOURCE_UNAVAILABLE`/`SOURCE_INVALID`) и вызывает failed command с новым run identity. Worker видит typed receipt/outcome, завершает success и retry-ит technical failure. Ни exception text драйвера, SQL, DSN, параметры, row dumps, raw numbers не переходят boundary. Альтернатива — exception до application seam — теряет run diagnostics и воспроизводит текущий дефект.

6. **Versioned claim contract регистрируется централизованно.** ERP type/version входит в тот же точный allowlist/decoder, что остальные handlers; payload/schema validation происходит до source access. Scheduler и manual command создают один canonical payload/idempotency key, привязанный к hourly slot либо explicit manual run. Альтернатива — special-case после claim — оставляет seam несовместимым.

7. **`.env` является единственным pilot secret input.** `FMONITOR_ERP_PASSWORD_FILE` и `FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY_FILE` удаляются из обязательной validation/composition; direct values проходят `local-runtime-env` allowlist и Compose environment mapping. `.env.example` содержит непригодные placeholder values. Скрипт не выводит values; qualification проверяет tracked/ignored state и restrictive mode без записи secrets в evidence. Альтернатива — dual mandatory modes — создаёт неоднозначную конфигурацию и не выполняет owner decision.

8. **Canonical templates остаются source of generated Docker/Compose.** Меняются `tools/delivery/*.in`, затем штатная generation/parity command обновляет top-level `Dockerfile`/`compose.yaml`; direct-only generated edits запрещены. Jobs services получают одинаковые ERP inputs. Bounded values имеют conservative defaults/ranges, но реальные credentials не имеют defaults.

9. **Обычный `make up` становится полным pilot contour.** Предпочтение — убрать необходимость знать profile `jobs` из штатного пути: target явно включает scheduler/worker services либо включает profile internally. Отдельный target допустим только если он становится единственным ясно документированным owner-approved startup, а старый UI-only path не называется complete/ready. Down/upgrade path не использует volume removal.

10. **Readiness проверяет не просто container presence.** Scheduler и worker пишут/обновляют существующее либо минимально расширенное operational heartbeat состояние; readiness применяет bounded freshness threshold и проверяет configuration qualification. Нужна additive migration только если текущая heartbeat schema не представляет scheduler; migration catalogue, recovery/backup inventories и disposable restore verifier обновляются вместе. Альтернатива — Compose healthcheck только по process PID — допускает зависший scheduler и ложный GREEN.

11. **Qualification разделена на детерминированные checks и live stand evidence.** Tests используют fake transport/disposable DB и не требуют secrets. После Gate 3 executor state-preserving обновляет 8093, локально создаёт/обновляет ignored `.env` из legacy evidence без печати, проверяет permissions, поднимает полный contour, ждёт terminal run и проверяет cards/projections/history через разрешённые seams. Receipt/evidence редактируются до IDs/status/timestamps/object IDs. Никаких reset/down-with-volumes.

12. **Публикация следует CRITICAL/планнер-выбранному lane.** После root spec/tests и intended RED отдельный `gpt-5.6-sol/low` executor реализует. Независимые reviewers выполняют planner-required Gate 3/5 по prepared snapshots. Локально запускаются только bounded commands; затем один exact-source GitHub CI run с полным failure inventory. UNKNOWN никогда не повышается до GREEN.

## Risks / Trade-offs

- [Количество локальных номеров всё ещё велико] → строгий chunk size, per-query timeout/row cap и bounded aggregate validation; никогда не возвращаться к full catalogue.
- [ERP dialect/driver ограничивает число parameters] → conservative configurable chunk size в allowlisted range и tests нескольких chunks.
- [Два query внутри chunk видят разные моменты] → использовать read-only snapshot при поддержке; иначе принимать batch только после обоих запросов и документировать консистентность как live observation, не как выдуманный GREEN.
- [Retry после failed run создаёт несколько run records] → это допустимая append-only диагностика; projection/history меняются только на successful authoritative state.
- [Readiness может стать временно RED сразу после startup] → явный startup grace/freshness contract и bounded wait в deployment qualification, без ослабления steady-state проверки.
- [Прямые secrets в environment видимы локальным privileged tooling] → `.env` mode, ограниченный host access, отсутствие echo/logging и non-secret placeholders; это принятый владельцем pilot trade-off.
- [State-preserving стенд отличается от disposable tests] → перед изменением снять безопасный inventory/backup readiness, применять только additive migration и иметь code rollback без data deletion.

## Migration Plan

1. Создать normative spec amendment/verification input и planner plan; root пишет полный focused RED candidate и сохраняет intended failures вне checkout.
2. После Gate 3 отдельный executor реализует source/job/config/startup/readiness/card/recovery изменения и generated-source parity.
3. Прогнать planner-selected bounded checks, architecture/auth checks для затронутых HTTP файлов и disposable migration/recovery paths; полный локальный suite запрещён.
4. Подготовить state-preserving stand update: безопасно проверить текущие services/data/schema, локально перенести legacy значения в ignored mode-restricted `.env` без вывода и создать recoverable backup по существующему процессу, если он предусмотрен.
5. Обновить code/images и применить только additive migrations; поднять canonical complete contour без удаления volumes.
6. Дождаться healthy heartbeats и completed ERP run; проверить 1226/1427/2238/2239, отсутствие mapping для 1318 и same-slot/identical-state history idempotency. При failure сохранить данные, остановить дальнейшую публикацию и rollback-нуть code/services, не schema/history.
7. Зафиксировать reviewed exact source, push/PR, один exact-source CI и полный failure inventory; после GREEN выполнить независимый Gate 5. Merge и внешний deployment остаются заблокированы до явного owner confirmation.

## Open Questions

Нет вопросов, которые безопасно отложить без изменения контракта или task breakdown. Фактическая поддержка ERP snapshot isolation и live connectivity являются результатами qualification, а не основанием ослаблять acceptance.
