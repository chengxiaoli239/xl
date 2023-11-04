<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\AgentUsersBalanceFlows */
/* @var $form yii\widgets\ActiveForm */
?>
<div class="agent-users-balance-flows-search">
    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="row">
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'type')->dropDownList(
                [1=>'上分', 2=>'下分'], // Provide the options here
                ['prompt' => '-请选择-'] // Optional: Add a prompt message
            )->label('类型')?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'member_account') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'member_id') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'status')->dropDownList(
                [0=>'待审核', 1=>'已审核', 2=>'已拒绝'], // Provide the options here
                ['prompt' => '-请选择-'] // Optional: Add a prompt message
            )->label('状态')?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <label> </label>
            <div class="form-group">
                <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
                <?= Html::resetButton('重置', ['class' => 'btn btn-default']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
