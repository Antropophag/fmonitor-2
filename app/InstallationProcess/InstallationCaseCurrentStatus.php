<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class InstallationCaseCurrentStatus
{
    /** @return array{opened:bool,label:string} */
    public static function project(array$row):array
    {
        $tuple=[$row['actual_start_date']??null,$row['opened_at']??null,$row['opened_by_user_id']??null];
        $present=count(array_filter($tuple,static fn(mixed$value):bool=>$value!==null));
        $opened=$present===3;$empty=$present===0;
        $selected=($row['selection_order_id']??null)!==null;
        $applied=($row['application_id']??null)!==null;
        $order=$row['order_status']??null;
        $ready=($row['original_revision_id']??null)!==null||(!$selected&&($applied||$order==='registered'));
        $label=match($row['process_state']??null){
            'needs_assignment_order'=>$empty&&$ready?'Готов к открытию'
                :($empty&&($selected||($order===null&&!$applied))?'Требуется распоряжение':null),
            'assignment_order_prepared'=>$empty&&$ready?'Готов к открытию'
                :($empty&&($selected||($order==='prepared'&&!$applied))?'Требуется распоряжение':null),
            'working'=>$opened&&($applied||in_array($order,['prepared','registered'],true))?'В работе':null,
            'needs_assignment_change'=>$opened&&($applied||in_array($order,['prepared','registered'],true))?'Требуется изменение':null,
            default=>null,
        };
        if(!in_array($present,[0,3],true)||$label===null)throw new \RuntimeException('Malformed installation case status.');
        return compact('opened','label');
    }
}
