<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\StaticHzProfitsPerdate */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static-hz-profits-perdate-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'date') ?>

    <?= $form->field($model, 'hz_0_4') ?>

    <?= $form->field($model, 'hz_5_10') ?>

    <?= $form->field($model, 'hz_11_15') ?>

    <?php // echo $form->field($model, 'hz_16_19') ?>

    <?php // echo $form->field($model, 'hz_20_24') ?>

    <?php // echo $form->field($model, 'hz_25_29') ?>

    <?php // echo $form->field($model, 'hz_30_35') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
