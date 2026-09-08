<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

final class AssignmentOrderOriginalSafeLogAttributePolicy
{
    public static function accepts(int $mode,int $uid,int $device,int $inode,int $effectiveUid,int $expectedDevice,int $expectedInode): bool
    {
        return ($mode & 0170000)===0100000 && ($mode & 07777)===0600
            && $uid===$effectiveUid && $device===$expectedDevice && $inode===$expectedInode;
    }
}
