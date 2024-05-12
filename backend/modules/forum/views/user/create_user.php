<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\TzSystemsUsers */
/* @var $form yii\widgets\ActiveForm */
$this->title = '添加/编辑用户';
$user = \Yii::$app->user;
$sitesOptions = [];
foreach ($sites as $site){
    $sitesOptions[$site['id']] = $site['ssc_domain'].' ('.$site['kj_num'].'个数)';
}
$editing = isset($_GET['id']);

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
            'action' => ['/forum/user/create-user'],
            'id' => 'createUser'
        ]); ?>
        <div class="panel-body">
            <div class="row">
                <?= $form->field($model, 'id')->hiddenInput()->label(false); ?>
                <div class="col-lg-4 col-xs-6">
                    <?= $form->field($model, 'username')->label('后台账号')->textInput(['maxlength' => true, 'readonly' => $editing]) ?>
                </div>
                <div class="col-lg-4 col-xs-6">
                    <?= $form->field($model, 'password')->label('后台密码')->textInput(['maxlength' => true, 'placeholder' => $editing?'留空表示不修改':'']) ?>
                </div>
                <div class="col-lg-4 col-xs-12">
                    <?= $form->field($model, 'tz_system_id')->dropDownList(
                        $sitesOptions,
                        ['prompt' => '-选择-'] // Optional: Add a prompt message
                    )->label('盘口站点')?>
                </div>
                <div class="col-lg-4 col-xs-6">
                    <?= $form->field($model, 'site_account')->label('盘口账号')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-lg-4 col-xs-6">
                    <?= $form->field($model, 'site_password')->label('盘口密码')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-lg-4 col-xs-6">
                    <?= $form->field($model, 'secure_code')->label('安全码')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-lg-4 col-xs-6">
                    <?= $form->field($model, 'kj_num')->dropDownList(
                        [4=>'4位', 5=>'5位'],
                        ['prompt' => '-选择-'] // Optional: Add a prompt message
                    )->label('位数')?>
                </div>
                <div class="col-lg-4 col-xs-12">
                    <?= $form->field($model, 'description')->label('备注')->textarea(['maxlength' => true]) ?>
                </div>
            </div>

            <div class="row save-btn">
                <div class="col-lg-offset-10 col-lg-6 col-xs-offset-6">
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
    var formData = $('#createUser').serializeArray();
    // 发起 AJAX 请求
    $.ajax({
        type: "POST",
        url: "/forum/user/create-user", // 后端接口地址
        data: formData, // 表单数据
        success: function(response) {
            console.log('111', response);
            if(response.status != 200){
                Ewin.alert(response.msg);
                //alert(response.msg)
                return
            }
            // 校验通过，再次提交表单
            $('#createUser').submit();
        },
        error: function(xhr, status, error) {
            // 处理错误
        }
    });
});
</script>