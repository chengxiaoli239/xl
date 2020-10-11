<?php

use yii\helpers\Html;
?>

<div class="btn-group">
    <?= Html::a('号码类型', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>1, 'SscStaticYl[type]'=>2], ['class' => 'btn '.($code_type == 1 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
||
<div class="btn-group">
    <?= Html::a('三现带双', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>2, 'SscStaticYl[type]'=>3, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[type_3]'=>0], ['class' => 'btn '.($code_type == 2 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('三现带双热', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>3, 'SscStaticYl[type]'=>3, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[type_3]'=>0, 'SscStaticYl[is_hots]'=>1], ['class' => 'btn '.($code_type == 3 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('三重', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>4, 'SscStaticYl[type]'=>3, 'SscStaticYl[type_3]'=>1], ['class' => 'btn '.($code_type == 4 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
||
<div class="btn-group">
    <?= Html::a('四现带双三字', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>502, 'SscStaticYl[type]'=>5, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[is_hots]'=>1], ['class' => 'btn '.($code_type == 502 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('四现带双', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>5, 'SscStaticYl[type_22]'=>0, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_2]'=>1,'SscStaticYl[type_22]'=>0, 'SscStaticYl[type_3]'=>0], ['class' => 'btn '.($code_type == 5 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('四现带双热', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>501, 'SscStaticYl[type_22]'=>0, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[type_3]'=>0, 'SscStaticYl[is_hots]'=>1], ['class' => 'btn '.($code_type == 501 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
||
<div class="btn-group">
    <?= Html::a('四现不带双', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>6, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_2]'=>0], ['class' => 'btn '.($code_type == 6 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('四现不带双热', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>601, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_2]'=>0, 'SscStaticYl[is_hots]'=>1], ['class' => 'btn '.($code_type == 601 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
||

<div class="btn-group">
    <?= Html::a('四兄', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>7, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_4b]'=>1], ['class' => 'btn '.($code_type == 7 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('四单双', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>8, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_2]'=>0, 'SscStaticYl[type_4ds]'=>1], ['class' => 'btn '.($code_type == 8 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
||

<div class="btn-group">
    <?= Html::a('四单带双', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>9, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_4d]'=>1,'SscStaticYl[type_22]'=>0, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[type_3]'=>0], ['class' => 'btn '.($code_type == 9 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('四单带双热', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>901, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_22]'=>0,'SscStaticYl[type_4d]'=>1, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[type_3]'=>0, 'SscStaticYl[is_hots]'=>1], ['class' => 'btn '.($code_type == 901 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>

||
<div class="btn-group">
    <?= Html::a('四双带双', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>10, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_4s]'=>1, 'SscStaticYl[type_22]'=>0, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[type_3]'=>0], ['class' => 'btn '.($code_type == 10 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('四双带双热', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>1001, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_4s]'=>1,'SscStaticYl[type_22]'=>0, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[type_3]'=>0, 'SscStaticYl[is_hots]'=>1], ['class' => 'btn '.($code_type == 1001 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>

||
<div class="btn-group">
    <?= Html::a('双双重', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>1002, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_22]'=>1, 'SscStaticYl[type_3]'=>0, 'SscStaticYl[is_hots]'=>1], ['class' => 'btn '.($code_type == 1002 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>
<div class="btn-group">
    <?= Html::a('全四现', ['index', 'SscStaticYl[lottery_type]'=>$lottery_type, 'SscStaticYl[code_type]'=>1003, 'SscStaticYl[type]'=>4], ['class' => 'btn '.($code_type == 1003 ? 'btn-success' : 'btn-default'), 'style' => 'margin-bottom:15px;']) ?>
</div>

