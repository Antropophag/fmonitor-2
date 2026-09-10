## Why

После поставки документарного закрытия в PR #91 самостоятельный справочник монтажников `/pilot/installers` остаётся на rapid-pilot HTTP composition. Сотруднику ФКР и диспетчеру нужен тот же read-only каталог через целевой Yii2 runtime, чтобы продолжить №76 без изменения кадровых или процессных правил.

## What Changes

- Перенести `GET` и `HEAD /pilot/installers` в Yii2 controller/view composition с сохранением поиска, фильтров, пагинации, кадровых статусов, текущих закреплений и навигации.
- Читать существующие workforce/process facts через Yii DB read model; запрос не выполняет DDL/DML и не создаёт второго владельца кадровых данных.
- Сохранить authorization, session/CSRF boundary, CSP/cache/HEAD/error contracts и responsive server-rendered UI.
- Включить новый Yii2 HTTP/browser маршрут в обязательный verification inventory и подтвердить отсутствие runtime-загрузки rapid-pilot для этого URL.
- Не добавлять в этом срезе карточку монтажника, новую политику конфликтов/устаревания, sync/import, кадровые команды, выбор состава или переключение рабочего стенда.

## Capabilities

### New Capabilities

- `runtime/yii2-installer-directory`: Yii2 runtime обслуживает существующий read-only справочник монтажников на публичном seam `/pilot/installers` без изменения наблюдаемого контракта.

### Modified Capabilities

Нет.

## Impact

Затрагиваются Yii URL rules, новый controller/read model/view, существующие Yii assets/navigation, HTTP/browser tests и verification inventory. Источником поведенческой истины остаются `MariaDbInstallerDirectoryReader`, `ProductionInstallerDirectoryRenderer`, `rapid-pilot/verify-installer-directory-pagination.php` и действующие pilot contracts; rapid-pilot остаётся oracle/адаптером до общего cutover.
