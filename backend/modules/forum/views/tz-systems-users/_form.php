<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\TzSystemsUsers */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="tz-systems-users-form row">
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
        <?php $form = ActiveForm::begin(); ?>
            <!--?= $form->field($model, 'uid')->textInput() ?-->

            <!--?= $form->field($model, 'tz_system_id')->textInput() ?-->

            <!--?= $form->field($model, 'sys_name')->textInput(['maxlength' => true]) ?-->
            <div class="row">
                <div class="col-lg-6 col-xs-6">
                    <?= $form->field($model, 'account')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-lg-6 col-xs-6">
                    <?= $form->field($model, 'password')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">

                    <?= $form->field($model, 'ssc_domain')->textInput(['maxlength' => true])->label('网盘地址(格式：http://f9.ww666733.xyz:5678，或http://f9.ww666733.xyz)') ?>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <?= $form->field($model, 'cookie')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 col-xs-4">
                    <?= $form->field($model, 'odds_2d')->textInput() ?>
                </div>
                <div class="col-lg-4 col-xs-4">
                    <?= $form->field($model, 'odds_3d')->textInput() ?>
                </div>
                <div class="col-lg-4 col-xs-4">
                    <?= $form->field($model, 'odds_4d')->textInput() ?>
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <div class="col-lg-offset-2 col-lg-10">
                        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-danger']) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </section>
</div>
