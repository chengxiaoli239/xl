<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
$user = \Yii::$app->user;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
?>
<!--大小、单双模板-->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color:green">
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'arise')->textInput()->label('上奖') ?>
    </div>
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'exclude_codes')->textInput()->label('排除') ?>
    </div>
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'is_init_perdate')->checkboxList(
            [1=>'否',2=>'是'],
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
        )->label('每天初始化(翻倍计划)') ?>
    </div>

    <?php if($user->identity->is_can_op_bet == \backend\models\UserSysPlans::BET_DIRECT_F){ ?>
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'bet_direct')->checkboxList(
            \backend\models\UserSysPlans::BET_DIRECT_OPTION,
            [
                //'value' => [1],
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
        )->label('切换下方向') ?>
    </div>
    <?}?>

    <?php if($model->playway==1){ ?>
    <div class="col-lg-2 col-xs-6">
        <!--二定含除、取-->
        <?= $form->field($model, 'arise_in_sel')->checkboxList([1=>'除',2=>'取'])->label('2.二字定含') ?>
    </div>
    <div class="col-lg-2 col-xs-6">
        <?= $form->field($model, 'arise_in')->textInput()->label('2.二字定含')?>
    </div>
    <?}?>
</div>