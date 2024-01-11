<?php

use backend\service\UserService;
use common\service\thirdD\CommonBaseService;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\wechat\Bets */
/* @var $form yii\widgets\ActiveForm */

$PlayMethods = \common\models\thirdD\PlayMethod::find()->where(['status'=>1])->asArray()->all();
$datas = array_column($PlayMethods, 'name', 'id');
$playMethodOptions = $datas;
$is3dAdmin = UserService::is3dAdmin(\Yii::$app->user->identity);
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
<div class="bets-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="row">
        <?php if($is3dAdmin){?>
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'username')->label('代理') ?>
        </div>
        <?}?>
        <div class="col-lg-2 col-xs-4">
            <?= $form->field($model, 'wechatUserName')
                ->label('微信ID', ['class' => 'control-label hidden-xs'])->textInput(['placeholder' => '微信ID'])
            ?>
        </div>
        <div class="col-lg-2 col-xs-4">
            <?= $form->field($model, 'order_id')
                ->label('订单ID', ['class' => 'control-label hidden-xs'])->textInput(['placeholder' => '订单ID'])
            ?>
        </div>
        <div class="col-lg-2 col-xs-4">
            <?= $form->field($model, 'play_method')->dropDownList(
                $playMethodOptions, // Provide the options here
                ['prompt' => '-请选择-'] // Optional: Add a prompt message
            )->label('玩法', ['class' => 'control-label hidden-xs'])?>
        </div>
        <div class="col-lg-2 col-xs-4">
            <?php echo $form->field($model, 'qihao')
                ->label('期号', ['class' => 'control-label hidden-xs'])->textInput(['placeholder' => '期号'])
            ?>
        </div>
        <div class="col-lg-2 col-xs-4">
            <?php echo $form->field($model, 'status')->dropDownList(
                CommonBaseService::STATUS_OPTIONS, ['prompt'=>'-选择状态-']
            )->label('状态', ['class' => 'control-label hidden-xs']); ?>
        </div>
        <div class="col-lg-2 col-xs-4">
            <?php echo $form->field($model, 'push_status')->dropDownList(
        \backend\models\thirdD\BetsBackend::PUSH_STATUS_OPTIONS, ['prompt'=>'-选择状态-']
            )->label('盘口', ['class' => 'control-label hidden-xs']); ?>
        </div>
        <div class="col-lg-2 col-xs-4">
            <?php echo $form->field($model, 'lottery_type')->dropDownList(
        CommonBaseService::THIRDD_LOTTERY_OPTIONS, ['prompt'=>'-选择彩种-']
            )->label('类', ['class' => 'control-label hidden-xs']); ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?php // echo $form->field($model, 'created_at') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <label> </label>
                <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
        </div>
        <div class="col-lg-2 col-xs-4">
            <label> </label>
                <?= Html::resetButton('重置', ['class' => 'btn btn-default']) ?>
        </div>

    <?php ActiveForm::end(); ?>

</div>
