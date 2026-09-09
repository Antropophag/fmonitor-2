<?php
declare(strict_types=1);
namespace FMonitor2\Otiz;

/** Builds read-only selection plans; normative values are supplied by NativePremiumNorms. */
final readonly class MariaDbObjectRegisterQuery
{
    public function __construct(private \mysqli $db,private string $prefix,private string $legacyPrefix) {}

    private function literal(string $text): string
    { return "'".$this->db->real_escape_string($text)."'"; }

    public function search(array $query,string $registration,string $address): string
    {
        if($query['q']==='')return '';
        $needle=$this->literal(mb_strtolower($query['q'],'UTF-8'));
        return " WHERE LOCATE({$needle} COLLATE utf8mb4_bin,LOWER(CONCAT(COALESCE({$registration},''),' ',COALESCE({$address},''))) COLLATE utf8mb4_bin)>0";
    }

    public function where(array $query): string
    {
        $parts=[];
        if($query['q']!==''){
            // LOCATE gives literal substring matching, including %, _ and backslashes.
            $needle=$this->literal(mb_strtolower($query['q'],'UTF-8'));
            $parts[]="LOCATE({$needle} COLLATE utf8mb4_bin,LOWER(CONCAT(COALESCE(regnumber,''),' ',COALESCE(address,''))) COLLATE utf8mb4_bin)>0";
        }
        if($query['state']!=='')$parts[]='state='.$this->literal($query['state']);
        return $parts===[]?'':' WHERE '.implode(' AND ',$parts);
    }

    /** Equivalent to positive FILTER_VALIDATE_INT operands used by the established norm reader. */
    private function integer(string $json): string
    {
        $raw="JSON_UNQUOTE({$json})";
        $pattern=$this->literal('^[\\x09\\x0a\\x0b\\x0d\\x20]*[+]?[1-9][0-9]*[\\x09\\x0a\\x0b\\x0d\\x20]*$');
        return "CASE WHEN JSON_TYPE({$json})='STRING' AND {$raw} REGEXP {$pattern} THEN CAST({$raw} AS DECIMAL(30,0)) WHEN JSON_TYPE({$json}) IN('INTEGER','DOUBLE') AND CAST({$raw} AS DOUBLE)=FLOOR(CAST({$raw} AS DOUBLE)) THEN CAST({$raw} AS DOUBLE) ELSE NULL END";
    }

    public function base(string $rawPage = ''): string
    {
        $p=$this->prefix;$l=$this->legacyPrefix;$norms=new NativePremiumNorms();
        $bands=[];foreach($norms->premiumBands() as $r){
            $bands[]='SELECT '.$this->literal($r['type'])." lift_type,{$r['floors']} floors,{$r['from']} capacity_from,{$r['to']} capacity_to,{$r['premiumCents']} premium_cents";
        }
        $shafts=[];foreach($norms->shaftRules() as $name=>$amount)$shafts[]='SELECT '.$this->literal($name)." material,{$amount} shaft_bp";
        $bandSql=implode(' UNION ALL ',$bands);$shaftSql=implode(' UNION ALL ',$shafts);
        $floors=$this->integer('floor_json');$capacity=$this->integer('capacity_json');
        $trimPattern=$this->literal('^[\\x00\\x09\\x0a\\x0b\\x0d\\x20]+|[\\x00\\x09\\x0a\\x0b\\x0d\\x20]+$');
        $plusPattern=$this->literal('\\s*\\+\\s*');$spacePattern=$this->literal('\\s+');
        return "WITH
        premium_bands AS ({$bandSql}), shaft_rules AS ({$shaftSql}),
        latest AS (
            SELECT candidate.*,snapshot.report_date,
                ROW_NUMBER() OVER(PARTITION BY candidate.object_id ORDER BY snapshot.report_date DESC,snapshot.id DESC) position_no
            FROM `{$p}fm2_pilot_otiz_snapshot_objects` candidate
            JOIN `{$p}fm2_pilot_otiz_snapshots` snapshot ON snapshot.id=candidate.snapshot_id
        ), closures AS (
            SELECT object_id,SUM(paid_cents) paid_cents,SUM(discipline_cents) discipline_cents,SUM(deadline_cents) deadline_cents
            FROM `{$p}fm2_pilot_otiz_payment_closures` GROUP BY object_id
        ), snapshot_closures AS (
            SELECT snapshot_id,object_id,SUM(paid_cents+discipline_cents+deadline_cents) closed_cents
            FROM `{$p}fm2_pilot_otiz_payment_closures` GROUP BY snapshot_id,object_id
        ), raw_objects AS (
            SELECT l.id object_id,l.regnumber,l.ordadr_address address,d.captured_at,
                JSON_EXTRACT(IF(JSON_VALID(d.payload_json),d.payload_json,'{}'),'$.fields.floors.raw') floor_json,
                JSON_EXTRACT(IF(JSON_VALID(d.payload_json),d.payload_json,'{}'),'$.fields.weight.raw') capacity_json,
                JSON_VALUE(IF(JSON_VALID(d.payload_json),d.payload_json,'{}'),'$.fields.lift_type.display') type_text,
                JSON_VALUE(IF(JSON_VALID(d.payload_json),d.payload_json,'{}'),'$.fields.pitmaterial.display') material_text,
                so.snapshot_id,so.report_date,so.current_progress_bp,so.progress_fact_date,
                so.accrued_cents,so.pool_cents,so.kss_bp,so.calculation_state,
                JSON_TYPE(JSON_EXTRACT(IF(JSON_VALID(so.inputs_json),so.inputs_json,'{}'),'$.premiumCalculation')) IN('OBJECT','ARRAY') has_calculation,
                (SELECT jt.amount FROM JSON_TABLE(IF(JSON_VALID(so.inputs_json),so.inputs_json,'{}'),
                    '$.premiumCalculation.formulaTrace[*]' COLUMNS(ord FOR ORDINALITY,step VARCHAR(100) PATH '$.step',amount BIGINT PATH '$.resultCents')) jt
                    WHERE BINARY jt.step=BINARY 'progress' ORDER BY jt.ord DESC LIMIT 1) trace_progress_cents,
                COALESCE(c.paid_cents,0) paid_cents,COALESCE(c.discipline_cents,0) discipline_cents,
                COALESCE(c.deadline_cents,0) deadline_cents,COALESCE(sc.closed_cents,0) snapshot_closed_cents
            FROM `{$l}fm_maintable` l
            LEFT JOIN `{$p}fm2_pilot_object_details` d ON d.object_id=l.id
            LEFT JOIN latest so ON so.object_id=l.id AND so.position_no=1
            LEFT JOIN closures c ON c.object_id=l.id
            LEFT JOIN snapshot_closures sc ON sc.object_id=l.id AND sc.snapshot_id=so.snapshot_id
            {$rawPage}
        ), operands AS (
            SELECT object_id,regnumber,address,captured_at,snapshot_id,report_date,current_progress_bp,progress_fact_date,
                accrued_cents,pool_cents,kss_bp,calculation_state,has_calculation,trace_progress_cents,
                paid_cents,discipline_cents,deadline_cents,snapshot_closed_cents,{$floors} floors,{$capacity} capacity,
                CASE WHEN LOCATE('груз',LOWER(COALESCE(type_text,'')))>0 THEN 'cargo' ELSE 'passenger' END lift_type,
                CAST(REGEXP_REPLACE(REGEXP_REPLACE(LOWER(REGEXP_REPLACE(COALESCE(material_text,''),{$trimPattern},'')),{$plusPattern},' + '),{$spacePattern},' ') AS CHAR(160)) material
            FROM raw_objects
            /* Materialize operands once: otherwise MariaDB repeats JSON/regexp parsing
               for every norm band. This unsigned maximum does not truncate rows. */
            LIMIT 18446744073709551615
        ), economics AS (
            SELECT o.*,n.premium_cents,s.shaft_bp,
                CASE WHEN n.premium_cents IS NULL OR s.shaft_bp IS NULL THEN 'missing_norm'
                    WHEN o.snapshot_id IS NULL THEN 'planned'
                    WHEN o.calculation_state='ready' AND o.pool_cents>0 AND o.snapshot_closed_cents>=o.pool_cents THEN 'completed'
                    ELSE o.calculation_state END state
            FROM operands o
            LEFT JOIN premium_bands n ON n.lift_type=o.lift_type AND n.floors=o.floors AND o.capacity BETWEEN n.capacity_from AND n.capacity_to
            LEFT JOIN shaft_rules s ON BINARY s.material=BINARY o.material
        )";
    }
}
