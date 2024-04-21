<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\EyunAuth */
/* @var $form yii\widgets\ActiveForm */
$platforms = \common\helpers\Platform::TYPE_OPTIONS;
?>

<div class="eyun-auth-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="row">
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'platform_id')->dropDownList(
                $platforms, // Provide the options here
                ['prompt' => '-请选择-'] // Optional: Add a prompt message
            )->label('平台')?>
        </div>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'platform_robot_id')->label('TG名称') ?>
        </div>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'status')->dropDownList(
                ['0'=>'禁用', 1=>'已启用'], // Provide the options here
                ['prompt' => '-请选择-'] // Optional: Add a prompt message
            )->label('状态')?>
        </div>
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
