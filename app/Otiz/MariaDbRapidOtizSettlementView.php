<?php
declare(strict_types=1);
namespace FMonitor2\Otiz;
/** Temporary mysqli read adapter for the retained rapid presentation. */
final class MariaDbRapidOtizSettlementView
{
 public function __construct(private \mysqli$db,private string$prefix){if(preg_match('/^[A-Za-z0-9_]{0,25}$/D',$prefix)!==1)throw new \InvalidArgumentException();}
 public function closedForObject(int$id):int{$s=$this->db->prepare("SELECT COALESCE(SUM(paid_cents+discipline_cents+deadline_cents),0) n FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE object_id=?");$s->bind_param('i',$id);$s->execute();return(int)$s->get_result()->fetch_assoc()['n'];}
 public function closedForSnapshotObjects(int$id):int{$s=$this->db->prepare("SELECT COALESCE(SUM(paid_cents+discipline_cents+deadline_cents),0) n FROM `{$this->prefix}fm2_pilot_otiz_payment_closures` WHERE object_id IN(SELECT object_id FROM `{$this->prefix}fm2_pilot_otiz_snapshot_objects` WHERE snapshot_id=?)");$s->bind_param('i',$id);$s->execute();return(int)$s->get_result()->fetch_assoc()['n'];}
}
