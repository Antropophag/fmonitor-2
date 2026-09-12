## 1. Scope и Gate 2

- [x] 1.1 Подготовить verification input и обязательный Quality Graph plan; проверить отсутствие UNKNOWN/missing obligations командой planner check
- [x] 1.2 Добавить минимальный публичный Yii2 HTTP regression test и сохранить intended RED: строки с `pto_act` видны до реализации
- [x] 1.3 Получить независимый Gate 3 APPROVED для спецификации, теста, RED и verification plan

## 2. Реализация

- [x] 2.1 Применить единый server-side предикат отсутствия `pto_act` к COUNT и SELECT read owner; focused тест становится GREEN
- [x] 2.2 Выполнить обязательные focused/fast проверки из plan, включая HTTP qualification и architecture checks, без локального full suite

## 3. Review и Done

- [x] 3.1 Получить независимый Gate 5 APPROVED на полный exact-source diff и evidence
- [ ] 3.2 Подготовить publication candidate через harness, запустить один exact-source полный CI и честно зафиксировать PR/CI/deployment state
