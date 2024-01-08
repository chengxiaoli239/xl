<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\TzSystemsUsers */
/* @var $form yii\widgets\ActiveForm */
$this->title = '盘口信息修改:'.$model->sys_name;
?>
<div class="user-update tz-systems-users-form row">
    <section class="user-update user panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <?php $form = ActiveForm::begin(); ?>
        <div class="panel-body">
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
                <div class="col-lg-offset-2 col-lg-10">
                        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-danger']) ?>
                </div>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </section>
</div>
