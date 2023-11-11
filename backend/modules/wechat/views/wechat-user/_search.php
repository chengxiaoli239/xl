<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\wechat\WechatUser */
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

<div class="wechat-user-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="row">
        <div class="col-lg-2 col-xs-4">
            <?= $form->field($model, 'userName')
                ->label('微信ID', ['class' => 'control-label hidden-xs'])->textInput(['placeholder' => '微信ID'])
            ?>
        </div>

        <div class="col-lg-2 col-xs-4">
            <?= $form->field($model, 'nickName')
                ->label('昵称', ['class' => 'control-label hidden-xs'])->textInput(['placeholder' => '昵称'])
            ?>
        </div>

        <div class="col-lg-2 col-xs-4">
            <?= $form->field($model, 'remark')
                ->label('备注', ['class' => 'control-label hidden-xs'])->textInput(['placeholder' => '备注'])
            ?>
        </div>
        <div class="col-lg-2 col-xs-4">
            <?php echo $form->field($model, 'status')->dropDownList(
                ['0'=>'禁用', '1'=>'启用'], ['prompt'=>'-状态-']
            )->label('状态', ['class' => 'control-label hidden-xs']); ?>
        </div>

        <?php //$form->field($model, 'aliasName') ?>

        <?php // echo $form->field($model, 'status') ?>

        <?php // echo $form->field($model, 'balance') ?>

        <?php // echo $form->field($model, 'is_credit') ?>

        <?php // echo $form->field($model, 'bigHead') ?>

        <?php // echo $form->field($model, 'smallHead') ?>

        <?php // echo $form->field($model, 'labelList') ?>

        <?php // echo $form->field($model, 'remark') ?>

        <?php // echo $form->field($model, 'expire_time') ?>

        <?php // echo $form->field($model, 'created_at') ?>

        <?php // echo $form->field($model, 'updated_at') ?>

        <?php // echo $form->field($model, 'update_at') ?>

        <div class="col-lg-1 col-xs-4">
            <label> </label>
            <div class="form-group"> <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?> </div>
        </div>
        <div class="col-lg-1 col-xs-4">
            <label> </label>
            <div class="form-group"> <?= Html::resetButton('重置', ['class' => 'btn btn-default']) ?> </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>

</div>
