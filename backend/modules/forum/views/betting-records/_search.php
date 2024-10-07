<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\BettingRecords */
/* @var $form yii\widgets\ActiveForm */

$userId = \Yii::$app->user->id;
$userNameList = \common\models\AdminModel::find()->select(['username'])->where('id>1 and user_type=1')->indexBy('username')->column();
?>

<div class="betting-records-search">
    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <div class="row">
        <?php if($userId == 1){?>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'plan_id')->label('计划ID') ?>
        </div>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'account')->dropDownList($userNameList)->label('账号名称') ?>
        </div>
        <?php }?>
        <div class="col-lg-2 col-xs-6">
            <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
        </div>
        <div class="col-lg-2 col-xs-6">
            <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
