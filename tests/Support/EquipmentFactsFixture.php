<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/bootstrap.php';

use FMonitor2\InstallationProcess\EquipmentFactsSchemaMigration;

final class EquipmentFactsFixture
{
    public mysqli $admin;
    public mysqli $db;
    public string $name;
    public string $p;
    public const HMAC_KEY='test-only-equipment-facts-hmac-key-32-bytes';

    public function __construct(string $tag)
    {
        $host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);
        $user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$password=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';
        $this->name='t_erp_facts_'.$tag.'_'.bin2hex(random_bytes(4));$this->p='ef_';
        $this->admin=new mysqli($host,$user,$password,'',$port);$this->admin->query("CREATE DATABASE `{$this->name}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->db=new mysqli($host,$user,$password,$this->name,$port);$this->db->set_charset('utf8mb4');
        $this->db->query("CREATE TABLE {$this->p}fm_maintable(id BIGINT UNSIGNED NOT NULL PRIMARY KEY,zavnumber VARCHAR(120) COLLATE utf8mb4_bin NULL,process_marker VARCHAR(64) NOT NULL DEFAULT 'unchanged')ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
    }
    public function migrate(): void { EquipmentFactsSchemaMigration::apply($this->db,$this->p); }
    public function object(int$id,?string$order):void{$s=$this->db->prepare("INSERT INTO {$this->p}fm_maintable(id,zavnumber)VALUES(?,?)");$s->bind_param('is',$id,$order);$s->execute();}
    public function rows(string$table):array{return$this->db->query("SELECT * FROM {$this->p}{$table} ORDER BY 1")->fetch_all(MYSQLI_ASSOC);}
    public function close():void{$this->db->close();$this->admin->query("DROP DATABASE IF EXISTS `{$this->name}`");$this->admin->close();}
}

function equipmentCommand(string$run,string$at,array$records):array{return['actor'=>['type'=>'system','id'=>'erp-equipment-facts-hourly-v1'],'kind'=>'complete','runId'=>$run,'observedAtUtc'=>$at,'records'=>$records];}
function equipmentRecord(string$order,?string$ready,?string$first,?string$full):array{return['sourceOrderNumber'=>$order,'readinessDate'=>$ready,'firstShipmentDate'=>$first,'fullShipmentDate'=>$full];}
