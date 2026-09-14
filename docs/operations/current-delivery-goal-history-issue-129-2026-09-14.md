# Текущая цель — №129, согласование delivery-документации

[№76](https://github.com/Antropophag/fmonitor-2/issues/76) закрыта через
[PR #127](https://github.com/Antropophag/fmonitor-2/pull/127).
[№128](https://github.com/Antropophag/fmonitor-2/issues/128) также уже закрыта:
актуальный
[локальный Yii2 quickstart](../../README.md#локальный-yii2-quickstart) поставлен.
Ближайшая продуктовая работа — следующий продуктовый
тестовый сценарий; текущее ограниченное
[поручение №129](https://github.com/Antropophag/fmonitor-2/issues/129) согласует delivery-
инструкции и их прямых consumers без изменения поведения, classifier, merge
или deployment.

История прежнего указателя сохранена в
[current-delivery-goal-history-2026-09-14.md](current-delivery-goal-history-2026-09-14.md).
Фактические source, PR, CI, lane и required reviews получать через
`python3 tools/delivery/harness.py state` и активный role package. Отсутствующие
live adapters, server-side enforcement и deployment evidence остаются `UNKNOWN`;
они не означают approval и не разрешают autonomous admission.
