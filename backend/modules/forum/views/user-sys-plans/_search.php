<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\UserSysPlans */
/* @var $form yii\widgets\ActiveForm */
$userId = \Yii::$app->user->id;
?>

<div class="user-sys-plans-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>
    <div class="row">
        <?php if($userId == 1){?>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'uid') ?>
        </div>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'account') ?>
        </div>
        <?php }?>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'status')->dropDownList([0=>'关闭', 1=>'开启'])->label('状态') ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
