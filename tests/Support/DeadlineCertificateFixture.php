<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
/** Role setup from the public catalogue with independent exact expected certificate defaults. */
final class DeadlineCertificateFixture
{
    public static function grants(\mysqli $db,string $prefix):void
    {
        $roles=\FMonitor2\IdentityAccess\LocalRoleCatalog::roles();
        foreach(['fkr_operator'=>['deadline_certificate.read','deadline_certificate.write'],'manager'=>['deadline_certificate.read','deadline_certificate.write'],'otiz_specialist'=>['deadline_certificate.read'],'construction_control_engineer'=>[],'access_administrator'=>[],'superadministrator'=>[]] as $code=>$expected){
            $actual=array_values(array_filter($roles[$code]['permissions'],static fn(string $p):bool=>str_starts_with($p,'deadline_certificate.')));sort($actual);\assertSameValue($expected,$actual,'RED_ASSERTION exact certificate defaults '.$code);
        }
        foreach([1=>'fkr_operator',6=>'otiz_specialist',7=>'manager'] as $id=>$code){foreach($roles[$code]['permissions'] as $permission)if(str_starts_with($permission,'deadline_certificate.')){$s=$db->prepare("INSERT IGNORE INTO {$prefix}fm2_pilot_role_permissions(role_id,permission) VALUES(?,?)");$s->execute([$id,$permission]);}}
    }
    public static function multipart(array $fields,string $pdf):array
    {
        $boundary='cert-fixture-'.bin2hex(random_bytes(8));$body='';foreach($fields as $key=>$value)$body.='--'.$boundary."\r\nContent-Disposition: form-data; name=\"".$key."\"\r\n\r\n".$value."\r\n";
        $body.='--'.$boundary."\r\nContent-Disposition: form-data; name=\"pdf\"; filename=\"certificate.pdf\"\r\nContent-Type: application/pdf\r\n\r\n".$pdf."\r\n--".$boundary."--\r\n";
        return [['Content-Type: multipart/form-data; boundary='.$boundary],$body];
    }
}
