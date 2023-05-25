<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
?>
<div class="row" style="border-width:1px;margin-top:3px;border-style:solid;border-color: green;">
    <div class="col-lg-1 col-xs-12">
        <!--两数合、三数合-->
        <?= $form->field($model, 'fixed_pos_hefen_sel')->checkboxList(
            [1=>'除',2=>'取'],
            [
                'item' => function ($index, $label, $name, $checked, $value) {
                    $options = [
                        'class' => 'checkbox-item',
                        'label' => $label,
                        'value' => $value,
                        'checked' => $checked,
                    ];

                    return Html::checkbox($name, $checked, $options);
                }
            ]
        )->label('2.定位合分') ?>
    </div>
    <div class="col-lg-1 col-xs-4">
        <!--位置合分：位置-->
        <?= $form->field($model, 'hefen_pos1')->checkboxList($hefen_pos)->label('1.1位置') ?>
    </div>
    <div class="col-lg-1 col-xs-6">
        <!--位置合分：合分-->
        <?= $form->field($model, 'hefen1')->textInput()->label('1.1合分值')?>
    </div>
    <div class="col-lg-1 col-xs-4">
        <!--位置合分：位置-->
        <?= $form->field($model, 'hefen_pos2')->checkboxList($hefen_pos)->label('1.2位置') ?>
    </div>
    <div class="col-lg-1 col-xs-6">
        <!--位置合分：合分-->
        <?= $form->field($model, 'hefen2')->textInput()->label('1.2合分值')?>
    </div>
    <div class="col-lg-1 col-xs-4">
        <!--位置合分：位置-->
        <?= $form->field($model, 'hefen_pos3')->checkboxList($hefen_pos)->label('1.3位置') ?>
    </div>
    <div class="col-lg-1 col-xs-6">
        <!--位置合分：合分-->
        <?= $form->field($model, 'hefen3')->textInput()->label('1.3合分值')?>
    </div>
    <div class="col-lg-1 col-xs-4">
        <!--位置合分：位置-->
        <?= $form->field($model, 'hefen_pos4')->checkboxList($hefen_pos)->label('1.4位置') ?>
    </div>
    <div class="col-lg-1 col-xs-6">
        <!--位置合分：合分-->
        <?= $form->field($model, 'hefen4')->textInput()->label('1.4合分值')?>
    </div>
</div>
