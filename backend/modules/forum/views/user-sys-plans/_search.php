<?php

use backend\service\SscDataService;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
$userId = \Yii::$app->user->id;
$userNameList = \common\models\AdminModel::find()->select(['username'])->where('id>1 and user_type=1')->indexBy('username')->column();
?>

<div class="user-sys-plans-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>
    <div class="row">
        <?php if($userId == 1){?>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'id')->label('计划ID') ?>
        </div>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'account')->dropDownList($userNameList, ['prompt' => '-请选择-'])->label('账号名称') ?>
        </div>
        <?php }?>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'status')->dropDownList([0=>'关闭', 1=>'开启'], ['prompt' => '-请选择-'])->label('状态') ?>
        </div>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'plan_type')->dropDownList(SscDataService::PLAN_TYPE_OPTIONS, ['prompt' => '-请选择-'])->label('类型') ?>
        </div>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'is_test')->dropDownList(SscDataService::TEST_TYPE_OPTIONS, ['prompt' => '-请选择-'])->label('计划类型') ?>
        </div>
        <div class="col-lg-2 col-xs-6 d-flex align-items-center">
            <div class="form-group mb-0" style="margin-top: 20px">
                <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
                <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
