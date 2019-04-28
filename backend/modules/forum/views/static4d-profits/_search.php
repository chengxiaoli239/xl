<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\Static4dProfits */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static4d-profits-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'month') ?>

    <?= $form->field($model, 'codes_4d_all') ?>

    <?= $form->field($model, 'codes_13_31') ?>

    <?= $form->field($model, 'codes_22_22') ?>

    <?php // echo $form->field($model, 'codes_1111_2222') ?>

    <?php // echo $form->field($model, 'codes_13') ?>

    <?php // echo $form->field($model, 'codes_31') ?>

    <?php // echo $form->field($model, 'codes_13_2222') ?>

    <?php // echo $form->field($model, 'codes_31_1111') ?>

    <?php // echo $form->field($model, 'codes_31_2222') ?>

    <?php // echo $form->field($model, 'codes_13_1111') ?>

    <?php // echo $form->field($model, 'codes_31_2222_1111') ?>

    <?php // echo $form->field($model, 'codes_13_1111_2222') ?>

    <?php // echo $form->field($model, 'codes_2222') ?>

    <?php // echo $form->field($model, 'codes_1111') ?>

    <?php // echo $form->field($model, 'codes_1_nums') ?>

    <?php // echo $form->field($model, 'codes_2_nums') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
