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

    <div class="row">
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'id') ?>
        </div>

        <?php //$form->field($model, 'user_id') ?>

        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'userName') ?>
        </div>

        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'nickName') ?>
        </div>

        <?php //$form->field($model, 'aliasName') ?>

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

        <div class="col-lg-2 col-xs-3">
            <label>  </label>
            <div class="form-group">
                <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
                <?= Html::resetButton('重置', ['class' => 'btn btn-default']) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>

</div>
