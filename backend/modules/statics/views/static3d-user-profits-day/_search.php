<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\statics\Static3dUserProfitsDay */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="static3d-user-profits-day-search">
   <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="row">
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'date')->label('日期')->textInput(['id' => 'date-picker'])?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'wechat_user_id')->label('客户id') ?>
        </div>
        <!--
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'bonus')->label('奖金') ?>
        </div>
        -->
        <div class="col-lg-2 col-xs-6">
            <label> </label>
            <div class="form-group">
                <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
                <?= Html::resetButton('重置', ['class' => 'btn btn-default']) ?>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>

<script>
layui.use('laydate', function(){
    var laydate = layui.laydate;

    // 执行日期选择器初始化
    laydate.render({
        elem: '#date-picker',
        type: 'date', // 日期选择的类型
        format: 'yyyy-MM-dd', // 日期格式
    });
});
</script>
