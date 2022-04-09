<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\LotteryDataDealStatus */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="lottery-data-deal-status-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'lottery_type') ?>

    <?= $form->field($model, 'status') ?>

    <?= $form->field($model, 'status_desc') ?>

    <?= $form->field($model, 'static4dPerDateProfits_status') ?>

    <?php // echo $form->field($model, 'static4dPerDateProfits_status_desc') ?>

    <?php // echo $form->field($model, 'updateDs_status') ?>

    <?php // echo $form->field($model, 'updateDs_status_desc') ?>

    <?php // echo $form->field($model, 'updateDsYL_status') ?>

    <?php // echo $form->field($model, 'updateDsYL_status_desc') ?>

    <?php // echo $form->field($model, 'update3NumYL_status') ?>

    <?php // echo $form->field($model, 'update3NumYL_status_desc') ?>

    <?php // echo $form->field($model, 'updateSdHzYL_status') ?>

    <?php // echo $form->field($model, 'updateSdHzYL_status_desc') ?>

    <?php // echo $form->field($model, 'opProfitsPlans_status') ?>

    <?php // echo $form->field($model, 'opProfitsPlans_status_desc') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
