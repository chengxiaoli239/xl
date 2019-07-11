<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\Static4dProfitsDay */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static4d-profits-day-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'date') ?>

    <?= $form->field($model, 'codes_1112') ?>

    <?= $form->field($model, 'codes_1121') ?>

    <?= $form->field($model, 'codes_1211') ?>

    <?php // echo $form->field($model, 'codes_2111') ?>

    <?php // echo $form->field($model, 'codes_1222') ?>

    <?php // echo $form->field($model, 'codes_2122') ?>

    <?php // echo $form->field($model, 'codes_2212') ?>

    <?php // echo $form->field($model, 'codes_2221') ?>

    <?php // echo $form->field($model, 'codes_1122') ?>

    <?php // echo $form->field($model, 'codes_1212') ?>

    <?php // echo $form->field($model, 'codes_1221') ?>

    <?php // echo $form->field($model, 'codes_2112') ?>

    <?php // echo $form->field($model, 'codes_2121') ?>

    <?php // echo $form->field($model, 'codes_2211') ?>

    <?php // echo $form->field($model, 'codes_1111') ?>

    <?php // echo $form->field($model, 'codes_2222') ?>

    <?php // echo $form->field($model, 'lottery_type') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
