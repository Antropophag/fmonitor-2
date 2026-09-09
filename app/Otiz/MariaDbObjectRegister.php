<?php
declare(strict_types=1);
namespace FMonitor2\Otiz;

use FMonitor2\IdentityAccess\MariaDbPilotAccessPolicy;
use FMonitor2\InspectionEvidence\MariaDbObjectCurrentProgress;

/** SQL selection and a bounded-memory financial fold over one consistent read. */
final readonly class MariaDbObjectRegister
{
    public function __construct(private \mysqli $db,private string $prefix,private string $legacyPrefix)
    {
        foreach([$prefix,$legacyPrefix] as $p)if(preg_match('/^[A-Za-z0-9_]*$/D',$p)!==1)throw new \InvalidArgumentException('Invalid register prefix');
    }

    public function read(int $actorId,array $input): array
    {
        if(!MariaDbPilotAccessPolicy::grants(MariaDbPilotAccessPolicy::forUser($this->db,$this->prefix,$actorId),MariaDbPilotAccessPolicy::OTIZ_MANAGE))throw new \DomainException('REGISTER_FORBIDDEN');
        $query=ObjectRegister::query($input);
        $this->db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->db->begin_transaction(MYSQLI_TRANS_START_READ_ONLY|MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);
        try{
            $sql=new MariaDbObjectRegisterQuery($this->db,$this->prefix,$this->legacyPrefix);
            $base=$sql->base();$where=$sql->where($query);
            $total=$query['state']===''
                ?(int)$this->db->query('SELECT COUNT(*) FROM `'.$this->legacyPrefix.'fm_maintable` l'.$sql->search($query,'l.regnumber','l.ordadr_address'))->fetch_column()
                :null;
            $page=$query['page'];$pageSize=$query['pageSize'];
            if($total!==null&&$page>max(1,(int)ceil($total/$pageSize)))throw new \DomainException('REGISTER_PAGE_NOT_FOUND');
            $order=match($query['sort']){
                'regnumber_asc'=>'regnumber ASC,object_id ASC',
                'regnumber_desc'=>'regnumber DESC,object_id ASC',
                default=>"snapshot_id IS NULL,FIELD(calculation_state,'blocked','ready','no_new_amount','completed'),regnumber,object_id",
            };
            $rawOrder=strtr($order,['snapshot_id'=>'so.snapshot_id','calculation_state'=>'so.calculation_state','regnumber'=>'l.regnumber','object_id'=>'l.id']);
            $offset=($page-1)*$pageSize;
            // Without a derived-state filter, select the raw page before matching norms.
            // The global summary below still reads the complete authorized register.
            $pageBase=$query['state']===''
                ?$sql->base($sql->search($query,'l.regnumber','l.ordadr_address')." ORDER BY {$rawOrder} LIMIT {$pageSize} OFFSET {$offset}"):$base;
            $pageWhere=$query['state']===''?'':$where;
            $pageOffset=$query['state']===''?0:$offset;
            $pageOrder=strtr($order,['snapshot_id'=>'page.snapshot_id','calculation_state'=>'page.calculation_state','regnumber'=>'page.regnumber','object_id'=>'page.object_id']);
            $countColumn=$query['state']===''?'':',COUNT(*) OVER() filtered_total';
            $p=$this->prefix;
            $rows=$this->db->query($pageBase.' SELECT page.*,d.payload_json,so.inputs_json FROM (SELECT *'.$countColumn.' FROM economics'.$pageWhere.
                " ORDER BY {$order} LIMIT {$pageSize} OFFSET {$pageOffset}) page
                LEFT JOIN `{$p}fm2_pilot_object_details` d ON d.object_id=page.object_id
                LEFT JOIN `{$p}fm2_pilot_otiz_snapshot_objects` so ON so.object_id=page.object_id AND so.snapshot_id=page.snapshot_id
                ORDER BY {$pageOrder}")->fetch_all(MYSQLI_ASSOC);
            if($total===null)$total=$rows!==[]?(int)$rows[0]['filtered_total']
                :(int)$this->db->query($base.' SELECT COUNT(*) FROM economics'.$where)->fetch_column();
            $pages=max(1,(int)ceil($total/$pageSize));
            if($page>$pages)throw new \DomainException('REGISTER_PAGE_NOT_FOUND');
            $live=(new MariaDbObjectCurrentProgress($this->db,$this->prefix))->read(array_map('intval',array_column($rows,'object_id')));
            foreach($rows as &$row){
                unset($row['filtered_total']);
                $row=ObjectEconomy::decorate($row);$progress=$live[(int)$row['object_id']]??null;
                $row['display_progress_bp']=$progress['progressBp']??$row['current_progress_bp'];
                $row['display_progress_date']=$progress['factDate']??$row['progress_fact_date'];
            }unset($row);
            $summary=ObjectEconomy::emptySummary();$norms=new NativePremiumNorms();
            // No details/trace blobs, object arrays, or current-progress hydration in the global fold.
            $result=$this->db->query($base.' SELECT floor_json,capacity_json,type_text,material_text,has_calculation,trace_progress_cents,accrued_cents,paid_cents,discipline_cents,deadline_cents,snapshot_id,calculation_state,pool_cents,snapshot_closed_cents FROM raw_objects',MYSQLI_USE_RESULT);
            try{while($row=$result->fetch_assoc())ObjectEconomy::accumulate($summary,ObjectEconomy::decorate(ObjectEconomy::norms($row,$norms)));}finally{$result->free();}
            $this->db->commit();
            return compact('query','page','pageSize','pages','total','rows','summary');
        }catch(\Throwable $error){$this->db->rollback();throw $error;}
    }

}
