<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\AgentUsers */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="agent-users-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'desc') ?>

    <?= $form->field($model, 'images') ?>

    <?= $form->field($model, 'balance') ?>

    <?php // echo $form->field($model, 'is_tuo') ?>

    <?php // echo $form->field($model, 'is_chi') ?>

    <?php // echo $form->field($model, 'is_cha') ?>

    <?php // echo $form->field($model, 'all_bet_money') ?>

    <?php // echo $form->field($model, 'is_bind') ?>

    <?php // echo $form->field($model, 'bet_url') ?>

    <?php // echo $form->field($model, 'token') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
