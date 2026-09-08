# ASSIGNMENT-ORDER-SELECTION-SCHEMA-001 — schema/data RED

Reviewer: `/root/selection_data_gate3`, новый `gpt-5.6-sol`, low, fork none.
Author: root. Verdict: **APPROVED — bounded schema/data tranche**.
Exact clean HEAD: `765de8974d47e61a2d660490e1603ee57efd0155`.

## Independent findings

Два native normative controls prefix0/25 PASS. Остальные36 scenarios доходят
до intended missing public seam; setup/cleanup errors отсутствуют. Fixtures
независимо заданы normative JSON; target actions используют только публичные
apply/isReady/snapshot. Partial/gap/nonempty/registry states конструктивны,
metadata literal/grouping/FK mutations исполнимы. Populated corruptions покрывают
composition/request/event/audit/registry echoes. Valid/exhausted/over-boundary
AUTO_INCREMENT и last-valid-row cases различены, counters сохраняются.
Native state snapshots и owned-schema cleanup проверяют preservation.
Blocking findings нет. Reviewer не редактировал файлы и не повторял capture.

Scope: schema/data, prefix25, leading recovery, corruption, counters. Input,
permissions, lock, observer и child interruption остаются отдельным tranche.
Полный Gate3 открыт; GREEN этим частичным approval не разрешён. Assertions после
missing-facade guard ещё не выполнялись; это intended preimplementation RED.

## Exact evidence

Command: `/opt/homebrew/bin/php tests/InstallationProcess/assignment_order_selection_schema_data_001_test.php`.
Synthetic local DB127.0.0.1:23306, existing approved fixture credentials.
Capture5.054s, terminal exit1, clean before/after.
Archive `/Users/antropophag/.local/state/fmonitor2-verification/selection-schema-data-red-podz4jby`.

```text
7837bb11057e2650356ac2d6c08d397238bf16b69cd739b3fad26200b290397c  tests/InstallationProcess/assignment_order_selection_schema_data_001_test.php
defc68c44e52de056b2fed405094d4638b836009c9d984fbc0447ed5e20cdbd1  tests/Support/SelectionSchemaTestDatabase.php
5f4388e05f8bb0c4b3e39bca8c612c6e9a7ec4c406005136eec86e5f1743a37c  tests/Support/SelectionSchemaAssertions.php
b409eddac3fa05caf1283c7c2dbf96f36f7611f4bd1a86cb8fd4bbf06b89d02c  tests/Support/IdentityRegistryTestDatabase.php
63759ba4e889c0a3940ea15f60e4f747f48ce26c39165fe9dc67984682fe5bb1  specs/ASSIGNMENT-ORDER-SELECTION-SCHEMA-001.md
ec2635711b019c61a631e9c9ab414d2a938552d0290d744f4258fa2fa20f20a7  evidence.json
17e79cc959cd57380d4494b2c10050bab616006a17d5cd29a78a522459b5d8be  red.log
```

Первый capture `selection-schema-data-red-c7hvtnfj` сохранён: manifest
8a84286edeaaa4b2d8511dbefc25f746ee6b55eb0ff7d58b0e0dc49e50bd57e7.
Он содержит genuine FK setup failure при DROP/ADD same named FK в одном ALTER.
Commit765de89 разделил fixture setup на две native команды без смены oracle.
Failed capture не выдаётся за intended RED и не удалён.
