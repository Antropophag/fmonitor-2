## 1. Gate 1–3: executable restore contract

- [x] 1.1 Создать normative executable spec `specs/PRODUCTION-RUNTIME-RESTORE-001.md` из delta: public CLI inputs/outcomes, bundle manifest allowlist, empty-target rule, exact row/file/session assertions, update/rollback matrix и deferred #34; получить независимый Gate 1 review.
- [x] 1.2 Написать independently-authored RED для backup public seam: stopped writers, новый destination, manifest-last publication, exact DB/state hashes, secret exclusion, active-writer и occupied-destination zero-mutation; выполнить RED и получить Gate 3.
- [x] 1.3 Написать independently-authored RED для restore public seam: valid bundle, corrupt/missing/extra/traversal/symlink members, nonempty DB/state targets и failure без готового target; выполнить RED и получить Gate 3.

## 2. Backup bundle

- [x] 2.1 Добавить application owner `app/RuntimeRestore` и тонкий backup CLI с explicit config/path, stable non-disclosing outcomes и запретом вызова из HTTP/runtime; проверить architecture check и focused RED.
- [x] 2.2 Реализовать stopped-writer admission, MariaDB logical dump и symlink-free state archive; проверить, что source DB/state не меняются и active writer отказан.
- [x] 2.3 Реализовать versioned manifest с source/image identities, DB/state size+SHA-256 и отсортированным member inventory; проверить manifest-last atomic publication, mode 0600 и отсутствие synthetic credential values.

## 3. Restore в пустой изолированный contour

- [x] 3.1 Реализовать полный read-only preflight bundle и canonical path validation до target mutation; проверить все corruption/traversal/duplicate/symlink случаи и exact zero-target delta.
- [x] 3.2 Реализовать DB import и state materialization только в explicit empty target с runtime ownership/modes; проверить partial failure как not-ready и отсутствие mutation source/соседних contours.
- [x] 3.3 Подключить read-only Runtime readiness как terminal admission; проверить, что restore success публикуется только после schema/storage/DB readiness и повтор не merge-ит существующий target.

## 4. Synthetic restore и update drill

- [x] 4.1 Поднять task-owned populated source contour существующим production browser fixture, создать bundle и сохранить primary evidence вне repository; проверить точные source/image/bundle identities и elapsed timestamps.
- [x] 4.2 Восстановить bundle в новый project/DB/state volumes и сравнить exact ordered users/roles/domain/history rows, PDF/photo/session hashes и связи; проверить existing-cookie authorized read и новую append-only command.
- [x] 4.3 Собрать второй reviewed exact image, выполнить отдельные migrations и recreate web/php; проверить readiness, existing session/private files/history и новый browser write после update.
- [x] 4.4 Проверить additive-compatible image rollback без удаления schema/history; отдельным failure case зафиксировать, что incompatible migration требует restore-forward и не допускает blind DB downgrade.

## 5. Runbook, review и Done

- [x] 5.1 Обновить #27 production runbook exact backup/restore/update/rollback командами, private evidence policy и NEEDS_GRILL для retention/RPO/RTO; проверить docs references и отсутствие secrets.
- [x] 5.2 Записать безопасный публичный drill report: фактическая длительность, bundle/source/image IDs, exact сверки и ограничения; primary dump/archive оставить вне repository mode 0600.
- [ ] 5.3 Получить независимый Gate 5 review production diff, tests и evidence; выполнить focused restore/runtime/browser проверки, architecture check и один полный CI по принятой матрице.
- [ ] 5.4 Сверить Done: source contour не изменён, восстановленный contour проходит exact data/file/auth/session checks и update drill, cleanup ограничен task-owned targets, рабочий stand и volumes не затронуты.

## 6. Deferred после #34

- [x] 6.1 После завершения #34 создать отдельный OpenSpec slice для worker quiesce, pending/leased jobs, outbox dedup/retry и unknown external delivery без реальных sends; до этого не помечать background recovery проверенным.
