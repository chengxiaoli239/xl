<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\wechat\Bets */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="bets-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'user_id') ?>

    <?= $form->field($model, 'wechat_user_id') ?>

    <?= $form->field($model, 'order_id') ?>

    <?= $form->field($model, 'play_method') ?>

    <?php // echo $form->field($model, 'codes') ?>

    <?php // echo $form->field($model, 'bet_money') ?>

    <?php // echo $form->field($model, 'bonus') ?>

    <?php // echo $form->field($model, 'single') ?>

    <?php // echo $form->field($model, 'ratio') ?>

    <?php // echo $form->field($model, 'profits') ?>

    <?php // echo $form->field($model, 'qihao') ?>

    <?php // echo $form->field($model, 'kj_codes') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'cancel_status') ?>

    <?php // echo $form->field($model, 'is_simulate') ?>

    <?php // echo $form->field($model, 'lottery_name') ?>

    <?php // echo $form->field($model, 'lottery_type') ?>

    <?php // echo $form->field($model, 'is_profits_record') ?>

    <?php // echo $form->field($model, 'bet_desc') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <?php // echo $form->field($model, 'update_at') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
