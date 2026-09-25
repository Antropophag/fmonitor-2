<?php
declare(strict_types=1);
use yii\helpers\Html;
$labels=['queued'=>'Ожидает запуска','running'=>'Выполняется','retry'=>'Ожидает повторной попытки','completed'=>'Завершено успешно','dead'=>'Завершено с ошибкой','unknown'=>'Состояние выполнения неизвестно'];
$outcomes=['completed'=>'Успешно','retry_scheduled'=>'Повторная попытка','dead'=>'Ошибка','expired'=>'Срок аренды истёк'];
$attemptOutcomes=['completed'=>'Завершено успешно','retry_scheduled'=>'Ожидает повторной попытки','dead'=>'Завершено с ошибкой','expired'=>'Состояние выполнения неизвестно'];
?>
<style>
.fm2-docs{margin:2rem 0 3rem}.fm2-docs__summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;margin:1.25rem 0 2rem}.fm2-docs__card{padding:1.25rem;border:1px solid var(--shlz-color-border,#d9dee7);border-radius:.75rem}.fm2-docs__card h3{margin-top:0}.fm2-docs__card p{margin-bottom:.35rem}.fm2-docs nav{display:flex;align-items:center;gap:1rem;margin-top:1rem}@media(max-width:560px){.fm2-docs__summary{grid-template-columns:1fr}.fm2-docs{margin-top:1.5rem}}
</style>
<section class="fm2-docs" data-integration="bitrix-documents" aria-labelledby="documents-summary">
 <h2 id="documents-summary">Техническая документация Битрикс</h2>
 <?php if(!$documents['available']):?><p class="shlz-alert shlz-alert--danger">Сведения недоступны</p><?php else:?>
 <div class="fm2-docs__summary">
  <article class="fm2-docs__card"><h3>Состояние очереди</h3><?php if($documents['queue']===null):?><p>Нет зарегистрированных заданий</p><?php else:$q=$documents['queue'];?><p><span class="shlz-status"><?=Html::encode($labels[$q['status']]??$labels['unknown'])?></span></p><p><?=Html::encode($q['failure_code']??($q['status']==='dead'?'Причина не зарегистрирована':''))?></p><?php endif?></article>
  <article class="fm2-docs__card"><span>Последняя попытка</span><h3>Последняя фактически начавшаяся попытка</h3><?php if($documents['attempt']===null):?><p>Нет зарегистрированных запусков</p><?php else:$a=$documents['attempt'];$outcome=$a['outcome']??'';?><p>#<?=(int)$a['job_id']?> · Попытка <?=(int)$a['attempt']?></p><p><?=Html::encode($date((string)$a['started_at']))?></p><p><?=Html::encode($attemptOutcomes[$outcome]??'Результат не зарегистрирован')?></p><p><?=Html::encode($a['failure_code']??(($outcome==='dead'||$outcome==='retry_scheduled')?'Причина не зарегистрирована':''))?></p><?php endif?></article>
  <article class="fm2-docs__card"><h3>Последний подтверждённый успех</h3><?php if($documents['success']===null):?><p>Нет подтверждённых успешных запусков</p><?php else:$s=$documents['success'];?><p><?=Html::encode($date((string)$s['finished_at']))?></p><p>Опубликовано связей «заказ — папка»: <?=(int)$s['published']?></p><?php endif?></article>
 </div>
 <h3 id="documents-history">История запусков</h3>
 <?php if($documents['rows']===[]):?><div class="shlz-empty-state shlz-empty-state--simple"><div class="shlz-empty-state__content"><p>Нет зарегистрированных запусков</p></div></div><?php endif?><div class="shlz-table-wrap" data-mobile-strategy="contained-scroll" tabindex="0"><table class="shlz-table" aria-labelledby="documents-history"><thead class="shlz-table__head"><tr class="shlz-table__row"><th class="shlz-table__cell">Задание</th><th class="shlz-table__cell">Попытка</th><th class="shlz-table__cell">Начало</th><th class="shlz-table__cell">Результат</th><th class="shlz-table__cell">Причина</th></tr></thead><tbody><?php foreach($documents['rows']as$r):$outcome=$r['outcome']??'';?><tr class="shlz-table__row"><td class="shlz-table__cell">#<?=(int)$r['job_id']?></td><td class="shlz-table__cell">Попытка <?=(int)$r['attempt']?></td><td class="shlz-table__cell"><?=Html::encode($date((string)$r['started_at']))?></td><td class="shlz-table__cell"><?=Html::encode($outcomes[$outcome]??'Результат не зарегистрирован')?></td><td class="shlz-table__cell"><?=Html::encode($r['failure_code']??(($outcome==='dead'||$outcome==='retry_scheduled')?'Причина не зарегистрирована':'—'))?></td></tr><?php endforeach?></tbody></table></div><?=$pager('documentsPage',$documents,$pages,$pageSize)?>
 <?php endif?>
</section>
