# Durable private verification evidence archive

Дата: 2026-09-05. Автор: `/root`.

Для сохранения raw logs после очистки `/tmp` создан отдельный private archive
вне repository:
`/Users/antropophag/.local/state/fmonitor2-verification/autonomous-20260905-inpcqplw`.
Directory mode0700, files0600; создан новый уникальный namespace, существующие
артефакты не заменялись. Manifest содержит exact SHA-256 шести immutable copies.
Исходные `/tmp` files не удалялись и previous evidence records не переписаны.

```text
2dcb1386e7c4cc39e38c6f718e283e4b7ea0f76385af9070b5269d22f59b8f80  fmonitor2-verify-d1a5d09.log
f5ab9e710c1b881d6f6369999c22d52199cc2064a41b8a85ef261f40c73ad623  fmonitor2-verify-d1a5d09-prepared.log
f79330443ead1c099480c33747f9c6b988b70a3b651743e6a9c269abd3b4cbfe  fmonitor2-otiz-v12-red.log
7a53287c00e72ff05d27c40766d1aa69cb717ff325a6c446a0ccedca454e8424  fmonitor2-otiz-v12-green.log
f843522a21bc26547d0d1c273eb67b7b5e3b9f3cef67afa9f50adbd745f06f0b  fmonitor2-v12-consumer-green.log
9b53d950598dba65d0e9bb8c8e4e831c49c23c45f30de4364a55bf7447c4ee83  fmonitor2-v12-consumer-green-v2.log
```

Два diagnostic full runs не дали VERIFY_OK; один focused batch имел две
поправленные fixture ошибки, следующий — 11/11 amended-input PASS и retained
bootstrap/E2E failure. Archive сохраняет failures наравне с GREEN evidence.
Отдельный detached verification checkout удалён после clean tracked-status
проверки; его единственным ignored artifact был созданный нами vendor TCPDF.
Основной checkout остался единственным git worktree, test MariaDB healthy.
