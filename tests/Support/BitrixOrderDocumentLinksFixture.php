<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/Yii2/UserAccessFixture.php';

function documentLinksFixture(bool $production=false):UserAccessFixture
{
    $fixture=new UserAccessFixture(dirname(__DIR__,2));$prefix=$fixture->p;
    if($production){
        assertSameValue(true,class_exists(FMonitor2\InstallationProcess\BitrixOrderDocumentLinksSchemaMigration::class),'INTENDED_RED schema owner missing');
        FMonitor2\InstallationProcess\BitrixOrderDocumentLinksSchemaMigration::apply($fixture->db,$prefix);
        return $fixture;
    }
    if(!$fixture->db->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm_maintable' AND COLUMN_NAME='zavnumber'")->fetch_row()){
        $fixture->db->query("ALTER TABLE {$prefix}fm_maintable ADD zavnumber VARCHAR(120) NULL COLLATE utf8mb4_bin");
    }
    $fixture->db->query("CREATE TABLE IF NOT EXISTS {$prefix}fm2_bitrix_order_document_links(source_folder_id BIGINT UNSIGNED NOT NULL,source_folder_name VARCHAR(255) NOT NULL,order_number VARCHAR(120) COLLATE utf8mb4_bin NOT NULL,url VARCHAR(2048) NOT NULL,UNIQUE KEY uq_folder_order(source_folder_id,order_number),KEY ix_order(order_number))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
    return $fixture;
}
function documentLink(string $id,string $name,string $order,string $url):array{return['sourceFolderId'=>$id,'sourceFolderName'=>$name,'orderNumber'=>$order,'url'=>$url];}
