<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final readonly class MariaDbBitrixOrderDocumentLinks
{
    public function __construct(private \mysqli$db,private string$prefix=''){MariaDbSchemaInspector::validateTablePrefix($prefix);}
    public static function for(\mysqli$db,string$prefix):self{return new self($db,$prefix);}
    public function replaceCurrent(array$rows):void
    {
        $table=$this->prefix.'fm2_bitrix_order_document_links';$this->db->begin_transaction();
        try{$this->db->query("DELETE FROM `{$table}`");$insert=$this->db->prepare("INSERT INTO `{$table}`(source_folder_id,source_folder_name,order_number,url) VALUES(?,?,?,?)");foreach($rows as[$id,$name,$order,$url]){$insert->bind_param('isss',$id,$name,$order,$url);$insert->execute();}$this->db->commit();}
        catch(\Throwable$error){try{$this->db->rollback();}catch(\Throwable){}throw$error;}
    }
}
