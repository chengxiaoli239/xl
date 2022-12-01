<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\QueueLog */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="queue-log-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'system_queue_id') ?>

    <?= $form->field($model, 'business_id') ?>

    <?= $form->field($model, 'params') ?>

    <?= $form->field($model, 'remark') ?>

    <?php // echo $form->field($model, 'count') ?>

    <?php // echo $form->field($model, 'name') ?>

    <?php // echo $form->field($model, 'job_class') ?>

    <?php // echo $form->field($model, 'job_class_md5') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'time') ?>

    <?php // echo $form->field($model, 'last_push_time') ?>

    <?php // echo $form->field($model, 'complete_time') ?>

    <?php // echo $form->field($model, 'delay') ?>

    <?php // echo $form->field($model, 'type') ?>

    <?php // echo $form->field($model, 'create_time') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
