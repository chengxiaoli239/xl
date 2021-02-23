<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\SscStaticYl */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ssc-static-yl-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'val') ?>

    <?= $form->field($model, 'current_miss') ?>

    <?= $form->field($model, 'last_time_miss') ?>

    <?= $form->field($model, 'last_time_miss_range') ?>

    <?php // echo $form->field($model, 'max_miss') ?>

    <?php // echo $form->field($model, 'max_range') ?>

    <?php // echo $form->field($model, 'yl_records') ?>

    <?php // echo $form->field($model, 'history_max_miss') ?>

    <?php // echo $form->field($model, 'count') ?>

    <?php // echo $form->field($model, 'static_nums') ?>

    <?php // echo $form->field($model, 'theory_nums_perdate') ?>

    <?php // echo $form->field($model, 'today_nums') ?>

    <?php // echo $form->field($model, 'lottery_type') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
