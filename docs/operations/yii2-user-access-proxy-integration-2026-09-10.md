# Объединённый кандидат Yii user access + proxy privacy — #76

## Исходники и независимость

User access: `83049f45`, review `reviews/code/YII2-USER-ACCESS-001.md` APPROVED.
Proxy privacy: `6e7d9313` из отдельной ветки, перенесён как `97ab6cbe`, review
`reviews/code/ACTIVATION-PROXY-LOG-001.md` APPROVED. Оба автора реализации — sol/low,
root написал спецификации и тесты. #82 поставлена ранее PR84 и остаётся закрытой.

Cherry-pick затронул конфликты только current goal и общих inventory-файлов.
Сохранены обе группы additions и прежний historical digest; production-код каждого
среза совпадает с независимо проверенным. Общий plan пересчитан для двух changes.

## Интеграционная проверка

- Inventory15/15 GREEN: `/tmp/76-combined-inventory.log`.
- Actual architecture7/7 + HTTP qualification: `/tmp/76-combined-architecture.log`.
- CI policy сначала выявила единственный старый exact E2E list:
  `/tmp/76-combined-ci-policy.log`. Root добавил два новых имени в тот же expected
  list, не удаляя прежние и не ослабляя проверку. Новый15/15 GREEN:
  `/tmp/76-combined-ci-policy-green.log`.
- Независимый от реализации expected list чувствителен к ошибочной категории:
  в isolated checkout privacy-test временно перенесён E2E→integration с согласованной
  регистрацией; публичный CLI остаётся валидным, но новый oracle падает именно на
  отсутствии E2E member. RED `/tmp/76-combined-ci-policy-intended-red.log`;
  после восстановления metadata — GREEN `/tmp/76-combined-ci-policy-restored-green.log`.

Последняя test-delta отправляется на независимую проверку вместе с итогом слияния.
После неё — один authoritative full CI объединённого exact candidate. Ни full CI,
ни production switch пока не объявлены завершёнными. Далее #76: очередь объектов,
процессные маршруты, остальные ОТиЗ, console/jobs и финальный cutover/retirement.

## Интеграционный review

Независимый reviewer `/root/review76_proxy` подтвердил Gates3/5 интеграционной
delta без findings: union registries, tightened E2E oracle, RED/GREEN sensitivity,
неизменные production hashes обоих срезов и реальные architecture/inventory checks.
Dispositions дописаны в существующие reviews/tests и reviews/code
`ACTIVATION-PROXY-LOG-001.md`; новый микросрез не создавался.
Теперь готов один окончательный checkpoint для authoritative CI.
