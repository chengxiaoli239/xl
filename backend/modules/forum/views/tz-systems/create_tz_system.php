<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\TzSystemsUsers */
/* @var $form yii\widgets\ActiveForm */
$this->title = '添加/编辑系统';
$user = \Yii::$app->user;
?>
<style>
    .panel {
        min-height: 300px; /* 设置最小高度 */
    }
    .user-update.create-user-form.row {
        height: 300px;
    }
    .save-btn{
        margin-bottom: 20px;
    }
    .modal-dialog {
        margin-top: 150px; /* 调整弹出框的顶部间距 */
    }
</style>
<div class="user-update create-user-form row">
    <section class="user-update user panel">
        <header class="panel-heading">
            <?= Html::encode($this->title); ?>
        </header>
        <?php $form = ActiveForm::begin([
            'action' => ['/forum/tz-systems/create'],
            'id' => 'createTzSystem'
        ]); ?>
        <div class="panel-body">
            <div class="row">
                <?= $form->field($model, 'id')->hiddenInput()->label(false); ?>
                <div class="col-lg-4 col-xs-6">
                    <?= $form->field($model, 'name')->label('站点名称')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-lg-4 col-xs-6">
                    <?= $form->field($model, 'kj_num')->dropDownList(
                        [4=>'4位', 5=>'5位'],
                        ['prompt' => '-选择-'] // Optional: Add a prompt message
                    )->label('位数')?>
                </div>
                <div class="col-lg-4 col-xs-12">
                    <?= $form->field($model, 'ssc_domain')->label('站点地址')->textInput(['maxlength' => true]) ?>
                </div>
                <?php if($user->id==1){?>
                <div class="col-lg-4 col-xs-12">
                    <?= $form->field($model, 'type')->checkboxList([1=>'时时彩', 2=>'网球', 3=>'3D'])->label('彩种类型') ?>
                </div>
                <div class="col-lg-4 col-xs-12">
                    <?= $form->field($model, 'tz_types')->checkboxList($allTzTypes)->label('已对接玩法') ?>
                </div>
                <?}?>
            </div>


            <div class="row save-btn">
                <div class="col-lg-offset-10 col-lg-6 col-xs-offset-5">
                        <?= Html::submitButton(Yii::t('app', 'Save'), ['id' => 'save-btn', 'class' => 'btn btn-danger']) ?>
                </div>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </section>
</div>

<script>
$('#save-btn').on('click', function(e) {
    console.log('000');
    e.preventDefault(); // 阻止默认提交行为

    // 获取表单数据并存储在 formData 变量中
    var formData = $('#createTzSystem').serializeArray();
    // 发起 AJAX 请求
    $.ajax({
        type: "POST",
        url: "/forum/tz-systems/create", // 后端接口地址
        data: formData, // 表单数据
        success: function(response) {
            console.log('111', response);
            if(response.status != 200){
                Ewin.alert(response.msg);
                //alert(response.msg)
                return
            }
            // 校验通过，再次提交表单
            $('#createTzSystem').submit();
        },
        error: function(xhr, status, error) {
            // 处理错误
        }
    });
});
</script>