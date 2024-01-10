<link rel="stylesheet" href="/vendors/layui/2.5.4/css/layui.css?v=2020">
<link rel="stylesheet" href="/css/layui/global.css?v={{STATIC_VERSION}}">
<script type="text/javascript" src="/vendors/layui-layer/3.1.1/layer.js"></script>
<script type="text/javascript" src="/vendors/layui/2.4.5/layui.js"></script>
<script type="text/javascript" src="/vendors/atrtemplate/4.13.2/template-web.js"></script>
<script type="text/javascript" src="/statics/js/jquery-2.0.3.js"></script>
<script type="text/javascript" src="/js/layui/global.js?v={{STATIC_VERSION}}"></script>
<script type="text/javascript" src="/js/common.js?v={{STATIC_VERSION}}"></script>
<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\TzSystemsUsers */
/* @var $form yii\widgets\ActiveForm */
$this->title = '密码修改：'. \Yii::$app->user->identity['username'];
?>
<div class="update-password tz-systems-users-form row">
    <section class="user-update user panel">
        <?php $form = ActiveForm::begin([
            'id' => 'update-password-form',
            'enableAjaxValidation' => true, // Enable AJAX validation
            //'options' => ['class' => 'ajax-form'], // Add a class to identify the form in JavaScript
        ]); ?>
        <div class="panel-body">
            <div class="row">
                <div class="col-lg-6 col-xs-6">
                    <?= $form->field($model, 'password')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-lg-6 col-xs-6">
                    <?= $form->field($model, 're_password')->label('重复密码')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-offset-2 col-lg-10">
                        <?= Html::button(Yii::t('app', 'Save'), ['class' => 'btn btn-danger', 'id'=>'submit-update-password-btn']) ?>
                </div>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </section>
</div>
<script src="/statics/js/jquery-2.0.3.js"></script>
<script>
$('#submit-update-password-btn').on('click', function () {
    var form = $('#update-password-form');
    // 发送 AJAX 请求提交密码更新
    $.post('/forum/user/update-password', form.serialize(), function (response) {
        console.log('response:', response)
        if (response.status !== 200) {
            // 处理错误消息
            //layer.alert(response.msg, {icon: 2});
            alert(response.msg);
        } else {
            alert(response.msg);
            setTimeout(function(){
                location.reload(); // Reload the current page after 2 seconds
            }, 1000); // 2000 milliseconds (2 seconds)
            //layer.alert(response.msg, function(index){
            //    layer.close(index); // Close the alert
            //    setTimeout(function(){
            //        location.reload(); // Reload the current page after 2 seconds
            //    }, 1000); // 2000 milliseconds (2 seconds)
            //});
            // 关闭模态框等其他处理
            //$('#update-password-modal').modal('hide');
            // 可以进行页面刷新或其他操作
        }
    });
});
</script>