<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\BetErrorPlansTask */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="bet-error-plans-task-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'codes') ?>

    <?= $form->field($model, 'uid') ?>

    <?= $form->field($model, 'agent_id') ?>

    <?= $form->field($model, 'account') ?>

    <?php // echo $form->field($model, 'bet_url') ?>

    <?php // echo $form->field($model, 'bet_headers') ?>

    <?php // echo $form->field($model, 'post_datas') ?>

    <?php // echo $form->field($model, 'playway') ?>

    <?php // echo $form->field($model, 'tz_type') ?>

    <?php // echo $form->field($model, 'playway_name') ?>

    <?php // echo $form->field($model, 'bet_money') ?>

    <?php // echo $form->field($model, 'single') ?>

    <?php // echo $form->field($model, 'qihao') ?>

    <?php // echo $form->field($model, 'kj_codes') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'sn') ?>

    <?php // echo $form->field($model, 'snid') ?>

    <?php // echo $form->field($model, 'plan_id') ?>

    <?php // echo $form->field($model, 'tz_system_id') ?>

    <?php // echo $form->field($model, 'lotteryclass') ?>

    <?php // echo $form->field($model, 'lottery_type') ?>

    <?php // echo $form->field($model, 'post_desc') ?>

    <?php // echo $form->field($model, 'error_desc') ?>

    <?php // echo $form->field($model, 'updated_time') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
