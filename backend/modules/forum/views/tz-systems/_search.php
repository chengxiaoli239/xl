<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\TzSystems */
/* @var $form yii\widgets\ActiveForm */
$statusOptions = [
    0 => '已关闭',
    1 => '已开启',
];
?>

<div class="tz-systems-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="row">
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'name') ?>
        </div>

        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'ssc_domain')->label('站点') ?>
        </div>
        <div class="col-lg-2 col-xs-6">
            <?= $form->field($model, 'status')->dropDownList(
                    $statusOptions,
                    ['prompt' => '-请选择-'] // Optional: Add a prompt message
            )->label('开启状态'); ?>
        </div>

        <div class="col-lg-2 col-xs-6">
            <label>  </label>
            <div class="form-group">
                <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
                <?= Html::resetButton('重置', ['class' => 'btn btn-default']) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
