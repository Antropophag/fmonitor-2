## Context

См. `proposal.md`. Все известные production user routes уже имеют Yii2
controllers, но `public/runtime.php` самостоятельно обрабатывает health/host и
для не-OTIZ запросов включает `rapid-pilot/router.php`. `public/yii.php` остаётся
параллельным front controller, а deployment nginx направляет запросы в
`runtime.php`.

## Goals / Non-Goals

**Goals:** один production bootstrap и error boundary, полный route/assets
inventory, доказуемое отсутствие legacy runtime reachability, сохранение
accepted Yii2 behavior и простой image rollback.

**Non-Goals:** удаление behavioral oracle файлов, изменение domain/persistence
owners, cookies, schema, console/jobs, stand deployment или общий data restore
rehearsal.

## Decisions

1. `public/runtime.php` остаётся стабильным deployment filename, но становится
   тонким Yii2 front controller. Альтернатива с изменением nginx на `yii.php`
   создаёт лишний operational change и второй источник bootstrap semantics.
2. Trusted host и safe startup error выполняются до/вокруг Yii application run,
   а routing, method handling, session и response security принадлежат Yii.
   Дублировать dispatch в front controller запрещено.
3. `/` получает явное Yii route/action. Все production assets должны быть в
   `PilotAssetController` inventory; отсутствующий asset сначала переносится как
   read-only adapter из public exports, без включения rapid-pilot PHP.
4. Persistence owners и application seams остаются существующими `app/*`
   модулями. Runtime composition вправе их связывать, но не читать SQL и не
   создавать facts.
5. `rapid-pilot` сохраняется как oracle для parity tests, но architecture check
   запрещает require/include и runtime class reachability из `public/`, Yii
   config/controllers и production Compose.

## Risks / Trade-offs

- [Inventory пропускает редкий route/asset] → сравнить извлечённые legacy/Yii
  inventories и проверить representative routes каждого owner плюс unknown/method.
- [Bootstrap error меняет публичный формат] → отдельные invalid config/host/DB/
  session fault cases через реальный `runtime.php`.
- [HEAD или CSP drift] → включить headers/body matrix и browser smoke для
  динамических страниц, downloads, CSS/JS/font/icon assets.
- [Слишком широкий regression run] → локально только generated focused/fast
  checks; полный matrix один раз выполняет exact-source CI.

## Migration Plan

1. Зафиксировать route/assets inventory, normative spec и intended RED на
   production entrypoint, затем получить независимый Gate 3.
2. Перевести bootstrap и недостающие root/assets routes, получить focused GREEN
   и независимый Gate 5 exact-source.
3. Создать отдельный PR, выполнить один full Quality Graph CI и merge.
4. Deployment не входит в срез. Rollback PR — вернуть предыдущий image; schema и
   данные не изменялись.
