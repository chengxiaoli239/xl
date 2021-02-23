<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\LotteryType */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="lottery-type-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'lottery_type') ?>

    <?= $form->field($model, 'enable') ?>

    <?= $form->field($model, 'isDelete') ?>

    <?= $form->field($model, 'sort') ?>

    <?php // echo $form->field($model, 'name') ?>

    <?php // echo $form->field($model, 'codeList') ?>

    <?php // echo $form->field($model, 'title') ?>

    <?php // echo $form->field($model, 'shortName') ?>

    <?php // echo $form->field($model, 'info') ?>

    <?php // echo $form->field($model, 'onGetNoed') ?>

    <?php // echo $form->field($model, 'data_ftime') ?>

    <?php // echo $form->field($model, 'defaultViewGroup') ?>

    <?php // echo $form->field($model, 'android') ?>

    <?php // echo $form->field($model, 'num') ?>

    <?php // echo $form->field($model, 'typeGroupName') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
