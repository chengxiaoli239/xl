<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\wechat\Bets */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="bets-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="row">
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'wechat_user_id') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'order_id') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'play_method') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'codes') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'bet_money') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'bonus') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'single') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'ratio') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'profits') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'qihao') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'kj_codes') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'status') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'cancel_status') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'is_simulate') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'lottery_name') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'lottery_type') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'is_profits_record') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'bet_desc') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'created_at') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'updated_at') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'update_at') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <label> </label>
            <div class="form-group">
                <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
                <?= Html::resetButton('重置', ['class' => 'btn btn-default']) ?>

            </div>
        </div>

    <?php ActiveForm::end(); ?>

</div>
