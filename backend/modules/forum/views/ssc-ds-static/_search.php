<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\SscDsStatic */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ssc-ds-static-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'positions') ?>

    <?= $form->field($model, 'qihao') ?>

    <?= $form->field($model, 'periods') ?>

    <?= $form->field($model, 'DS') ?>

    <?php // echo $form->field($model, 'SD') ?>

    <?php // echo $form->field($model, 'DD') ?>

    <?php // echo $form->field($model, 'SS') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
