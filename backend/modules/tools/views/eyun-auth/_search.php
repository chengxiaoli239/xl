<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\EyunAuth */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="eyun-auth-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'type') ?>

    <?= $form->field($model, 'account') ?>

    <?= $form->field($model, 'password') ?>

    <?= $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'authorization') ?>

    <?php // echo $form->field($model, 'callback_url') ?>

    <?php // echo $form->field($model, 'base_url') ?>

    <?php // echo $form->field($model, 'desc') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_at') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
