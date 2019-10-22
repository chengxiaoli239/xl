<?php

use yii\helpers\Html;
?>

<div class="btn-group">
    <?= Html::a('不带双一', ['index', 'StaticCode3nAriseMonth[lottery_type]'=>$lottery_type, 'StaticCode3nAriseMonth[type]'=>1], ['class' => 'btn '.($type == 1 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('不带双二', ['index', 'StaticCode3nAriseMonth[lottery_type]'=>$lottery_type, 'StaticCode3nAriseMonth[type]'=>2], ['class' => 'btn '.($type == 2 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('不带双三', ['index', 'StaticCode3nAriseMonth[lottery_type]'=>$lottery_type, 'StaticCode3nAriseMonth[type]'=>3], ['class' => 'btn '.($type == 3 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('不带双四', ['index', 'StaticCode3nAriseMonth[lottery_type]'=>$lottery_type, 'StaticCode3nAriseMonth[type]'=>4], ['class' => 'btn '.($type == 4 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
||
<div class="btn-group">
    <?= Html::a('带双一', ['index', 'StaticCode3nAriseMonth[lottery_type]'=>$lottery_type, 'StaticCode3nAriseMonth[type]'=>5], ['class' => 'btn '.($type == 5 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('带双二', ['index', 'StaticCode3nAriseMonth[lottery_type]'=>$lottery_type, 'StaticCode3nAriseMonth[type]'=>6], ['class' => 'btn '.($type == 6 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('带双三', ['index', 'StaticCode3nAriseMonth[lottery_type]'=>$lottery_type, 'StaticCode3nAriseMonth[type]'=>7], ['class' => 'btn '.($type == 7 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
||
<div class="btn-group">
    <?= Html::a('三重', ['index', 'StaticCode3nAriseMonth[lottery_type]'=>$lottery_type, 'StaticCode3nAriseMonth[type]'=>8], ['class' => 'btn '.($type == 9 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
