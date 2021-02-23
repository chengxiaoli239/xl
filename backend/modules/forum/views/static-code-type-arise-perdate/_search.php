<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\StaticCodeTypeArisePerdate */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static-code-type-arise-perdate-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'date') ?>

    <?= $form->field($model, 'type_2') ?>

    <?= $form->field($model, 'type_3') ?>

    <?= $form->field($model, 'type_22') ?>

    <?php // echo $form->field($model, 'type_2b') ?>

    <?php // echo $form->field($model, 'type_3b') ?>

    <?php // echo $form->field($model, 'type_4b') ?>

    <?php // echo $form->field($model, 'type_2_type_2b') ?>

    <?php // echo $form->field($model, 'type_2_type_3b') ?>

    <?php // echo $form->field($model, 'lottery_type') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
