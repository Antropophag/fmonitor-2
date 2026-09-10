<?php
declare(strict_types=1);
namespace FMonitor2\InspectionEvidence;

trait MariaDbYiiChecklistPersistence
{
        private function one(string$sql,array$p=[]):?array{
    if($this->yii instanceof \yii\db\Connection){$row=$this->yii->createCommand($sql,$this->yiiParams($p))->queryOne();return$row===false?null:$row;}
    $s=$this->db->prepare($sql);$s->execute($p);return$s->get_result()->fetch_assoc()?:null;
    }

        private function all(string$sql,array$p=[]):array{
    if($this->yii instanceof \yii\db\Connection)return$this->yii->createCommand($sql,$this->yiiParams($p))->queryAll();
    $s=$this->db->prepare($sql);$s->execute($p);return$s->get_result()->fetch_all(MYSQLI_ASSOC);
    }

        private function execute(string$sql,array$p=[]):int{
    if($this->yii instanceof \yii\db\Connection)return$this->yii->createCommand($sql,$this->yiiParams($p))->execute();
    $s=$this->db->prepare($sql);$s->execute($p);return$s->affected_rows;
    }

        private function begin():void{if($this->yii instanceof \yii\db\Connection){$this->transaction=$this->yii->beginTransaction();return;}$this->db->begin_transaction();}
        private function commit():void{if($this->transaction!==null){$this->transaction->commit();$this->transaction=null;return;}$this->db->commit();}
        private function rollback():void{if($this->transaction!==null){$this->transaction->rollBack();$this->transaction=null;return;}if($this->db!==null)$this->db->rollback();}
        private function yiiParams(array$p):array{return$p===[]?[]:array_combine(range(1,count($p)),array_values($p));}

        private function t(string$n):string{return'`'.$this->prefix.$n.'`';
    }

        private function tLegacy(string$n):string{return'`'.$this->legacyPrefix.$n.'`';
    }

        private function uuid(string$v):bool{return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$v)===1;
    }

        private function instant(string$v):bool{try{return$v!==''&&strlen($v)<=40&&(new \DateTimeImmutable($v))->format('c')!=='';
    } catch(\Throwable)
        {return false;
    }}
}
