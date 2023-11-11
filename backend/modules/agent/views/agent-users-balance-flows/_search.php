<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\AgentUsersBalanceFlows */
/* @var $form yii\widgets\ActiveForm */
?>
<style>
    .form-control{
        padding : 0px 0px;
    }
    /* 在小屏幕上的样式，标题显示在框内 */
    @media (max-width: 767px) {
        .control-label.hidden-xs {
            display: block;
            width: 100%;
            margin-bottom: 5px; /* 根据需要进行调整 */
        }
    }
</style>

<div class="agent-users-balance-flows-search">
    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="row">
        <div class="col-lg-2 col-xs-4">
            <?= $form->field($model, 'type')->dropDownList(
                [1=>'上分', 2=>'下分'], // Provide the options here
                ['prompt' => '-请选择-'] // Optional: Add a prompt message
            )->label('类型', ['class' => 'control-label hidden-xs'])->textInput(['placeholder' => '类型'])?>
        </div>
        <div class="col-lg-2 col-xs-4">
            <?= $form->field($model, 'member_account')->label('微信', ['class' => 'control-label hidden-xs'])->textInput(['placeholder' => '微信'])?>
        </div>
        <div class="col-lg-2 col-xs-4">
            <?= $form->field($model, 'member_id')->label('用户id', ['class' => 'control-label hidden-xs'])->textInput(['placeholder' => '用户id'])?>
        </div>
        <div class="col-lg-2 col-xs-4">
            <?= $form->field($model, 'status')->dropDownList(
                [0=>'待审核', 1=>'已审核', 2=>'已拒绝'], // Provide the options here
                ['prompt' => '-请选择-'] // Optional: Add a prompt message
            )->label('状态', ['class' => 'control-label hidden-xs'])->textInput(['placeholder' => '状态'])?>
        </div>
        <div class="col-lg-1 col-xs-4">
            <label> </label>
            <div class="form-group"> <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?> </div>
        </div>
        <div class="col-lg-1 col-xs-4">
            <label> </label>
            <div class="form-group"> <?= Html::resetButton('重置', ['class' => 'btn btn-default']) ?> </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
