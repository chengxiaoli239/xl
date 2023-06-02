<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\AgentUserBetLogs */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="agent-user-bet-logs-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'access_token') ?>

    <?= $form->field($model, 'member_id') ?>

    <?= $form->field($model, 'account') ?>

    <?= $form->field($model, 'bet_logs') ?>

    <?php // echo $form->field($model, 'bet_logs_codes_hz') ?>

    <?php // echo $form->field($model, 'bet_codes') ?>

    <?php // echo $form->field($model, 'bet_codes_op') ?>

    <?php // echo $form->field($model, 'bet_type') ?>

    <?php // echo $form->field($model, 'playway') ?>

    <?php // echo $form->field($model, 'desc') ?>

    <?php // echo $form->field($model, 'lottery_type') ?>

    <?php // echo $form->field($model, 'qihao') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'tz_system_id') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_time') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
