<?php
declare(strict_types=1);
namespace FMonitor2\RuntimeRestore;
final class RuntimeRecoverySchemaV29
{
 public const VERSION=29;
 public const DEFERRED=RuntimeRecoverySchemaV28::DEFERRED;
 public static function tables(string$p):array{$x=[...RuntimeRecoverySchemaV28::tables($p),$p.'fm2_legacy_identity_links',$p.'fm2_legacy_identity_link_events',$p.'fm2_engineer_migration_operations'];sort($x,SORT_STRING);return$x;}
 public static function autoIncrement(string$p):array{$x=[...RuntimeRecoverySchemaV28::autoIncrement($p),$p.'fm2_legacy_identity_links',$p.'fm2_legacy_identity_link_events'];sort($x,SORT_STRING);return$x;}
}
