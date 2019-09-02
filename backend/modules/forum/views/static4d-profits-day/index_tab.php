<?php

use yii\helpers\Html;

if(count($lottery_types)>1)
foreach ($lottery_types as $lottery_type) {
    $class = $lottery['lottery_type'] == $lottery_type ? 'btn-success' : 'btn-default';
?>
<div class="btn-group">
    <?= Html::a($lottery_type['name'], ['index', 'Static4dProfitsDay[lottery_type]' => $lottery_type['lottery_type']], ['class' => 'btn '.$class, 'style' => 'margin-bottom:15px;']) ?>
</div>
<?php
}
?>

