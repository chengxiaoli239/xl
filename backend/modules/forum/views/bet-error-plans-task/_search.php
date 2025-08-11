<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\BetErrorPlansTask */
/* @var $form yii\widgets\ActiveForm */
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
            <?= $form->field($model, 'plan_id')->label('计划ID') ?>
        </div>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'qihao')->label('期号') ?>
        </div>
        <?php if($userId == 1){?>
            <div class="col-lg-2 col-xs-6">
                <?= $form->field($model, 'account')->dropDownList($userNameList, ['prompt' => '-选择-'])->label('账号名称') ?>
            </div>
        <?php }?>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
            <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
