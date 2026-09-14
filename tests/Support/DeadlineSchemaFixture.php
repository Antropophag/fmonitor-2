<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
final class DeadlineSchemaFixture
{
    public \mysqli $admin;
    public array $databases=[];
    public string $host;
    public int $port;
    public string $user;
    public string $password;
    public function __construct()
    {
        $this->host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$this->port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);
        $this->user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$this->password=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';
        $this->admin=new \mysqli($this->host,$this->user,$this->password,'',$this->port);
    }
    public function database():\mysqli
    {
        $name='t_cert25_'.bin2hex(random_bytes(6));$this->admin->query("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$this->databases[]=$name;
        $db=new \mysqli($this->host,$this->user,$this->password,$name,$this->port);$db->set_charset('utf8mb4');return$db;
    }
    public static function rows(\mysqli $db,array $tables):array
    {
        $out=[];foreach($tables as $table){$rows=$db->query("SELECT * FROM `$table`")->fetch_all(MYSQLI_ASSOC);usort($rows,static fn($a,$b)=>strcmp(serialize($a),serialize($b)));$out[$table]=$rows;}ksort($out);return$out;
    }
    public static function tables(\mysqli $db):array{return array_column($db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM),0);}
    public static function counters(\mysqli $db):array{return$db->query('SELECT TABLE_NAME,AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND AUTO_INCREMENT IS NOT NULL ORDER BY BINARY TABLE_NAME')->fetch_all(MYSQLI_ASSOC);}
    public static function closeTree(string $path):void
    {
        if(is_file($path)||is_link($path)){unlink($path);return;}if(!is_dir($path))return;foreach(scandir($path)as$entry)if($entry!=='.'&&$entry!=='..')self::closeTree($path.'/'.$entry);rmdir($path);
    }
    public function close():void{foreach($this->databases as $name)$this->admin->query("DROP DATABASE IF EXISTS `$name`");$this->admin->close();}
}
