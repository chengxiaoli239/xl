<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\wechat\RobotUser */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="robot-user-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'user_id') ?>

    <?= $form->field($model, 'wcId') ?>

    <?= $form->field($model, 'wId') ?>

    <?= $form->field($model, 'uuid') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'wechat_status') ?>

    <?php // echo $form->field($model, 'desc') ?>

    <?php // echo $form->field($model, 'expire_time') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_at') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
