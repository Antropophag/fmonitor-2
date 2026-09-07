<?php

declare(strict_types=1);

namespace FMonitor2\InspectionEvidence;

final class MariaDbInspectionTemplateDirectory
{
    public function __construct(private readonly \mysqli $db, private readonly string $prefix)
    {
    }

    public function template(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $statement = $this->db->prepare(
            'SELECT snapshot_version,content_sha256,payload_json FROM '
            .$this->table('fm2_checklist_template_snapshots').' WHERE id=?'
        );
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        if ($row === null) {
            return null;
        }

        $payload = json_decode($row['payload_json'], true, 512, JSON_THROW_ON_ERROR);
        $items = isset($payload['sections'])
            ? self::sectionItems((array) $payload['sections'])
            : self::normalizedLegacyItems($payload);

        return [
            'version' => (string) $row['snapshot_version'],
            'sha256' => (string) $row['content_sha256'],
            'items' => $items,
        ];
    }

    private static function sectionItems(array $sections):array
    {
        $items=[];
        foreach($sections as$section){
            if(!is_array($section))continue;
            $sectionId=(int)($section['id']??0);if($sectionId<1)continue;
            $items[$sectionId]=array_values(array_map(
                static fn(array$item):int=>(int)($item['id']??0),
                array_filter((array)($section['items']??[]),'is_array'),
            ));
        }
        return$items;
    }

    private static function normalizedLegacyItems(array $payload):array
    {
        $parts=array_values(array_filter((array)($payload['parts']??[]),'is_array'));
        usort($parts,self::ordered(...));
        $partOrdinals=[];$items=[];
        foreach($parts as$index=>$part){$id=(string)($part['id']??'');if($id==='')continue;$ordinal=$index+1;$partOrdinals[$id]=$ordinal;$items[$ordinal]=[];}
        $definitions=array_values(array_filter((array)($payload['definitions']??[]),'is_array'));
        usort($definitions,self::ordered(...));
        foreach($definitions as$definition){$part=(string)($definition['part_id']??'');$item=(int)($definition['id']??0);if($item<1||!isset($partOrdinals[$part]))continue;$items[$partOrdinals[$part]][]=$item;}
        return$items;
    }

    private static function ordered(array $left,array $right):int
    {
        return[(int)($left['rang']??0),(int)($left['id']??0)]<=>[(int)($right['rang']??0),(int)($right['id']??0)];
    }

    private function table(string $name): string
    {
        return '`'.$this->prefix.$name.'`';
    }
}
