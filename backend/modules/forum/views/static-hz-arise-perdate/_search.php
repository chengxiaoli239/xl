<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\StaticHzArisePerdate */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static-hz-arise-perdate-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'date') ?>

    <?= $form->field($model, 'hz_0_6') ?>

    <?= $form->field($model, 'hz_5_10') ?>

    <?= $form->field($model, 'hz_11_15') ?>

    <?php // echo $form->field($model, 'hz_16_19') ?>

    <?php // echo $form->field($model, 'hz_20_24') ?>

    <?php // echo $form->field($model, 'hz_25_29') ?>

    <?php // echo $form->field($model, 'hz_30_36') ?>

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
