<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

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