<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\statics\StaticPositionTypeArisePerdate */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static-position-type-arise-perdate-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'date') ?>

    <?= $form->field($model, 'type') ?>

    <?= $form->field($model, 'p1') ?>

    <?= $form->field($model, 'p2') ?>

    <?php // echo $form->field($model, 'p3') ?>

    <?php // echo $form->field($model, 'p4') ?>

    <?php // echo $form->field($model, 'p5') ?>

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
