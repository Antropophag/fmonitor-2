<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

/** Exact read-only legacy ERP query ownership for the bounded equipment facts adapter. */
final class MariaDbErpEquipmentFactsSource
{
    public static function ordersQuery():string{return "/* BI_Sпроф_СрокиХраненияГотовойПродукцииID */ SELECT z.номер AS sourceOrderNumber, MAX(s.датакомплектности) AS readinessDate, MAX(s.датаполнойотгрузки) AS fullShipmentDate FROM BI_DзаказклиентаID z JOIN BI_Sпроф_срокихраненияготовойпродукцииID s ON s.заказклиентаID=z.ID GROUP BY z.номер";}
    public static function shipmentsQuery():string{return "SELECT z.номер AS sourceOrderNumber, MIN(e.датаотгрузки) AS shipmentDate FROM BI_DзаказклиентаID z JOIN BI_Dэтаппроизводства2_2ID e ON e.заказклиентаID=z.ID WHERE e.пометкаудаления=0 AND e.наименование='лифтовоеоборудование' GROUP BY z.номер";}
}
