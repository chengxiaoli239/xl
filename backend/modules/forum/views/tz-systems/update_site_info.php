<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\TzSystems */
/* @var $form yii\widgets\ActiveForm */
$this->title = '站点添加:'.$model->name;
?>
<style>
    .user-update.tz-systems-users-form.row {
        height: 300px;
    }
</style>
<div class="user-update tz-systems-users-form row">
    <section class="user-update user panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <?php $form = ActiveForm::begin(); ?>
        <div class="panel-body">
            <div class="row">
                <div class="col-lg-4 col-xs-6">
                    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-lg-4 col-xs-6">
                    <?= $form->field($model, 'ssc_domain')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-lg-4 col-xs-6">
                    <?= $form->field($model, 'kj_num')->dropDownList(
                    [4=>'4个数', 5=>'5个数'], ['prompt'=>'-机器人-']
                    )->label('开奖号码', ['class' => 'control-label hidden-xs']); ?>
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
                <div class="col-lg-offset-6 col-lg-6">
                        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-danger']) ?>
                </div>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </section>
</div>
