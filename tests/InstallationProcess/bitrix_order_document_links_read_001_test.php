<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/BitrixOrderDocumentLinksFixture.php';

use FMonitor2\InstallationProcess\MariaDbBitrixOrderDocumentLinksRead;

$fixture=null;
try{
    $fixture=documentLinksFixture();$prefix=$fixture->p;
    $fixture->db->query("INSERT INTO {$prefix}fm2_bitrix_order_document_links VALUES(1,'One','4507.2','https://tenant.bitrix24.test/1'),(2,'Two','4507.20','https://tenant.bitrix24.test/2')");
    assertSameValue(true,class_exists(MariaDbBitrixOrderDocumentLinksRead::class),'INTENDED_RED read owner missing');
    $read=new MariaDbBitrixOrderDocumentLinksRead($fixture->db,$prefix);
    assertSameValue(['status'=>'available','links'=>[['name'=>'One','url'=>'https://tenant.bitrix24.test/1']]],$read->forOrder('4507.2'),'binary exact read');
    assertSameValue(['status'=>'empty','links'=>[]],$read->forOrder('4507.02'),'no fuzzy match');
    assertSameValue(['status'=>'order_number_missing','links'=>[]],$read->forOrder(null),'null order has missing state');
    assertSameValue(['status'=>'order_number_missing','links'=>[]],$read->forOrder(''),'empty order has missing state');
    echo"PASS: BITRIX-ORDER-DOCUMENT-LINKS-001 exact read\n";
}finally{if($fixture instanceof UserAccessFixture)$fixture->close();}
