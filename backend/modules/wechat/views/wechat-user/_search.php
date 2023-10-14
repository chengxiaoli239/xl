<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\wechat\WechatUser */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="wechat-user-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'user_id') ?>

    <?= $form->field($model, 'userName') ?>

    <?= $form->field($model, 'nickName') ?>

    <?= $form->field($model, 'aliasName') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'balance') ?>

    <?php // echo $form->field($model, 'is_credit') ?>

    <?php // echo $form->field($model, 'bigHead') ?>

    <?php // echo $form->field($model, 'smallHead') ?>

    <?php // echo $form->field($model, 'labelList') ?>

    <?php // echo $form->field($model, 'remark') ?>

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
