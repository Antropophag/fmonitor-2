## 1. Контракт и evidence

- [x] 1.1 Проверить inventory и ADR с независимой проверкой карты входов.
- [x] 1.2 Зафиксировать YII2-RUNTIME-001 и intended RED реальных HTTP/CLI проверок.
- [x] 1.3 Получить независимый Gate3 APPROVED на спецификацию и тесты.

## 2. Runtime

- [x] 2.1 Подключить Yii2 через общий Composer lock; проверить clean install и pinned TCPDF.
- [ ] 2.2 Реализовать общую configuration и Yii web/console health lifecycle; focused test GREEN.
- [x] 2.3 Собрать isolated PHP8.4 FPM/nginx contour; HTTP smoke и безопасная readiness failure GREEN.
- [ ] 2.4 Подключить тест к verification inventory и dependency bootstrap; focused architecture/regression GREEN.

## 3. Поставка

- [ ] 3.1 Получить независимый Gate5 на точный source и evidence.
- [ ] 3.2 Один полный exact-source CI, PR и merge после GREEN.
- [ ] 3.3 Записать фактические результаты и следующий real read-route slice; этап2 #76 остаётся открытым до HTML/assets/auth parity.

Done: все пункты GREEN, reviewers независимы, текущий стенд сохранён. Archive только
по фактической готовности этого operational slice, не всего #76.
