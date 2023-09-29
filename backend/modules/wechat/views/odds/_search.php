<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\searchs\wechat\Odds */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="odds-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <div class="row">
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'play_method_id') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'name') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <?= $form->field($model, 'money') ?>
        </div>
        <div class="col-lg-2 col-xs-3">
            <div class="form-group">
                <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
                <?= Html::resetButton('重置', ['class' => 'btn btn-default']) ?>
            </div>
        </div>
        <?php // echo $form->field($model, 'bouns') ?>

        <?php // echo $form->field($model, 'odds') ?>

        <?php // echo $form->field($model, 'status') ?>

        <?php // echo $form->field($model, 'created_at') ?>

        <?php // echo $form->field($model, 'updated_at') ?>

        <?php // echo $form->field($model, 'update_at') ?>
    </div>
    <div class="row">
        <div class="form-group">
            <div class="col-lg-4 col-xs-4">
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
