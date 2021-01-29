<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\StaticPeiShuCodeDateProfits */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static-pei-shu-code-date-profits-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'date') ?>

    <?= $form->field($model, 'code_147_369') ?>

    <?= $form->field($model, 'code_258_369') ?>

    <?= $form->field($model, 'code_019_368') ?>

    <?php // echo $form->field($model, 'code_123_678') ?>

    <?php // echo $form->field($model, 'code_147_258') ?>

    <?php // echo $form->field($model, 'code_017_348') ?>

    <?php // echo $form->field($model, 'code_456_789') ?>

    <?php // echo $form->field($model, 'code_012_789') ?>

    <?php // echo $form->field($model, 'code_345_678') ?>

    <?php // echo $form->field($model, 'code_357_019') ?>

    <?php // echo $form->field($model, 'code_3b') ?>

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
