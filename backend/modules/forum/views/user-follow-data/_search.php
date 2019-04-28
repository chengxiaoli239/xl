<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\UserFollowData */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-follow-data-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <!--?= $form->field($model, 'id') ?-->

    <!--?= $form->field($model, 'account') ?-->

    <?= $form->field($model, 'code') ?>

    <?= $form->field($model, 'codes_hezhi') ?>

    <?= $form->field($model, 'playway') ?>

    <?php // echo $form->field($model, 'position') ?>

    <?php // echo $form->field($model, 'reference_codes') ?>

    <?php // echo $form->field($model, 'is_follow') ?>

    <?php // echo $form->field($model, 'is_simulate') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'single') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
