<?php
declare(strict_types=1);
use FMonitor2\YiiRuntime\Assets\AssetVersion;
use FMonitor2\YiiRuntime\ViewSupport;
/** @var int $snapshotId */
/** @var object $identity */
$this->registerJsFile('/pilot/assets/'.AssetVersion::file('otiz.js'),['type'=>'module','position'=>\yii\web\View::POS_END]);
ViewSupport::begin($this, 'Расчёт устарел', $identity, 'otiz', 'wide');
?>
<main class="fm2-otiz fm2-otiz-page">
 <section class="fm2-otiz-surface" role="alert" data-otiz-confirmed-outcome="payment">
  <h1>Расчёт #<?=$snapshotId?> устарел</h1>
  <p>Этот расчёт устарел; подготовьте новый расчёт перед регистрацией выплаты.</p>
  <p><a class="shlz-button shlz-button--secondary" href="/pilot/otiz/snapshots/<?=$snapshotId?>">Вернуться к исходному расчёту</a> <a class="shlz-button shlz-button--primary" href="/pilot/otiz/payments">Новый расчёт</a></p>
 </section>
</main>
<?php ViewSupport::end($this); ?>
