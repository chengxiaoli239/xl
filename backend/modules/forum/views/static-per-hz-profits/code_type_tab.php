<?php

use yii\helpers\Html;
?>

<div class="btn-group">
    <?= Html::a('和值一', ['index', 'StaticPerHzProfits[lottery_type]'=>$lottery_type, 'StaticPerHzProfits[type]'=>1], ['class' => 'btn '.($type == 1 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('和值二', ['index', 'StaticPerHzProfits[lottery_type]'=>$lottery_type, 'StaticPerHzProfits[type]'=>2], ['class' => 'btn '.($type == 2 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
