<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\BetErrorPlansTask */
/* @var $form yii\widgets\ActiveForm */
$userId = \Yii::$app->user->id;
$userNameList = \common\models\AdminModel::find()->select(['username'])->where('id>1 and user_type=1')->indexBy('username')->column();
?>

<div class="bet-error-plans-task-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1,
            'class' => 'd-flex align-items-center' // 使用 Flexbox 对齐
        ],
    ]); ?>
    <div class="row">
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'plan_ids')->textInput(['placeholder' => '请输入ID', 'value'=>$plan_ids])->label('计划ID'); ?>
        </div>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'qihao')->textInput(['placeholder' => '请输入期号', 'value'=>$qihao])->label('期号'); ?>
        </div>
        <?php if($userId == 1){?>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'account')->dropDownList($userNameList, ['prompt' => '-请选择-'])->label('账号名称') ?>
        </div>
        <?php }?>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'status')->dropDownList([0=>'关闭', 1=>'开启'], ['prompt' => '-请选择-'])->label('状态') ?>
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