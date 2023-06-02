<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
?>
<!--大小、单双模板-->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color:green">
    <div class="col-lg-1 col-xs-12">
        <!--?= $form->field($model, 'ps_sel')->checkboxList([1=>'除',2=>'取'])->label('配数全转') ?-->
        <?= $form->field($model, 'fixed_pos_sel')->checkboxList(
            [1=>'除', 2=>'取'],
            [
                'item' => function ($index, $label, $name, $checked, $value) {
                    $options = [
                        #'class' => 'ps_sel',
                        'class' => 'checkbox-item',
                        'label' => $label,
                        'value' => $value,
                        'checked' => $checked,
                    ];

                    return Html::checkbox($name, $checked, $options);
                }
            ]
        )->label('定位置') ?>
    </div>
    <div class="col-lg-2 col-xs-6">
        <?= $form->field($model, 'p1')->textInput()->label('千
            <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_1">大</a>
            <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_2">小</a>
            <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_1">单</a>
            <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_2">双</a>
        ') ?>
    </div>
    <div class="col-lg-2 col-xs-6">
        <?= $form->field($model, 'p2')->textInput()->label('百
            <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_1">大</a>
            <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_2">小</a>
            <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_1">单</a>
            <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_2">双</a>
        ') ?>
    </div>
    <div class="col-lg-2 col-xs-6">
        <?= $form->field($model, 'p3')->textInput()->label('十
            <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_1">大</a>
            <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_2">小</a>
            <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_1">单</a>
            <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_2">双</a>
        ') ?>
    </div>
    <div class="col-lg-2 col-xs-6">
        <?= $form->field($model, 'p4')->textInput()->label('个
            <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_1">大</a>
            <a href="javascript:;" class="btn btn-xs btn-info code_type_dx_2">小</a>
            <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_1">单</a>
            <a href="javascript:;" class="btn btn-xs btn-info code_type_ds_2">双</a>
        ') ?>
    </div>
</div>