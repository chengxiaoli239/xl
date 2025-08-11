<?php

use yii\helpers\Html;

if(count($lottery_types)>1)
foreach ($lottery_types as $lottery) {
    $class = $lottery['lottery_type'] == $lottery_type ? 'btn-success' : 'btn-default';
?>
<div class="btn-group">
    <?= Html::a($lottery['name'], ['index', 'BetErrorPlansTask[lottery_type]' => $lottery['lottery_type']], ['class' => 'btn '.$class, 'style' => 'margin-bottom:15px;']) ?>
</div>
<?php
}
?>

