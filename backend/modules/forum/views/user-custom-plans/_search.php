<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\UserCustomPlans */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-custom-plans-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'account') ?>

    <?= $form->field($model, 'hezhis') ?>

    <?= $form->field($model, 'playway') ?>

    <?= $form->field($model, 'positions') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'single') ?>

    <?php // echo $form->field($model, 'threshold_open') ?>

    <?php // echo $form->field($model, 'threshold_close') ?>

    <?php // echo $form->field($model, 'periods') ?>

    <?php // echo $form->field($model, 'is_simulate') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
