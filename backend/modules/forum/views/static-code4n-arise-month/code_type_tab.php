<?php

use yii\helpers\Html;
?>

<div class="btn-group">
    <?= Html::a('不带双一', ['index', 'StaticCode4nAriseMonth[lottery_type]'=>$lottery_type, 'StaticCode4nAriseMonth[type]'=>1, 'StaticCode4nAriseMonth[type_2]'=>0], ['class' => 'btn '.($type_2 == 0 && $type == 1 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('不带双二', ['index', 'StaticCode4nAriseMonth[lottery_type]'=>$lottery_type, 'StaticCode4nAriseMonth[type]'=>2, 'StaticCode4nAriseMonth[type_2]'=>0], ['class' => 'btn '.($type_2 == 0 && $type == 2 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
||
<div class="btn-group">
    <?= Html::a('带双一', ['index', 'StaticCode4nAriseMonth[lottery_type]'=>$lottery_type, 'StaticCode4nAriseMonth[type]'=>1, 'StaticCode4nAriseMonth[type_2]'=>1], ['class' => 'btn '.($type_2 == 1 && $type == 1 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('带双二', ['index', 'StaticCode4nAriseMonth[lottery_type]'=>$lottery_type, 'StaticCode4nAriseMonth[type]'=>2, 'StaticCode4nAriseMonth[type_2]'=>1], ['class' => 'btn '.($type_2 == 1 && $type == 2 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
