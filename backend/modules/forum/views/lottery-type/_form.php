<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\LotteryType */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="lottery-type-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title) ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>
                <div class="row">
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'lottery_type')->textInput()->label('彩种lottery_type') ?>
                    </div>
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'sort')->textInput()->label('排序') ?>
                    </div>
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'name')->textInput(['maxlength' => true])->label('名称') ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'codeList')->textInput(['maxlength' => true])->label('可选号码') ?>
                    </div>
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'title')->textInput(['maxlength' => true])->label('标题') ?>
                    </div>
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'shortName')->textInput(['maxlength' => true])->label('简称') ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'info')->textInput(['maxlength' => true])->label('描述') ?>
                    </div>
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'onGetNoed')->textInput(['maxlength' => true])->label('事件函数') ?>
                    </div>
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'data_ftime')->textInput() ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'defaultViewGroup')->textInput() ?>
                    </div>
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'num')->textInput() ?>
                    </div>
                    <div class="col-lg-4 col-xs-4">
                        <?= $form->field($model, 'typeGroupName')->textInput(['maxlength' => true]) ?>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-offset-2 col-lg-10">
                        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-danger']) ?>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </section>
    </div>
</div>
