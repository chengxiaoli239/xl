<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\BettingRecords */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="betting-records-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'codes') ?>

    <?= $form->field($model, 'account') ?>

    <?= $form->field($model, 'playway') ?>

    <?= $form->field($model, 'playway_name') ?>

    <?php // echo $form->field($model, 'betting_money') ?>

    <?php // echo $form->field($model, 'bonus') ?>

    <?php // echo $form->field($model, 'single') ?>

    <?php // echo $form->field($model, 'profits') ?>

    <?php // echo $form->field($model, 'qihao') ?>

    <?php // echo $form->field($model, 'kj_codes') ?>

    <?php // echo $form->field($model, 'position') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'sn') ?>

    <?php // echo $form->field($model, 'snid') ?>

    <?php // echo $form->field($model, 'is_simulate') ?>

    <?php // echo $form->field($model, 'lotteryclass') ?>

    <?php // echo $form->field($model, 'createtime') ?>

    <?php // echo $form->field($model, 'create_time') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
