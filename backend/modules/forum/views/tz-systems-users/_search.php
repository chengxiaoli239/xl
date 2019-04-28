<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\TzSystemsUsers */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="tz-systems-users-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'uid') ?>

    <?= $form->field($model, 'tz_system_id') ?>

    <?= $form->field($model, 'sys_name') ?>

    <?= $form->field($model, 'account') ?>

    <?php // echo $form->field($model, 'password') ?>

    <?php // echo $form->field($model, 'balance') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'ssc_domain') ?>

    <?php // echo $form->field($model, 'cookie') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
