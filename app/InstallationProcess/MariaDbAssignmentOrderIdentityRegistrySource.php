<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

final class MariaDbAssignmentOrderIdentityRegistrySource
{
    public static function capture(\mysqli $db,string $prefix,bool $lock=false): array
    {
        $raw=MariaDbAssignmentOrderIdentityRegistrySql::rows($db,
            "SELECT CAST(o.id AS CHAR) id,CAST(o.installation_case_id AS CHAR) case_id,CAST(o.version_no AS CHAR) version,o.prepared_at,CAST(c.id AS CHAR) actual_case FROM `{$prefix}fm2_assignment_orders` o LEFT JOIN `{$prefix}fm2_installation_cases` c ON c.id=o.installation_case_id ORDER BY o.id".($lock?' FOR UPDATE':''));
        $rows=[];$seen=[];
        foreach ($raw as $row) {
            $id=AssignmentOrderIdentityRegistryValues::decimal($row['id']);
            $case=AssignmentOrderIdentityRegistryValues::decimal($row['case_id']);
            $version=(int)AssignmentOrderIdentityRegistryValues::decimal($row['version'],false,'65535');
            if ($row['actual_case']!==$case || isset($seen[$case.':'.$version])) { throw new \DomainException('Conflicting source identity.'); }
            $seen[$case.':'.$version]=true;
            $rows[]=['id'=>$id,'case'=>$case,'version'=>$version,
                'utc'=>AssignmentOrderIdentityRegistryValues::instant($row['prepared_at']),'raw'=>$row['prepared_at']];
        }
        return ['rows'=>$rows,'next'=>MariaDbAssignmentOrderIdentityRegistryCatalog::nextId($db,$prefix.'fm2_assignment_orders')]
            + self::summary($rows);
    }

    public static function summary(array $rows): array
    {
        $tuple=hash_init('sha256');$prepared=hash_init('sha256');$max='0';
        foreach ($rows as $row) {
            hash_update($tuple,$row['id'].','.$row['case'].','.$row['version']."\n");
            hash_update($prepared,$row['id'].','.$row['utc']."\n");
            $max=$row['id'];
        }
        return ['max'=>$max,'count'=>(string)count($rows),'tuple'=>hash_final($tuple),'prepared'=>hash_final($prepared)];
    }
}
