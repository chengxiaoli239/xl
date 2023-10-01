<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\wechat\Odds */
/* @var $form yii\widgets\ActiveForm */
$playMethodOptions = [
    'option1' => 'Option 1',
    'option2' => 'Option 2',
    // Add more options as needed
];
?>

<div class="odds-search">
    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="row">
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'play_method_id')->label('Play Method ID') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'play_method_id')->dropDownList(
                $playMethodOptions, // Provide the options here
                ['prompt' => 'Select Play Method'] // Optional: Add a prompt message
            )?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'name') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'money') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <label> </label>
            <div class="form-group">
                <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
                <?= Html::resetButton('重置', ['class' => 'btn btn-default']) ?>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

</div>
