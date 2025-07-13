<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\User */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'admin_id') ?>
    <?= $form->field($model, 'username') ?>

    <?= $form->field($model, 'account') ?>

    <?= $form->field($model, 'balance') ?>

    <?php // echo $form->field($model, 'simulate_balance') ?>

    <?php // echo $form->field($model, 'email') ?>

    <?php // echo $form->field($model, 'tz_password') ?>

    <?php // echo $form->field($model, 'cookie') ?>

    <?php // echo $form->field($model, 'cookie2') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
