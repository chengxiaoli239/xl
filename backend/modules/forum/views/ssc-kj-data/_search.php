<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\SscKjData */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ssc-kj-data-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'kj_code') ?>

    <?= $form->field($model, 'code_str') ?>

    <?= $form->field($model, 'code1') ?>

    <?= $form->field($model, 'code2') ?>

    <?php // echo $form->field($model, 'code3') ?>

    <?php // echo $form->field($model, 'code4') ?>

    <?php // echo $form->field($model, 'code5') ?>

    <?php // echo $form->field($model, 'code_1_2') ?>

    <?php // echo $form->field($model, 'code_1_3') ?>

    <?php // echo $form->field($model, 'code_1_4') ?>

    <?php // echo $form->field($model, 'code_2_3') ?>

    <?php // echo $form->field($model, 'code_2_4') ?>

    <?php // echo $form->field($model, 'code_3_4') ?>

    <?php // echo $form->field($model, 'qihao') ?>

    <?php // echo $form->field($model, 'date') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
